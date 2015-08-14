<?php

class FormWidgetTextfield extends FormWidgetAbstract
{
    public function widgetConfigurationForm(array $form_state, array $configuration, $lang, array $stored_values, array $available_values = array()) {
        // ce widget possède t-il un formulaire de configuration ?
        if(empty($form_state)) {return true;}

        $form = array(
            'configuration' => array(

                'label_style' => array(
                    '#type' => 'select',
                    '#title' => t("Label's style"),
                    '#options' => array(
                        'title' => t("Outside field"),
                        'placeholder' => t("Inside field"),
                        'none' => t("No label"),
                    ),
                    '#default_value' => isset($configuration['label_style']) ? $configuration['label_style'] : 'title',
                    '#weight' => 0,
                ),
            ),
        );

        return $form;
    }

    public function widgetConfigurationFormSubmit(array $form, array &$form_state, array &$configuration, array &$stored_values) {
        $configuration['label_style'] = $form_state['values']['configuration']['label_style'];
    }

    public function render(array $configuration, array $stored_values, array $default_value = array()) {

        $element = array(
            '#type' => 'textfield',
            '#default_value' => $default_value[0], // attention, les entrées utilisateur brutes sont des tableaux
        );

        switch($configuration['label_style']) {

            case 'normal':
                break;

            case 'placeholder':
                $element['#title'] = null;
                $element['#attributes']['placeholder'] = true;
                break;

            case 'none';
                $element['#title'] = null;
                break;
        }

        return $element;
    }

    public function compileValues(array $raw_value, array $configuration, array $stored_values) {
        return $raw_value;
    }
}
