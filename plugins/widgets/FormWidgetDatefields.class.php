<?php

class FormWidgetDatefields extends FormWidgetAbstract
{
    public function widgetConfigurationForm(array $form_state, array $configuration, $lang, array $stored_values, array $available_values = array()) {
        // ce widget possède t-il un formulaire de configuration ?
        if(empty($form_state)) {return true;}

        $form = array();

        $form['configuration']['description'] = array(
            '#type' => 'textfield',
            '#title' => t("Help text"),
            '#default_value' => isset($configuration['description']) ? $configuration['description'] : '',
            '#size' => 30,
            '#weight' => 2,
        );

        $form['configuration']['field_from_label'] = array(
            '#type' => 'textfield',
            '#title' => t("Field 'start' label"),
            '#default_value' => isset($configuration['field_from_label']) ? $configuration['field_from_label'] : t("From", array(), array('langcode' => $lang)),
            '#required' => true,
            '#size' => 20,
            '#weight' => 3,
        );

        $form['configuration']['field_to_label'] = array(
            '#type' => 'textfield',
            '#title' => t("Field 'end' label"),
            '#default_value' => isset($configuration['field_to_label']) ? $configuration['field_to_label'] : t("To", array(), array('langcode' => $lang)),
            '#required' => true,
            '#size' => 20,
            '#weight' => 4,
        );

        return $form;
    }

    public function widgetConfigurationFormSubmit(array $form, array &$form_state, array &$configuration, array &$stored_values) {
        $configuration['description'] = $form_state['values']['configuration']['description'];
        $configuration['field_from_label'] = $form_state['values']['configuration']['field_from_label'];
        $configuration['field_to_label'] = $form_state['values']['configuration']['field_to_label'];
    }

    public function render(array $configuration, array $stored_values, array $default_value) {

        // si les valeurs ont été  passées dans l'URL, elles sont au format "array" et non "string"
        array_walk($default_value, function(&$val){
            $val = is_array($val) ? $val[0] : $val;
        });

        $element = array();

        $element['from'] = array(
            '#type' => 'textfield',
            '#title' => $configuration['field_from_label'],
            '#default_value' => $default_value['from'],
        );

        $element['to'] = array(
            '#type' => 'textfield',
            '#title' => $configuration['field_to_label'],
            '#default_value' => $default_value['to'],
        );

        return $element;
    }

    public function compileValues(array $raw_value, array $configuration, array $stored_values) {
        return $raw_value;
    }
}
