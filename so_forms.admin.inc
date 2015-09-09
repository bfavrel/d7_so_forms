<?php

/**
 * Fields overview for a given form
 *
 * @param array $form_state
 * @param string $sfid
 * @param string $lang
 *
 * @return array
 */
function so_forms_fields_overview($form, &$form_state, $module, $id, $display = 'default', $lang = null) {

    $sfid = so_forms_get_sfid($module, $id);

    $form_params = so_forms_load_form_params($sfid, $display);

    foreach($form_params->configuration['include_paths'] as $include) {
        module_load_include('inc', $include['module'], $include['script']);
    }

    if(drupal_get_title() == null) {
         drupal_set_title($form_params->name);
    }

    if(array_key_exists('destination', $_GET)) {
        $form_state['storage']['provenance'] = $_GET['destination'];
        unset($_GET['destination']);
    }

    $destination = array('destination' => $_GET['q']);

    $fields_pool = so_forms_load_fields_pool($sfid);
    $registered_fields_types = so_forms_registered_fields_types();
    $registered_widgets = so_forms_registered_widgets();

    // On met le cache des définitions de classes à jour.
    // On le fait à cet endroit, car il est inutile de rafraichir ce cache à chaque chargement du module.
    $cache = db_query("SELECT * FROM {so_forms} WHERE sfid=?", array($sfid))->fetchObject();

    $cache->params = unserialize($cache->params);

    $cache->params['definitions_cache'] = array(
        'registered_fields_types' => $registered_fields_types,
        'registered_widgets' => $registered_widgets,
    );

    $cache->params = serialize($cache->params);

    drupal_write_record('so_forms', $cache, 'sfid');

    $form = array(
        '#tree' => true,
        '#theme' => 'so_forms_fields_overview',

        'sfid' => array(
            '#type' => 'hidden',
            '#value' => $sfid,
        ),

        'display' => array(
            '#type' => 'hidden',
            '#value' => $display,
        ),

        'params' => array(),

        'fields' => array(),
    );

    $form_groups = so_forms_get_stored_groups($sfid, $display);

    foreach($form_groups as $key => &$form_group) {

        $form['fields']['group_' . $form_group->id] = array(
            '#depth' => 0,
            '#weight' => $form_group->weight,
            '#is_group' => 1,
            'weight' => array(
                '#type' => 'weight',
                '#delta' => 50,
                '#default_value' => $form_group->weight,
            ),
            'enabled' => array(
                '#type' => 'checkbox',
                '#default_value' => $form_group->enabled,
                '#return_value' => 1,
            ),
            'fid' => array(
                '#type' => 'textfield',
                '#default_value' => $form_group->id,
            ),
            'pfid' => array(
                '#type' => 'textfield',
                '#default_value' => null,
            ),
            'form_region' => array(
                '#type' => 'textfield',
                '#default_value' => $form_params->configuration['advanced'] == true ? $form_group->form_region : 'normal',
            ),
            'label' => array(
                '#type' => 'textfield',
                '#default_value' => html_entity_decode($form_group->label, ENT_QUOTES),
                '#size' => 30,
            ),
            'group_infos' => array(
                '#type' => 'markup',
                '#markup' => t("Group"),
            ),
            'operations' => array(
                'edit' => array(
                    '#markup' => l(t("configure"), "admin/so_forms/edit_group/" . $sfid . "/" . $form_group->id . "/" . $display, array('query' => $destination)),
                ),
                'delete' => array(
                    '#markup' => l(t('delete'), "admin/so_forms/delete_group/" . $sfid . "/" . $form_group->id, array('query' => $destination)),
                ),
            ),
        );
    }

    $form_fields = so_forms_get_stored_fields($sfid, $display);

    foreach($form_fields as $key => $form_field) {

        $field_name = explode(':', $form_field->field);

        $sub_form = array(
            '#depth' => (int)($form_field->gid != null),
            '#weight' => $form_field->weight,
            'weight' => array(
                '#type' => 'weight',
                '#delta' => 50,
                '#default_value' => $form_field->weight,
            ),
            'enabled' => array(
                '#type' => 'checkbox',
                '#default_value' => $form_field->enabled,
                '#return_value' => 1,
            ),
            'fid' => array(
                '#type' => 'textfield',
                '#default_value' => $form_field->id,
            ),
            'pfid' => array(
                '#type' => 'textfield',
                '#default_value' => $form_field->gid,
            ),
            'form_region' => array(
                '#type' => 'textfield',
                '#default_value' => $form_params->configuration['advanced'] == true ? $form_field->form_region : 'normal',
            ),
            'label' => array(
                '#type' => 'textfield',
                '#default_value' => html_entity_decode($form_field->label, ENT_QUOTES),
                '#size' => 30,
                '#attributes' => array('title' => t("Original field : !field_name", array('!field_name' => $field_name[1]))),
            ),
            'field_infos' => array(
                '#type' => 'markup',
                '#markup' => t($registered_fields_types[$form_field->field_type]['label']) . " : " . t($registered_widgets[$form_field->widget]['label']),
            ),

            'operations' => array(),
        );

        $field_instance = so_forms_get_field_instance($form_field->id, $display);
        $field_config_form = $field_instance->fieldConfigurationForm(array());

        if($display == 'default' && !empty($field_config_form)) {
            $sub_form['operations']['edit'] = array(
                '#markup' => l(t("configure"), "admin/so_forms/edit_field/" . $sfid . "/" . $form_field->id . "/" . $lang, array('query' => $destination)),
            );
        }

        if($display == 'default' && $form_params->configuration['api_only'] != true) {
            $sub_form['operations']['delete'] = array(
                '#markup' => l(t("delete"), "admin/so_forms/delete_field/" . $sfid . "/" . $form_field->id, array('query' => $destination)),
            );
        }

        if(!empty($form_field->gid)) {
            $form['fields']['group_' . $form_field->gid]['field_'. $form_field->id] = $sub_form;
        } else {
            $form['fields']['field_'. $form_field->id] = $sub_form;
        }
    }

    $weights = array();
    foreach ($form['fields'] as $key => $field) {
        $weights[$key] = $field['#weight'];
    }
    array_multisort($weights, SORT_ASC, $form['fields']);

    $form['params'] = array(
        '#type' => 'fieldset',
        '#title' => t("Form parameters for display '@display'", array('@display' => t($form_params->display_label))),
        '#collapsible' => true,
        '#collapsed' => true,
        '#tree' => true,

        'basic_params' => array(),

        'advanced_params' => array(),

        'save' => array(
            '#type' => 'submit',
            '#value' => t('Save configuration'),
            '#submit' => array('so_forms_fields_overview_params_submit'),
        ),

        'displays' => array(),

        'infos' => array(),
    );

    $form['params']['basic_params'] = array(
        '#theme_wrappers' => array('fieldset'),
        '#title' => t("Basic parameters"),

        'title' => array(
            '#type' => 'textfield',
            '#title' => t("Form's title"),
            '#default_value' => array_key_exists('title', $form_params->configuration) ? $form_params->configuration['title'] : $form_params->name,
            '#size' => 60,
        ),

        'buttons' => array(
            'submit' => array(
                '#type' => 'textfield',
                '#title' => t("Submit button's label"),
                '#description' => t("In english (it will be automatically translated at display).<br />No label = no button."),
                '#default_value' => $form_params->configuration['buttons']['submit'],
                '#size' => 25,
            ),

            'reset' => array(
                '#type' => 'textfield',
                '#title' => t("Reset button's label"),
                '#description' => t("In english (it will be automatically translated at display).<br />No label = no button."),
                '#default_value' => $form_params->configuration['buttons']['reset'],
                '#size' => 25,
            ),
        ),
    );

    if(user_access('administer so forms') == true) {

        $form['params']['advanced_params'] = array(
            '#type' => 'fieldset',
            '#title' => t("Advanced parameters"),
            '#collapsible' => true,
            '#collapsed' => true,

            'memorize' => array(
                '#type' => 'checkbox',
                '#title' => t("Store values in user's session ?"),
                '#default_value' => $form_params->configuration['memorize'],
            ),

            'url_key' => array(
                '#type' => 'checkbox',
                '#title' => t("Store values in database and provide an URL key ?"),
                '#default_value' => $form_params->configuration['url_key'],
            ),

            'advanced' => array(
                '#type' => 'checkbox',
                '#title' => t("Use the 'advanced' form region ?"),
                '#default_value' => $form_params->configuration['advanced'],
            ),

            'redirect' => array(
                '#type' => 'textfield',
                '#title' => t("Redirection path"),
                '#description' => t("Valid Drupal path with possibly URL GET parameters.<br />If this parameter is set, the redirect callback set for this form won't be called !"),
                '#default_value' => $form_params->configuration['redirect'],
                '#size' => 100,
            ),

            'classes' => array(
                '#type' => 'textfield',
                '#title' => t("Classes to apply to the form"),
                '#description' => t("Separated by commas."),
                '#default_value' => $form_params->configuration['classes'],
                '#size' => 60,
            ),
        );

        $display_operation_options = array(
            'duplicate' => t("Add an existing display"),
            'create' => t("Add a new display"),
        );

        if($display != 'default') {
            $display_operation_options['delete'] = t("Delete current display");
        }

        $existing_displays_results = db_query(
            "SELECT DISTINCT sfid, display, display_label FROM {so_forms_displays}
            WHERE display!='default'
            GROUP BY display
            HAVING sfid!=?",
            array($sfid)
        );

        $existing_displays_options = array();

        while($row = $existing_displays_results->fetchObject()) {
            $existing_displays_options[$row->display] = t($row->display_label);
        }

        if(empty($existing_displays_options)) {
            unset($display_operation_options['duplicate']);
        }

        $form_displays_results = db_query("SELECT display, display_label FROM {so_forms_displays} WHERE sfid=? ORDER BY display_label ASC", array($sfid));

        $from_displays_options = array(
            '' => "- " . t("nothing") . " -",
        );

        while($row = $form_displays_results->fetchObject()) {
            $from_displays_options[$row->display] = t($row->display_label);
        }

        $form['params']['displays'] = array(
            '#type' => 'fieldset',
            '#title' => t("Displays"),
            '#collapsible' => true,
            '#collapsed' => true,

            'operation' => array(
                '#type' => 'select',
                '#title' => t("Operation"),
                '#options' => $display_operation_options,
            ),

            'duplicate' => array(
                '#type' => 'container',
                '#states' => array(
                    'visible' => array(
                        ':input[name="params[displays][operation]"]' => array('value' => 'duplicate'),
                    ),
                ),

                'display' => array(
                    '#type' => 'select',
                    '#title' => t("Use existing"),
                    '#options' => $existing_displays_options,
                ),

                'from_display_form_configuration' => array(
                    '#type' => 'select',
                    '#title' => t("Copy form's configuration from"),
                    '#options' => $from_displays_options,
                ),

                'from_display_fields_configuration' => array(
                    '#type' => 'select',
                    '#title' => t("Copy field's configuration from"),
                    '#options' => $from_displays_options,
                ),
            ),

            'create' => array(
                '#type' => 'container',
                '#states' => array(
                    'visible' => array(
                        ':input[name="params[displays][operation]"]' => array('value' => 'create'),
                    ),
                ),

                'display_label' => array(
                    '#type' => 'textfield',
                    '#title' => t("Label"),
                    '#description' => t("translatable"),
                ),

                'display' => array(
                    '#type' => 'machine_name',
                    '#machine_name' => array(
                        'source' => array('params', 'displays', 'create', 'display_label'),
                        'standalone' => true,
                        'exists' => '_so_forms_check_display_name',
                    ),
                    '#required' => false,
                ),

                'from_display_form_configuration' => array(
                    '#type' => 'select',
                    '#title' => t("Copy form's configuration from"),
                    '#options' => $from_displays_options,
                ),

                'from_display_fields_configuration' => array(
                    '#type' => 'select',
                    '#title' => t("Copy field's configuration from"),
                    '#options' => $from_displays_options,
                ),
            ),

            'delete' => array(
                '#type' => 'container',
                '#states' => array(
                    'visible' => array(
                        ':input[name="params[displays][operation]"]' => array('value' => 'delete'),
                    ),
                ),

                'message' => array(
                    '#prefix' => "<div class='messages warning'>",
                    '#markup' => t("All parameters associated with this display will be deleted with no confirmation !"),
                    '#suffix' => "</div>",
                ),
            ),

            'proceed' => array(
                '#type' => 'submit',
                '#value' => t("Proceed"),
                '#submit' => array('so_forms_fields_overview_displays_submit'),
            ),
        );

        if(empty($existing_displays_options)) {
            unset($form['params']['displays']['duplicate']);
        }

        $sfid_infos = explode(':', $sfid); // 0 : module / 1 : id

        $form['params']['infos'] = array(
            '#type' => 'fieldset',
            '#title' => t("Informations"),
            '#collapsible' => true,
            '#collapsed' => true,

            'params' => array(
                '#type' => 'markup',
                '#prefix' => "<div>",
                '#markup' => "<strong>" . t("Form's ID") ." : </strong>" . $sfid . "<br />" .
                            "<strong>" . t("Form's secured ID") ." : </strong>" . $form_params->secured_sfid . "<br />" .
                            "<strong>" . t("Callbacks") ." : </strong><br />" .
                            "<ul>" .
                            "<li>process : " . (!empty($form_params->configuration['callbacks']['process']) ? $form_params->configuration['callbacks']['process'] . "()" : "<em>" . t("none") . "</em>") . "</li>" .
                            "<li>populate : " . (!empty($form_params->configuration['callbacks']['populate']) ? $form_params->configuration['callbacks']['populate'] . "()" : "<em>" . t("none") . "</em>") . "</li>" .
                            "<li>redirect : " . (!empty($form_params->configuration['callbacks']['redirect']) ? $form_params->configuration['callbacks']['redirect'] . "()" : "<em>" . t("none") . "</em>") . "</li>" .
                            "</ul>",
                '#suffix' => "</div>",
            ),

            'includes' => array(
                '#type' => 'markup',
                '#prefix' => "<div>",
                '#markup' => "<strong>" . t("The resulting form can be displayed using")  . " : </strong><br />" .
                            "<ul>" .
                            "<li>so_form(null, '" . $form_params->secured_sfid . "', '" . $display . "')</li>" .
                            "<li>so_form('" . $sfid_infos[0] . "', '" . $sfid_infos[1] . "', '" . $display . "')</li>" .
                            "</ul>",
                '#suffix' => "</div>",
            ),

                'templates' => array(
                '#type' => 'markup',
                '#prefix' => "<div>",
                '#markup' => "<strong>" . t("Templates")  . " : </strong><br />" .
                            "<ul>" .
                            "<li>so-form--" . $form_params->secured_sfid . "-" . $display .  ".tpl.php</li>" .
                            "<li>so-form--" . $form_params->secured_sfid . ".tpl.php</li>" .
                            "<li>so-form--" . $sfid_infos[0] . "-" . $sfid_infos[1] . "-" . $display .  ".tpl.php</li>" .
                            "<li>so-form--" . $sfid_infos[0] . "-" . $sfid_infos[1] . ".tpl.php</li>" .
                            "<li>so-form--" . $sfid_infos[0] . "-" . $display .  ".tpl.php</li>" .
                            "<li>so-form--" . $sfid_infos[0] . ".tpl.php</li>" .
                            "<li>so-form--" . $display .  ".tpl.php</li>" .
                            "<li>so-form.tpl.php</li>" .
                            "</ul>",
                '#suffix' => "</div>",
            ),
        );
    }

    if(!empty($form['fields'])) {
        $form['submit'] = array(
            '#type' => 'submit',
            '#value' => t('Save'),
            '#submit' => array('so_forms_fields_overview_fields_submit'),
        );
    }

    if($form_params->configuration['api_only'] != true && $display == 'default') {

        $fields_options = so_forms_modules_fields_list($sfid);
        $fields_options = array_merge(array('' => t('-- Select a field --')), $fields_options);

        $form['add_field'] = array();
        $form['add_field']['#depth'] = 1;
        $form['add_field']['label'] = array(
            '#type' => 'textfield',
            '#title' => t("Label"),
            '#description' => t("If empty, field label will be used."),
            '#size' => 25,
        );

        $form['add_field']['field'] = array(
            '#type' => 'select',
            '#title' => t('Field'),
            '#options' => $fields_options,
            '#attributes' => array('style' => 'max-width: 200px;'),
            '#ajax' => array(
                'callback' => 'so_forms_ajax_overview_add',
                'wrapper' => 'field_type_wrapper',
                'effect' => 'none',
                'event' => 'change',
                'method' => 'replace',
                'progress' => array(
                    'type' => 'throbber',
                    'message' => null,
                ),
            ),
        );

        $form['add_field']['field_wrapper'] = array(
            '#type' => 'container',
            '#prefix' => "<div id='field_type_wrapper'>",
            '#suffix' => "</div>",
        );

        $form['add_field']['field_wrapper']['field_type'] = array(
            '#type' => 'item',
            '#title' => t("Field type"),
            '#markup' => t("Select a field"),
            '#attributes' => array('style' => 'max-width: 200px;'),
        );

        $form['add_field']['submit'] = array(
            '#type' => 'submit',
            '#value' => t('Add a field'),
            '##op' => 'add_field',
            '#validate' => array('so_forms_fields_overview_add_validate'),
            '#submit' => array('so_forms_fields_overview_add_field_submit'),
        );
    }

    $selected_field = $form_state['values']['add_field']['field'];

    if($selected_field != '') {

        $field_types = (array)$fields_pool[$selected_field]['type'];
        $type_options = array();

        foreach($field_types as $field_type) {

            $available_widgets = $registered_fields_types[$field_type]['class']::getCompatibleWidgets($fields_pool[$selected_field]);

            if(!empty($fields_pool[$selected_field]['widget'])) {
                $available_widgets = array_intersect((array)$fields_pool[$selected_field]['widget'], $available_widgets);
            }

            $type_options[t($registered_fields_types[$field_type]['label'])] = array();

            foreach($available_widgets as $widget) {
                $type_options[t($registered_fields_types[$field_type]['label'])][$field_type . ':' . $widget] = t($registered_widgets[$widget]['label']);
            }
        }

        $form['add_field']['field_wrapper']['field_type'] = array(
            '#type' => 'select',
            '#title' => t("Field type"),
            '#options' => $type_options,
        );

        if(!empty($fields_pool[$selected_field]['callbacks']['values'])) {

            $function = $fields_pool[$selected_field]['callbacks']['values'];
            $field_infos = explode(':', $selected_field);

            $values = $function(
                array(
                    'field_name' => $field_infos[1],
                    'field_context' => $field_infos[2],
                    'field_module' => $field_infos[0],
                    'field_type' => $fields_pool[$selected_field]['type'],
                    'data' => $fields_pool[$selected_field]['data'],
                ),
                $fields_pool[$selected_field]['language'],
                null
            );

            if(!empty($values)) {

                if(count($values) > 25) {
                    $values = array_slice($values, 0, 25);
                    $more_marker = "[...]";
                }

                foreach($values as $value) {
                    $list .= (is_array($value) ? $value['alias'] : check_plain($value)) . "<br />";
                }

                    $list .= $more_marker;

                $form['add_field']['field_wrapper'][] = array(
                    '#type' => 'fieldset',
                    '#title' => t("Field values"),
                    '#collapsible' => true,
                    '#collapsed' => true,
                    'values' => array(
                        '#type' => 'markup',
                        '#prefix' => "<div>",
                        '#suffix' => "</div>",
                        '#markup' => $list,
                    ),
                );
            } else {
                $form['add_field']['field_wrapper'][] = array(
                    '#type' => 'markup',
                    '#markup' =>  t("No value available"),
                    '#prefix' => "<div>",
                    '#suffix' => "</div>",
                );
            }
        }
    }

    if($form_params->configuration['no_groups'] != true) {
        $form['add_group'] = array();
        $form['add_group']['#depth'] = 1;
        $form['add_group']['label'] = array(
            '#type' => 'textfield',
            '#title' => t("Title"),
            '#size' => 25,
        );
        $form['add_group']['submit'] = array(
            '#type' => 'submit',
            '#value' => t('Add a group'),
            '##op' => 'add_group',
            '#validate' => array('so_forms_fields_overview_add_validate'),
            '#submit' => array('so_forms_fields_overview_add_group_submit'),
        );
    }

    if(array_key_exists('back_path', $form_params->configuration) && !empty($form_params->configuration['back_path'])) {

        $form['back'] = array(
            '#markup' => l(t("Back"), $form_params->configuration['back_path']),
        );
    }

    return $form;
}

/**
 * Overview form validation.
 */
function so_forms_fields_overview_add_validate(&$form, &$form_state) {

    if($form_state['clicked_button']['##op'] == 'add_field') {
        if($form_state['values']['add_field']['field'] == '') {
            form_set_error('add_field][field', t('You have to select a field.'));
        }
        if(empty($form_state['values']['add_field']['field_wrapper']['field_type'])) {
            form_set_error('add_field][field_wrapper][field_type', t('You have to select a field type.'));
        }
    } elseif ($form_state['clicked_button']['##op'] == 'add_group') {
        if ($form_state['values']['add_group']['label'] == '') {
            form_set_error('add_group][label', t('You have to set a title for this group.'));
        }
    }
}

function so_forms_fields_overview_add_field_submit($form, &$form_state) {

    $fields_pool = so_forms_load_fields_pool($form_state['values']['sfid']);
    $form_params = explode(':', $form_state['values']['sfid']);
    $field_type_infos = explode(':', $form_state['values']['add_field']['field_wrapper']['field_type']);
    $field_infos = explode(':', $form_state['values']['add_field']['field']);

    if(empty($form_state['values']['add_field']['label'])) {
        $fields_list = so_forms_modules_fields_list($form_state['values']['sfid'], null, null, null, true);
        $label = $fields_list[$form_state['values']['add_field']['field']];
    } else {
        $label = $form_state['values']['add_field']['label'];
    }

    $field = array(
        'module' => $field_infos[0],
        'name' => $field_infos[1],
        'label' => $label,
        'type' => $field_type_infos[0],
        'widget' => $field_type_infos[1],
        'context' => $field_infos[2],
        'language' => $fields_pool[$form_state['values']['add_field']['field']]['language'],
        'include_paths' => $fields_pool[$form_state['values']['add_field']['field']]['include_paths'],
        'callbacks' => $fields_pool[$form_state['values']['add_field']['field']]['callbacks'],
        'data' => $fields_pool[$form_state['values']['add_field']['field']]['data']
    );

    so_forms_add_field($form_params[0], $form_params[1], $field);
}

function so_forms_fields_overview_add_group_submit($form, &$form_state) {

    $group = array(
        'id' => '',
        'sfid' => $form_state['values']['sfid'],
        'label' => $form_state['values']['add_group']['label'],
        'field' => '',
        'field_type' => 'group',
        'widget' => '',
        'field_values' => array(),
        'params' => array(),

        'enabled' => false,
        'form_region' => 'advanced',
        'gid' => '',
        'weight' => 50,
    );

    so_forms_store_field($group, $form_state['build_info']['args'][2]);
}

function so_forms_fields_overview_fields_submit($form, &$form_state) {

    // BUG : lorsqu'on change un fieldset de région, la région des champs inclus ne change pas.

    // on aplatit le tableau multi-dimensionnel - TODO : c'est lourd : optimiser
    $values = array();

    foreach($form_state['values']['fields'] as $key => $value) {
        if(preg_match('#^group_#', $key)) {
            $subvalues = array();
            foreach($value as $subkey => $subvalue) {
                if(preg_match('#^field_#', $subkey)) {
                    $subvalues[$subkey] = $subvalue;
                }
            }
            $values[$key] = $value;
            $values = array_merge($values, $subvalues);
        } else {
            $values[$key] = $value;
        }
    }

    $display = $form_state['build_info']['args'][2];

    foreach($values as $key => $form_field) {

        $record = (array)so_forms_get_stored_field($form_field['fid'], $display);

        $record = array_merge($record, array(
            'label' => $form_field['label'],

            'enabled' => $form_field['enabled'],
            'form_region' => $form_field['form_region'],
            'gid' => $form_field['pfid'],
            'weight' => $form_field['weight'],
        ));

        so_forms_store_field($record, $display);
    }

    drupal_set_message(t("Field's configuration has been saved"));
}

function so_forms_fields_overview_params_submit($form, &$form_state) {

    $display = $form_state['build_info']['args'][1];

    $form_default_params = so_forms_get_default_config();

    $stored_params = so_forms_load_form_params($form_state['values']['sfid'], $display);

    $form_params = $form_state['values']['params']['basic_params'];

    if(array_key_exists('advanced_params', $form_state['values']['params'])) {
        $form_params += $form_state['values']['params']['advanced_params'];
    }

    $display_configuration = array_intersect_key($stored_params->configuration, $form_default_params['display_params']);
    $display_configuration = array_merge($display_configuration, $form_params);

    db_query("REPLACE INTO {so_forms_displays} SET sfid=:sfid, display=:display, display_label=:displaylabel, configuration=:configuration", array(
        ':sfid' => $form_state['values']['sfid'],
        ':display' => $display,
        ':displaylabel' => $stored_params->display_label,
        ':configuration' => serialize($display_configuration),
    ));

    drupal_set_message(t("Form's configuration has been saved"));
}

function so_forms_fields_overview_displays_submit($form, &$form_state) {

    $sfid = $form_state['build_info']['args'][0];
    $display = $form_state['build_info']['args'][1];
    $form_default_params = so_forms_get_default_config();
    $operation = $form_state['values']['params']['displays'];

    switch($operation['operation']) {

        case 'duplicate':
            $params = $operation['duplicate'];
            $display_label = db_query("SELECT display_label FROM {so_forms_displays} WHERE display=? LIMIT 0,1", array($params['display']))->fetchColumn();

            break;

        case 'create':
            $params = $operation['create'];
            $display_label = $params['display_label'];

            break;
    }

    switch($operation['operation']) {

        case 'duplicate':
        case 'create':

            if(empty($params['from_display_form_configuration'])) {
                $configuration = serialize($form_default_params['display_params']);
            } else {
                $configuration = db_query("SELECT configuration FROM {so_forms_displays} WHERE sfid=:sfid AND display=:display", array(
                    ':sfid' => $sfid,
                    ':display' => $params['from_display_form_configuration'],
                ))->fetchColumn();
            }

            $form_display_record = array(
                'sfid' => $sfid,
                'display' => $params['display'],
                'display_label' => $display_label,
                'configuration' => $configuration,
            );

            drupal_write_record('so_forms_displays', $form_display_record);

            if(!empty($params['from_display_fields_configuration'])) {

                $group_displays_records = so_forms_get_stored_groups($sfid, $params['from_display_fields_configuration']);

                $gids_mapping = array();

                foreach($group_displays_records as $record) {

                    $gid_to_map = $record->id;
                    unset($record->id);
                    $stored_group = so_forms_store_field($record, $params['display']);
                    $gids_mapping[$gid_to_map] = $stored_group->id;
                }

                $fields_displays_records = so_forms_get_stored_fields($sfid, $params['from_display_fields_configuration']);

                foreach($fields_displays_records as $record) {

                    $record->display = $params['display'];
                    $record->gid = $gids_mapping[$record->gid];

                    drupal_write_record('so_forms_fields_displays', $record);
                }

            } else {

                $fields_displays_records = so_forms_get_stored_fields($sfid, 'default');

                foreach($fields_displays_records as $record) {

                    $record->display = $params['display'];
                    $record->enabled = false;
                    $record->form_region = 'normal';
                    $record->gid = '';

                    drupal_write_record('so_forms_fields_displays', $record);
                }
            }

            drupal_set_message(t("Display has been set."));

            break;

        case 'delete':

            db_query("DELETE FROM {so_forms_displays} WHERE sfid=? AND display=?", array($sfid, $display));

            $results = db_query("SELECT id FROM {so_forms_fields} WHERE sfid=?", array($sfid));

            while($row = $results->fetchObject()) {
                db_query("DELETE FROM {so_forms_fields_displays} WHERE fid=? AND display=?", array($row->id, $display));
            }

            drupal_set_message(t("Display has been deleted."));

            if(array_key_exists('storage', $form_state) && array_key_exists('provenance', $form_state['storage'])) {
                drupal_goto($form_state['storage']['provenance']);
            }

            break;
    }
}

/**
 * Form field's configuration form
 *
 * @param array $form
 * @param array &$form_state
 * @param int $id database id
 * @param string $lang
 *
 * @return array
 */
function so_forms_edit_field($form, &$form_state, $id, $lang = null) {

    $form = array(
        '#theme' => 'so_forms_edit_field',
    );

    if($lang == null) {
        $lang = language_default('language');
    }

    $field_instance = so_forms_get_field_instance($id, 'default', $lang);

    drupal_set_title(t("Field '@field' configuration", array('@field' => $field_instance->getFieldLabel())));

    $form = $field_instance->fieldConfigurationForm($form_state);

    $form['#sf_id'] = $id;
    $form['#sf_language'] = $lang;

    $form['submit'] = array(
        '#type' => 'submit',
        '#value' => t("Save"),
        '#validate' => array('so_forms_edit_field_validate'),
        '#submit' => array('so_forms_edit_field_submit'),
    );

    $form['back'] = array(
        '#markup' => l(t("Back"), $_GET['destination']),
    );

    return $form;
}

/**
 * Form field's configuration form validate
 */
function so_forms_edit_field_validate(&$form, &$form_state) {

    $field_instance = so_forms_get_field_instance($form['#sf_id'], 'default', $form['#sf_language']);
    $field_instance->fieldConfigurationFormValidate($form, $form_state);
}

/**
 * Form groups's configuration form
 *
 * @param array $form
 * @param array &$form_state
 * @param int $id database id
 * @param string $display
 *
 * @return array
 */
function so_forms_edit_group($form, &$form_state, $id, $display) {

    $group_infos = so_forms_get_stored_field($id, $display);

    $form = array(
        '#tree' => true,

        'configuration' => array(

            'display_label' => array(
                '#type' => 'checkbox',
                '#title' => t("Display the group label"),
                '#default_value' => !empty($group_infos->params['configuration']) ? $group_infos->params['configuration']['display_label'] : true,
            ),
        ),

        'submit' => array(
            '#type' => 'submit',
            '#value' => t("Save"),
            '#submit' => array('so_forms_edit_group_submit'),
        ),

        'back' => array(
            '#markup' => l(t("Back"), $_GET['destination']),
        ),
    );

    return $form;
}

function so_forms_edit_group_submit($form, &$form_state) {

    $group = so_forms_get_stored_field($form_state['build_info']['args'][0], $form_state['build_info']['args'][1]);

    $group->params['configuration'] = $form_state['values']['configuration'];

    so_forms_store_field($group, $form_state['build_info']['args'][1]);

    drupal_set_message(t("Group's' configuration has been saved."));
}

/**
 * Callback for AHAH/AJAX operations.
 *
 * @param string $secured_id
 * @param string $op
 * @param ... additionals arguments to pass to the class method
 *
 * @return string
 */
function so_forms_js_callback($secured_id, $op) {
    $args = (array)func_get_args();
    $args = array_slice($args, 1);

    $field_instance = so_forms_get_field_instance($secured_id, 'default', true, true);

    $output = call_user_func_array(array($field_instance, 'jsCallback'), $args);

    return drupal_json_output($output);
}

/**
 * Form field's configuration form submit
 */
function so_forms_edit_field_submit($form, &$form_state) {

    $field_instance = so_forms_get_field_instance($form['#sf_id'], 'default', $form['#sf_language']);
    $field_instance->fieldConfigurationFormSubmit($form, $form_state);

    $field = (array)so_forms_get_stored_field($form['#sf_id']);
    $field['params']['configuration'] = $field_instance->getFieldConfiguration();

    // le champ a demandé a être désactivé (par exemple : absence de valeurs, ou paramètre encore vierge, etc.)
    if($field_instance->fieldShouldBeDisabled() == true) {
        $field['enabled'] = false;
        drupal_set_message("Field has been disabled. You'll have to enable it again manually.", 'warning');
    }

    $field['field_values'] = $field_instance->getFieldValues();

    // problème : seul le display 'defaut' est désactivé
    so_forms_store_field($field);

    $form_state['rebuild'] = true;

    drupal_set_message("Field's configuration has been saved");
}

/**
 * Menu callback : delete a field
 *
 * @param int $id
 */
function so_forms_delete_field($id) {

    db_query("DELETE FROM {so_forms_fields} WHERE id=?", array($id));
    db_query("DELETE FROM {so_forms_fields_displays} WHERE fid=?", array($id));

    drupal_set_message('Field has been deleted');
    drupal_goto($_GET['destination']);
}

/**
 * Menu callback : delete a group
 *
 * @param int $id
 */
function so_forms_delete_group($id) {

    db_query("DELETE FROM {so_forms_fields} WHERE id=?", array($id));
    db_query("DELETE FROM {so_forms_fields_displays} WHERE fid=?", array($id));
    db_query("UPDATE {so_forms_fields_displays} SET gid='' WHERE gid=?", array($id));

    drupal_set_message('Group has been deleted');
    drupal_goto($_GET['destination']);
}

function so_forms_ajax_overview_add($form, $form_state) {
    return $form['add_field']['field_wrapper'];
}

/**
 * Returns a list of fields provided by modules
 *
 * @param string $sfid
 * @param string/array $modules : optional : filter by modules
 * @param string/array $types : optional : filter by field types
 * @param string/array $contexts : optional : filter by field contexts
 * @param boolean $flat : should the list be grouped by providers ?
 *
 * @return array : fields grouped by modules or not
 */
function so_forms_modules_fields_list($sfid = null, $modules = array(), $types = array(), $contexts = array(), $flat = false) {

    $fields_pool = so_forms_load_fields_pool($sfid);
    $registered_fields_types = so_forms_registered_fields_types();

    $fields_types_list = array_keys($registered_fields_types);
    $fields = array();

    foreach($fields_pool as $key => $field) {

        $field_infos = explode(':', $key);

        if(!empty($modules)) {
            if(!in_array($field_infos[0], (array)$modules)) {
                continue;
            }
        }

        if(!empty($contexts)) {
            if(!in_array($field_infos[2], (array)$contexts)) {
                continue;
            }
        }

        if(empty($types)) {$types = $fields_types_list;}
        $field_types = array_intersect((array)$field['type'], $fields_types_list, (array)$types);

        if(empty($field_types)) {
            continue;
        }

        $fields[$key] = $field['label'];
    }

    if($flat == true) {
        return $fields;
    }

    $list = array();

    foreach($fields as $key => $field) {

        $module = explode(':', $key);
        $module = $module[0];

        $list[$module][$key] = $field;
    }

    return $list;
}

/**
 * Theme the fields overview form
 */
function theme_so_forms_fields_overview($vars) {

    $output = "";

    $form = $vars['element'];

    $output .= drupal_render($form['params']);

    $display = $form['display']['#value'];
    $form_params = so_forms_load_form_params($form['sfid']['#value'], $display);
    $use_advanced_region = $form_params->configuration['advanced'];

    $rows = array();

    if($use_advanced_region == true) {
        $advanced_form_region_row = array(
            'data' => array(
                array(
                    'data' => t('Advanced form'),
                    'colspan' => 3,
                    'class' => array('region'),
                ),
                array(
                    'data' => "<input type='text' class='form-pfid' value=''/>",
                ),
                array(
                    'data' => "",
                    'colspan' => 2,
                    'class' => array('region'),
                ),
            ),
            'id' => 'form_fields_region_advanced',
            'class' => array('form_fields_region', 'tabledrag-root', 'tabledrag-leaf'),
        );
    }

    // on aplatit le tableau multi-dimensionnel - TODO : c'est lourd : optimiser
    $listing_form = array();

    foreach(element_children($form['fields']) as $key) {
        if(preg_match('#^group_#', $key)) {
            $listing_subform = array();
            foreach(element_children($form['fields'][$key]) as $subkey) {
                if(preg_match('#^field_#', $subkey)) {
                    $listing_subform[$subkey] = $form['fields'][$key][$subkey];
                    unset($form['fields'][$key][$subkey]);
                }
            }
            $listing_form[$key] = $form['fields'][$key];
            $listing_form = array_merge($listing_form, $listing_subform);
        } else {
            $listing_form[$key] = $form['fields'][$key];
        }

        unset($form['fields'][$key]);
    }

    foreach($listing_form as $key => &$element) {

        if($element['form_region']['#value'] == 'advanced' && $element['#depth'] == 0  && isset($advanced_form_region_row)) {
            $rows[] = $advanced_form_region_row;
            unset($advanced_form_region_row);
        }

        $operations = array();
        foreach(element_children($element['operations']) as $op) {
            $operations[] = drupal_render($element['operations'][$op]);
        }
        while (count($operations) < 2) {
            array_unshift($operations, '');
        }

        // Add special classes to be used for tabledrag.js.
        $element['pfid']['#attributes']['class'] = array('form-pfid');
        $element['fid']['#attributes']['class'] = array('form-fid');
        $element['weight']['#attributes']['class'] = array('form-weight');
        $element['form_region']['#attributes']['class'] = array('form_region_text');

        $row = array();

        $row[] = theme('indentation', array('size' => $element['#depth'])) . drupal_render($element['label']);
        $row[] = drupal_render($element['field_infos']) . drupal_render($element['group_infos']);
        $row[] = drupal_render($element['enabled']);
        $row[] = drupal_render($element['weight']) . drupal_render($element['pfid']) . drupal_render($element['fid']) . drupal_render($element['hidden_name']) . drupal_render($element['form_region']);
        $row = array_merge($row, $operations);

        if($element['#is_group'] == 1) {
            $classes = array('group draggable', 'tabledrag-root');
        } else {
            $classes = array('group draggable', 'tabledrag-leaf');
        }
        $rows[] = array('data' => $row, 'class' => $classes, 'id' => $key);
    }

    if (!empty($rows)) {
        if(isset($advanced_form_region_row)) {
            $rows[] = $advanced_form_region_row;
        }

        array_unshift($rows, array(
            'data' => array(
                array(
                    'data' => t("Normal form"),
                    'colspan' => 3,
                    'class' => array('region'),
                ),
                array(
                    'data' => "<input type='text' class='form-pfid' value=''/>",
                ),
                array(
                    'data' => "",
                    'colspan' => 2,
                    'class' => array('region'),
                ),
            ),
            'id' => 'form_fields_region_normal',
            'class' => array('form_fields_region', 'tabledrag-root', 'tabledrag-leaf'),
            'style' => ($use_advanced_region == true ? "" : "display: none;"),
        ));

        // En-tête du tableau
        $header = array(
            t('Field'),
            t('Type'),
            t('Enabled'),
            t('Weight'),
            array('data' => t('Operations'), 'colspan' => '2'),
        );
    }

    $output .= theme('table', array('header' => $header, 'rows' => $rows, 'attributes' => array('id' => 'so_form_fields')));

    drupal_add_tabledrag('so_form_fields', 'match', 'parent', 'form-pfid', null, 'form-fid', false, 1);
    drupal_add_tabledrag('so_form_fields', 'order', 'sibling', 'form-weight', null, null, true);

    drupal_add_js("
(function ($) {
    Drupal.behaviors.soFormsDrag = {
        attach: function (context, settings) {
            var formDrag = Drupal.tableDrag.so_form_fields;

            formDrag.onDrop = function() {
                draggedElement = this;
                var formRegion = $(draggedElement.rowObject.element).prevAll('tr.form_fields_region').attr('id').replace('form_fields_region_', '');
                $(draggedElement.rowObject.element).find('input.form_region_text').val(formRegion);
            }
        }
    };
})(jQuery);
        ", 'inline');

    $output .= drupal_render($form['submit']);

    if(array_key_exists('add_field', $form) && $form_params->configuration['api_only'] != true) {
        $rows = array();
        // Ajout du formulaire de création de champ
        $row = array();
        $element = &$form['add_field'];
        $row[] = drupal_render($element['label']);
        $row[] = drupal_render($element['field']);
        $row[] = drupal_render($element['field_wrapper']);
        $row[] = array('data' => drupal_render($element['submit']), 'style' => 'text-align: right;');
        $rows[] = array('data' => $row);

        $header = array(
                array('data' => t('Add field'), 'colspan' => '4')
        );

        $output .= theme('table', array('header' => $header, 'rows' => $rows, 'attributes' => array('id' => 'add_field')));
    }

    if($form_params->configuration['no_groups'] != true) {
        $rows = array();
        // Ajout du formulaire de création de groupe
        $row = array();
        $element = &$form['add_group'];
        $row[] = drupal_render($element['label']);
        $row[] = array('data' => drupal_render($element['submit']), 'style' => 'text-align: right;');
        $rows[] = array('data' => $row);

        $header = array(
                array('data' => t('Add group'), 'colspan' => '2')
        );

        $output .= theme('table', array('header' => $header, 'rows' => $rows, 'attributes' => array('id' => 'add_group')));
    }

    return $output . drupal_render_children($form);
}

/**
 * Implementation of hook_theme()
 * @param array $form
 */
function theme_so_forms_edit_field($vars) {
    $output = "";

    $form = $vars['element'];

    $field_instance = so_forms_get_field_instance($form['#sf_id'], 'default', $form['#sf_language']);
    $output .= $field_instance->themeFieldConfigurationForm($form);

    // form token, form_id, etc. au cas où ça ne serait pas déjà effectué dans une des deux classes.
    $output .= drupal_render_children($form);

    return $output;
}