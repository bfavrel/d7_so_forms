<?php

/**
 * Provides available fields, new fields types, and new widgets to so_forms module
 *
 * IMPORTANT : never use ":" in any id ! Module already uses it as a separator.
 *
 * Note that this hook is called only on the fields overview page of each forms. On other pages, so_forms use light cached version of data.
 * Modules are responsible to sort their own fields by label or other criterias.
 *
 * About 'fields_definitions[callbacks] : all callbacks are called with these arguments :
 *  $field_infos, $langcode, $configuration (see configuration callback below) and (optional) additional arguments (see each callback documentation for details)
 * The first argument ('field_infos') is an array with these keys : field_name, field_context, field_module, field_type and widget_name.
 * The third argument ('configuration') contains calling module's custom parameters + the 'js_path' parameter : see the callback 'js' bellow for details.
 * Callbacks function have to be accessible in Drupal's scope, like hooks.
 *
 * About 'fields_definitions[callbacks][values]' : The callback must return a non-indexed array of values. The callback is responsible to sort its own values.
 * If a value must not be exposed 'as it', the callback can provide a human readable alias. In this case, instead of providing a simple value, it
 * can return an array('value' => 'the real value', 'alias' => 'the human friendly value').
 * Example, for a field of taxonomy type, callback should return a such array('value => 'the tid', 'alias' => 'the term name').
 *
 * About 'fields_definitions[callbacks][configuration]' : results of submission will be stored in field configuration.
 * Stored submission values will then be passed to all other callbacks.
 * Module are responsible to validate their own elements.
 * If the module wishes process its own element, a custom attribute '#submit' (string) can be set on its root array. It will be transfered
 * by Forms! as the first entry of the valid '#submit' attribute of the root form.
 *
 * About 'fields_definitions[callbacks][render]' : additional argument : $element.
 * This callback can't alter the element by reference (func_get_args() known issue). So, function has to return the element (an empty() test is performed anyway on returned value).
 *
 * About 'fields_definitions[callbacks][js]' : this callback can be called with this path : 'so_forms/js/[field secured id]/[operation]/[...]/[...]'
 * In addition to basic arguments (see above), the function receive the $op argument which is retrieved from the path : '[operation]'.
 * The path components which follow '[operation]' are each passed as an argument to the callback (ex : autocomplete operation will use the partial term as last argument).
 * This function mustn't perform itself a drupal_json(). It must return its raw values instead.
 *
 * About 'fields_definitions[context]' : a module can provide a same field several times, for it's own reasons.
 * So, such fields have to be differentiated from each others in the process chain : 'context' suits that feature.
 * The context value follows the field accross its lifetime, inside and outside so_forms module.
 * For example, 'field_context' is passed as a sub-argument to the 'value_callback' function. If the field was a taxonomy field, 'context' could be
 * the vocabulary id. Without this information, the module could only returns all terms in database instead of a filtered set of ones.
 * If 'context' isn't provided, module's custom id is used by default.
 *
 * @param string $module : module name
 * @param string/int $id : unique form id in module's context
 *
 * @return array :
 *      - fields_definitions : array of :
 *           field_name => array :
 *              - label : (string) : human readable name. No t().
 *              - language : language to use to filter field's values. If empty no filtering occurs.
 *              - type : (string/string array) : existing type(s) or custom type(s) which have to be defined below
 *              - widget : (string/string array) : force one or more widgets. Widgets not compatible with 'type' will be ignored.
 *              - context : (int/string) optional : the context which the field is provided in.
 *              - data : mixed : informations owned by the module and passed back to the various callbacks.
 *              - include_paths : (array) : non-associative arrays of scripts to include before execute callbacks :
 *                  - module : (string) : used to get base path of the script
 *                  - script : (string) script name without extension (use of modules module_load_include('inc', ...))
 *              - callbacks : array :
 *                  - values : (string) optional : function name which returns a non-indexed array of values
 *                  - configuration : (string) optional : function name which returns one or more configuration form elements to add
 *                  - render : (string) optional : function name in charge to alter the form element before it's displayed.
 *                  - js: (string) optional : function name which process various JS operations like autocompletion
 *      - types_definitions : array of :
 *           type => array :
 *              - label : human readable name. No t().
 *              - class : class name
 *      - widgets_definitions : array of :
 *           type => array :
 *              - label : human readable name. No t().
 *              - class : class name
 *
 */
function hook_so_forms($module, $id) {}

/**
 * Provides compatible widgets to fields' types.
 *
 * @param string $field_type
 *
 * @return array : list of registered widgets compatible with the given field type
 */
function hook_so_forms_compatible_widgets_info($field_type) {}