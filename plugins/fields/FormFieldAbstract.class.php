<?php

/**
 * Abstract class for form fields types
 */
abstract class FormFieldAbstract
{
    public $debug; // a public variable containing the database field object for debugging purpose with dpm([field instance])

    protected $_field_id; // the field database id
    protected $_secured_id; // the field secured database id
    protected $_label;
    protected $_field; // 'module name':'field name':'field context'
    protected $_field_type;
    protected $_field_module;
    protected $_module_data;
    protected $_widget_name;
    protected $_widget; // instance du widget
    protected $_include_paths;
    protected $_values_callback;
    protected $_configuration_callback;
    protected $_render_callback;
    protected $_js_callback;
    protected $_stored_values; // values saved in database
    protected $_configuration;
    protected $_language;
    protected $_disable; // flag : si 'true' suggère au système de désactiver le champ
    protected $_weight;
    protected $_js_path;

    const JS_PATH_PREFIX = 'so_forms/js';

    /**
     * Constructor
     *
     * @param FormWidgetAbstract $widget : the widget to work on
     * @param stdClass $form_field : db stored field object
     * @param string $lang : lang code
     */
    public function __construct(stdClass $form_field, FormWidgetAbstract $widget, $lang) {
        $this->debug = $form_field;

        $this->_field_id = $form_field->id;
        $this->_secured_id = $form_field->secured_id;
        $this->_label = $form_field->label;
        $this->_field = $form_field->field;
        $this->_field_type = $form_field->field_type;
        $this->_widget_name = $form_field->widget;
        $this->_widget = $widget;
        $this->_stored_values = $form_field->field_values;
        $this->_language = $lang;
        $this->_weight = $form_field->weight;
        $this->_js_path = self::JS_PATH_PREFIX . '/' . $this->_secured_id;

        $params = $form_field->params;
        $this->_configuration = (array)$params['configuration'];
        $this->_module_data = $params['data'];

        $this->_include_paths = $params['include_paths'];
        $this->_values_callback = $params['callbacks']['values'];
        $this->_configuration_callback = $params['callbacks']['configuration'];
        $this->_render_callback = $params['callbacks']['render'];
        $this->_js_callback = $params['callbacks']['js'];

        $field = explode(':', $form_field->field);
        $this->_field_module = $field[0];

        $this->_disable = false;
    }

    /**
     * Let module know which widgets are available.
     * Defines which widgets this field type implements (performs some checks to eventually discard some, regarding of provided field definition).
     *
     * @param array $field_definition
     *
     * @return array : machine names of the widgets
     */
    public static function getCompatibleWidgets(array $field_definition) {

        $implemented_widgets = module_invoke_all('so_forms_compatible_widgets_info', $field_definition['type']);

        return $implemented_widgets;
    }

    /**
     * Optional form field's configuration form
     *
     * This method is first called by fields overview form, in order to know whether or not to provide an 'edit' link.
     * So, in case where $form_state is empty, if the widget implements a config form, it should return 'true' instead of a fully loaded array.
     *
     * @param array $form_state
     *
     * @return array
     */
    public function fieldConfigurationForm(array $form_state) {}

    /**
     * Optional form field's configuration form validation method
     *
     * @param array $form
     * @param array $form_state
     */
    public function fieldConfigurationFormValidate(array &$form, array &$form_state) {}

    /**
     * Optional form field's configuration form submission method
     *
     * @param array $form
     * @param array $form_state
     */
    public function fieldConfigurationFormSubmit(array $form, array &$form_state) {}

    /**
     * Returns the form element ready to be themed
     *
     * @param array $default_value
     *
     * @return array
     */
    abstract public function render(array $default_value);

    /**
     *  Return processed value(s)
     *
     * @param array &$raw_value submited form element value(s). Can be altered by reference.
     *
     * @return array of :
     *   [module name]:[field name]:[field context] => array :
     *          - module_name
     *          - field_name
     *          - field_context
     *          - field_type
     *          - widget
     *          - label
     *          - language
     *          - configuration : (array) module's stored configuration
     *          - values : (array) @see FormWidgetAbstract:compileValues()
     */
    public function compileUserInputs(array &$raw_value) {

        $field_infos = explode(':', $this->_field);

        $user_input = array(
            'module_name' => $field_infos[0],
            'field_id' => $this->_field_id,
            'field_name' => $field_infos[1],
            'field_context' => $field_infos[2],
            'data' => $this->_module_data,
            'field_type' => $this->_field_type,
            'widget' => $this->_widget_name,
            'label' => $this->_label,
            'language' => $this->_language,
        );

        if(!empty($this->_configuration['module_custom'])) {
            $user_input['configuration'] = $this->_configuration['module_custom'];
        }

        $user_input['values'] = array();

        return $user_input;
    }

    /**
     * Callback for AHAH/AJAX operations
     * This method mustn't perform itself a drupal_json(). It must return its values instead.
     *
     * @param string $op : operation requested
     * @param ... : additional arguments needed by the performed operation
     *
     * @return mixed
     */
    public function jsCallback($op) {
        $args = func_get_args();

        array_unshift($args, $this->_js_callback);

        foreach($this->_include_paths as $include) {
            module_load_include('inc', $include['module'], $include['script']);
        }

        return call_user_func_array(array($this, 'executeCallback'), $args);
    }

    /**
     * Returns configuration of the form field.
     *
     * @return array
     */
    public function getFieldConfiguration() {
        return $this->_configuration;
    }

    /**
     * Returns field values received from database and altered by config form
     *
     * @return array
     */
    public function getFieldValues() {
        return $this->_stored_values;
    }

    /**
     * In some case, field should be disable. This method give its advice if asked.
     * Example : if a select box is empty after deleting all options, we dont't want to expose it anymore to users.
     *
     * @return boolean
     */
    public function fieldShouldBeDisabled() {
        return $this->_disable;
    }

    /**
     * Return the unique field identifier (the secured one)
     *
     * @return string
     */
    public function getFieldIdentifier() {
        return $this->_secured_id;
    }

    /**
     * Field's human name, useful for drupal_title
     *
     * @return string
     */
    public function getFieldLabel() {
        return $this->_label;
    }

    /**
     * Theme the form field's configuration form elements
     *
     * @param array $form
     *
     * @return string
     */
    public function themeFieldConfigurationForm(array &$form) {
        $output = "";

        $output .= drupal_render($form['configuration']);

        $output .= $this->_widget->themeWidgetConfigurationForm($form);

        return $output;
    }

    /**
     * Helper : execute a given callback, and return result
     *
     * @param string $callback
     * @param ... : optional additional args
     *
     * @return mixed
     */
    protected function executeCallback($callback) {

        if(!empty($callback)) {
            $args = func_get_args();
            $args = array_slice($args, 1);

            $field_basic_infos = explode(':', $this->_field);

            $field_infos = array(
                'label' => $this->_label,
                'field_name' => $field_basic_infos[1],
                'field_context' => $field_basic_infos[2],
                'field_module' => $this->_field_module,
                'data' => $this->_module_data,
                'field_type' => $this->_field_type,
                'widget_name' => $this->_widget_name,
            );

            $this->_configuration['module_custom']['js_path'] = $this->_js_path;

            $arguments = array(
                $field_infos,
                $this->_language,
                $this->_configuration['module_custom'],
            );

            $args = array_merge($arguments, $args);

            if(!empty($this->_include_paths)) {
            foreach($this->_include_paths as $include) {
                module_load_include('inc', $include['module'], $include['script']);
                }
            }

            // field_infos, langcode, custom module's configuration and additional args
            return call_user_func_array($callback, $args);
        }

        return;
    }
}


