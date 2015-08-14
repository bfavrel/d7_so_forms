<?php

class FormFieldNumerical extends FormFieldAbstract
{
    public static function getCompatibleWidgets(array $field_definition) {

        $implemented_widgets = array(
            0 => 'textfield',
        );

        $implemented_widgets = array_unique(array_merge($implemented_widgets, parent::getCompatibleWidgets($field_definition)));

        return $implemented_widgets;
    }

    public function fieldConfigurationForm(array $form_state) {

        // les widgets de ce type de champ possèdent t-ils un formulaire de configuration ?
        if(empty($form_state)) {
            return true; // dans tous les cas ce sera true.
        }

        $form = array();

        $form['#tree'] = true;
        $form['#submit'] = array('so_forms_edit_field_submit'); // obligatoire afin d'avoir les submit callback dans le bon ordre

        $form['configuration']['module_custom'] = $this->executeCallback($this->_configuration_callback);

        // si le module souhaite intervenir sur la soumission de ses propres éléments de form
        // TODO : dangereux : le module a accès à tous les paramètres : c'est la porte ouverte au bricolage. A corriger sous D7.
        array_merge($form['#submit'], (array)$form['configuration']['module_custom']['#submit']);
        unset($form['configuration']['module_custom']['#submit']);

        switch($this->_widget_name) {

            case 'textfield':
                $form['configuration']['prefix'] = array(
                    '#type' => 'textfield',
                    '#title' => t("Field prefix"),
                    '#default_value' => $this->_configuration['prefix'],
                    '#size' => 20,
                    '#weight' => 0,
                );

                $form['configuration']['suffix'] = array(
                    '#type' => 'textfield',
                    '#title' => t("Field suffix"),
                    '#default_value' => $this->_configuration['suffix'],
                    '#size' => 20,
                    '#weight' => 1,
                );

                break;
        }

        $available_values = (array)$this->executeCallback($this->_values_callback);

        $form = array_merge_recursive($form, (array)$this->_widget->widgetConfigurationForm($form_state, $this->_configuration, $this->_language, $this->_stored_values, $available_values));

        return $form;
    }

    public function fieldConfigurationFormValidate(array &$form, array &$form_state) {
        $this->_widget->widgetConfigurationFormValidate($form, $form_state);
    }

    public function fieldConfigurationFormSubmit(array $form, array &$form_state) {

        $this->_configuration = $form_state['values']['configuration'];

        $this->_widget->widgetConfigurationFormSubmit($form, $form_state, $this->_configuration, $this->_stored_values);

        if(!empty($form_state['values']['configuration']['module_custom'])) {
            $this->_configuration['module_custom'] = $form_state['values']['configuration']['module_custom'];
        }
    }

    public function render(array $default_value) {
        $element = array(
            '#title' => $this->_label,
            '#field_prefix' => $this->_configuration['prefix'],
            '#field_suffix' => $this->_configuration['suffix'],
            '#weight' => $this->_weight,
            '#attributes' => array('class' => array('form-numerical')),
        );

        // Là, il faudrait passer l'élément par référence, pour effectuer la manip ci-après.
        // Mais cela nécessiterait de modifier l'ensemble des widgets -> pas le temps
        $element = array_merge($element, (array)$this->_widget->render($this->_configuration, $this->_stored_values, $default_value));

        if(array_key_exists('#attributes', $element) && array_key_exists('placeholder', $element['#attributes'])) {
            $element['#attributes']['placeholder'] = $this->_label;
        }

        if(!empty($this->_render_callback)) {
            // à cause de func_get_args() (copie des argument), il n'est malheureusement pas possible d'altérer l'élément par référence.
            $element = $this->executeCallback($this->_render_callback, $element);
        }

        return $element;
    }

    public function compileUserInputs(array &$raw_value) {

        $user_input = parent::compileUserInputs($raw_value);

        if($this->_widget_name == 'textfield' && is_numeric($raw_value[0]) == false) {return $user_input;}

        $user_input = array_merge($user_input, array('values' => $this->_widget->compileValues($raw_value, $this->_configuration, $this->_stored_values)));

        return $user_input;
    }
}
