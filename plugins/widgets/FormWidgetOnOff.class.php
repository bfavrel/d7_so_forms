<?php

class FormWidgetOnOff extends FormWidgetAbstract {

    public function widgetConfigurationForm(array $form_state, array $configuration, $lang, array $stored_values, array $available_values = array()) {

        // ce widget possède t-il un formulaire de configuration ?
        if(empty($form_state)) {return true;}

        $form = array();

        $form['options'] = array(
            '#type' => 'fieldset',
            '#title' => t("Values to use when the box is checked"),
            '#weight' => 50,
        );

        $values = array();
        $options = array();

        foreach($available_values as $available_value) {
            if(is_array($available_value)) {
                $value = $available_value['value'];
                $alias = $available_value['alias'];
            } else {
                $value = $available_value;
            }

            $value_id = md5($value);
            $values[$value_id] = $value;
            $options[$value_id] = !empty($alias) ? $alias : $value;
        }

        // ça, c'est moche : TODO : trouver mieux
        // problème du fait des transtypages en array, la valeur contient '[0] = 0'. Donc empty() renvoit false.
        if(count($stored_values) == 1 && array_pop(array_values($stored_values)) == '0') {
            $default_values = null;
        } else {
            $default_values = array_keys($stored_values);
        }

        $form['options']['available_values'] = array(
            '#type' => 'value',
            '#value' => $values,
        );

        $form['options']['checkbox_values'] = array(
            '#type' => 'select',
            '#title' => t("Available values"),
            '#multiple' => true,
            '#size' => 10,
            '#options' => $options,
            '#default_value' => $default_values,
        );

        return $form;
    }

    public function widgetConfigurationFormValidate(array &$form, array &$form_state) {
        if(empty($form_state['values']['options']['checkbox_values'])) {
            form_set_error('options][checkbox_values', t("You have to select at least one value"));
        }
    }

    public function widgetConfigurationFormSubmit(array $form, array &$form_state, array &$configuration, array &$stored_values) {
        $stored_values = array_intersect_key($form_state['values']['options']['available_values'], $form_state['values']['options']['checkbox_values']);
    }

    public function render(array $configuration, array $stored_values, array $default_value) {

        if(empty($stored_values)) {return;}

        return array(
            '#type' => 'checkbox',
            '#default_value' => array_pop($default_value),
            '#prefix' => "<div class='form-checkbox'>",
            '#suffix' => "</div>",
        );
    }

    public function compileValues(array $raw_value, array $configuration, array $stored_values) {
        $compiled_values = array();

        if($raw_value[0] == true) {
            $compiled_values = array_values($stored_values);
        }

        return $compiled_values;
    }

    public function themeWidgetConfigurationForm(array &$form) {
        $output = "";

        $output .= drupal_render($form['configuration']);
        $output .= drupal_render($form['options']);

        return $output;
    }
}
