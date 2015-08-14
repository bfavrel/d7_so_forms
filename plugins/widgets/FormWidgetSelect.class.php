<?php

class FormWidgetSelect extends FormWidgetCheckboxes
{
    public function widgetConfigurationForm(array $form_state, array $configuration, $lang, array $stored_values, array $available_values = array()) {
        // ce widget possède t-il un formulaire de configuration ?
        if(empty($form_state)) {return true;}

        $form = array();

        $form['configuration']['select_all_option'] = array(
            '#type' => 'select',
            '#title' => t("Add a 'select all' option"),
            '#default_value' => isset($configuration['select_all_option']) ? $configuration['select_all_option'] : 1,
            '#options' => array(
                0 => t("None"),
                1 => "\"- " . t("Indifferent") . " -\"",
                2 => "\"- " . t("All (male)") . " -\"",
                3 => "\"- " . t("All (female)") . " -\"",
            ),
            '#weight' => 3,
        );

        $form = array_merge_recursive($form, parent::widgetConfigurationForm($form_state, $configuration, $lang, $stored_values, $available_values));

        $form['configuration']['display_label']['#title'] = t("Distplay the label of the select box");

        if(isset($form['options'])) {
            $form['options']['#title'] = t("Select box options");
        }

        return $form;
    }

    public function widgetConfigurationFormSubmit(array $form, array &$form_state, array &$configuration, array &$stored_values) {

        $configuration['select_all_option'] = $form_state['values']['configuration']['select_all_option'];

        parent::widgetConfigurationFormSubmit($form, $form_state, $configuration, $stored_values);
    }

    public function render(array $configuration, array $stored_values, array $default_value) {

        if(empty($stored_values)) {return;}

        $element = parent::render($configuration, $stored_values, $default_value);

        $element['#type'] = 'select';

        global $language;

        $select_all_option = array();

        switch($configuration['select_all_option']) {
            case 1:
                $select_all_option = array('select_all' => "- " . t("Indifferent") . " -");
                break;

            case 2:
                if($language->language == 'en') {
                    $select_all_option = array('select_all' => "- All -");
                } else {
                    $select_all_option = array('select_all' => "- " . t("All (male)") . " -");
                }

                break;

            case 3:
                if($language->language == 'en') {
                    $select_all_option = array('select_all' => "- All -");
                } else {
                    $select_all_option = array('select_all' => "- " . t("All (female)") . " -");
                }
                break;
        }

        $element['#options'] = $select_all_option + $element['#options'];

        return $element;
    }

    public function compileValues(array $raw_values, array $configuration, array $stored_values) {

        $compiled_values = parent::compileValues($raw_values, $configuration, $stored_values);

        if($compiled_values[0] == 'select_all') {unset($compiled_values[0]);}

        return $compiled_values;
    }
}