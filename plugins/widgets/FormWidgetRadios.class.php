<?php

class FormWidgetRadios extends FormWidgetCheckboxes
{
    public function widgetConfigurationForm(array $form_state, array $configuration, $lang, array $stored_values, array $available_values = array()) {

        // ce widget possède t-il un formulaire de configuration ?
        if(empty($form_state)) {return true;}

        $form = parent::widgetConfigurationForm($form_state, $configuration, $lang, $stored_values, $available_values);

        $form['configuration']['display_label']['#title'] = t("Display the label of the radios buttons group");

        return $form;
    }

    public function render(array $configuration, array $stored_values, array $default_value) {

        if(empty($stored_values)) {return;}

        $element = parent::render($configuration, $stored_values, $default_value);

        $element['#type'] = 'radios';
        $element['#default_value'] = $default_value[0];

        return $element;
    }
}
