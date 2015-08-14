<?php

/**
 * Abstract class for form fields widgets
 */
abstract class FormWidgetAbstract 
{
    
    /**
     * Optional widget's configuration form elements
     * 
     * This method is first called by form field class, in order to tell to form fields overview form whether or not to provide an 'edit' link.
     * So, in case where $form_state is empty, if the widget implements a config form, it should return 'true' instead of a fully loaded array.
     *
     * TODO : correct method signature : string $available_values -> array $available_values
     * 
     * @param array $form_state
     * @param array $configuration
     * @param string $lang : langcode
     * @param array $stored_values : values saved in database
     * @param array $available_values values provided by modules for this field
     *
     * @return array
     */
    public function widgetConfigurationForm(array $form_state, array $configuration, $lang, array $stored_values, array $available_values = array()) {}
    
    /**
     * Optional widget's configuration form validation method
     *
     * @param array $form
     * @param array $form_state 
     */
    public function widgetConfigurationFormValidate(array &$form, array &$form_state) {}
    
    /**
     * Optional widget's configuration form submission method
     *
     * @param array $form
     * @param array $form_state 
     * @param array $configuration
     * @param array $stored_values
     */
    public function widgetConfigurationFormSubmit(array $form, array &$form_state, array &$configuration, array &$stored_values) {}
    
    /**
     * Returns the form element ready to be themed
     * 
     * @param array $configuration
     * @param array $stored_values : predefined values, managed by so_forms (hasn't to be confused with SESSION stored values !)
     *                               Example : checkboxes options.
     * @param array $default_value 
     * 
     * @return array
     */
    abstract public function render(array $configuration, array $stored_values, array $default_value);
    
    /**
     *  Return processed value(s)
     *
     * @param array $raw_value submited form element value(s)
     * @param array $configuration
     * @param array $stored_values 
     *
     * @return array : possibly :
     *      - non-indexed array of real values (one like textfield or several like checkboxes)
     *      - indexed array of real values (for widgets whith multiple identified sub-widgets, like datefields)
     */
    abstract public function compileValues(array $raw_values, array $configuration, array $stored_values);
    
    /**
     * Theme the widget's configuration form elements
     *
     * @param array $form
     * 
     * @return string
     */
    public function themeWidgetConfigurationForm(array &$form) {}
}
