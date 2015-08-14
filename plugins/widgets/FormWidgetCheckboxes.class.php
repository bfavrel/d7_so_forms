<?php

class FormWidgetCheckboxes extends FormWidgetAbstract
{
    public function widgetConfigurationForm(array $form_state, array $configuration, $lang, array $stored_values, array $available_values = array()) {

        // ce widget possède t-il un formulaire de configuration ?
        if(empty($form_state)) {return true;}

        $form = array();

        $form['configuration']['display_label'] = array(
            '#type' => 'checkbox',
            '#title' => t("Display the check boxes group label"),
            '#default_value' => $configuration['display_label'] === null ? 1 : $configuration['display_label'],
            '#weight' => 4,
        );

        switch($configuration['options_mode']) {
            case 1: // méthode additive : sélection des valeurs souhaitées

                $form['options'] = array(
                    '#type' => 'fieldset',
                    '#title' => t("Check boxes options"),
                    '#description' => t("Select values to use as predefined options"),
                );

                $default_weights = -99;

                foreach($available_values as $available_value) {

                    if(is_array($available_value)) {
                        $option = $available_value['value'];
                        $alias = $available_value['alias'];
                    } else {
                        $option = $available_value;
                        $alias = null;
                    }

                    $option_id = md5($option);

                    $form['options'][$option_id]['value'] = array(
                        '#type' => 'value',
                        '#value' => $option,
                    );

                    $form['options'][$option_id]['alias'] = array(
                        '#type' => 'value',
                        '#value' => $alias,
                    );

                    $form['options'][$option_id]['enabled'] = array(
                        '#type' => 'checkbox',
                        '#default_value' => !empty($stored_values[$option_id]),
                    );

                    if(!empty($stored_values[$option_id]['exposed_value'])) {
                        $exposed_value = $stored_values[$option_id]['exposed_value'];
                    } elseif(!empty($alias)) {
                        $exposed_value = $alias;
                    } else {
                        $exposed_value = $option;
                    }

                    $form['options'][$option_id]['exposed_value'] = array(
                        '#type' => 'textfield',
                        '#size' => 30,
                    	'#maxlength' => 256,
                        '#default_value' => $exposed_value,
                        '#required' => true,
                    );

                    if(isset($stored_values[$option_id])) {
                        $computed_weight = $stored_values[$option_id]['weight'];
                    } else {
                        $computed_weight = $default_weights++;
                    }

                    $form['options'][$option_id]['weight'] = array(
                        '#type' => 'weight',
                        '#delta' => 100,
                        '#default_value' => $computed_weight,
                    );

                    $form['options'][$option_id]['original_value'] = array(
                        '#type' => 'markup',
                        '#markup' => !empty($alias) ? $alias : $option,
                    );
                }

                // on est obligé de re-trier par les weights provenant de la BDD, car la liste se base sur celle fournie par le module
                $weights = array();
                foreach(element_children($form['options']) as $key) {
                    $weights[$key] = $form['options'][$key]['weight']['#default_value'];
                }

                asort($weights);
                $form['options'] = array_merge($weights, $form['options']);

                break;

            case 4: // méthode soustractive : désélection des valeurs non souhaitées

                $form['options'] = array(
                    '#type' => 'fieldset',
                    '#title' => t("Check boxes options"),
                    '#description' => t("Deselect values to discard"),
                );

                $unavailable_values = $stored_values;

                foreach($available_values as $available_value) {

                    if(is_array($available_value)) {
                        unset($unavailable_values[$available_value['value']]);
                    } else {
                        unset($unavailable_values[$available_value]);
                    }
                }

                array_walk($unavailable_values, function(&$val) {
                    $val['unavailable'] = true;
                });

                $available_values = $unavailable_values + $available_values;

                foreach($available_values as $available_value) {

                    if(is_array($available_value)) {
                        $option = $available_value['value'];
                        $alias = $available_value['alias'];
                    } else {
                        $option = $available_value;
                        $alias = null;
                    }

                    $option_id = $option;

                    $form['options'][$option_id]['value'] = array(
                        '#type' => 'value',
                        '#value' => $option,
                    );

                    $form['options'][$option_id]['alias'] = array(
                        '#type' => 'value',
                        '#value' => $alias,
                    );

                    $form['options'][$option_id]['enabled'] = array(
                        '#type' => 'checkbox',
                        '#default_value' => !array_key_exists($option_id, $stored_values),
                    );

                    $form['options'][$option_id]['exposed_value'] = array(
                        '#markup' => !empty($alias) ? $alias : $option,
                    );

                    if(array_key_exists('unavailable', (array)$available_value)) {
                        $form['options'][$option_id]['exposed_value']['#markup'] .= " (" . t("currently unavailable value") . ")";
                    }
                }

                break;

            case 2: // options custom à partir des valeurs existantes (usage unique des valeurs)

                $form['options'] = array(
                    '#type' => 'fieldset',
                    '#title' => t("Check boxes options"),
                );

                $sub_options = array();
                $sub_options_values = array();

                foreach($available_values as $available_value) {
                    if(is_array($available_value)) {
                        $sub_option = $available_value['value'];
                        $alias = $available_value['alias'];
                    } else {
                        $sub_option = $available_value;
                    }

                    $sub_option_id = md5($sub_option);

                    $sub_options[$sub_option_id] = !empty($alias) ? $alias : $sub_option;
                    $sub_options_values[$sub_option_id] = $sub_option;
                }

                $form['sub_options_values'] = array(
                    '#type' => 'value',
                    '#value' => $sub_options_values,
                );

                $form['sub_options_alias'] = array(
                    '#type' => 'value',
                    '#value' => $sub_options,
                );

                $unused_sub_options = $sub_options;

                foreach($stored_values as $option_id => $option) {
                    $unused_sub_options = array_diff_key($unused_sub_options, (array)$stored_values[$option_id]['values']);
                }

                foreach($stored_values as $option_id => $option) {
                    $form['options'][$option_id]['exposed_value'] = array(
                        '#type' => 'textfield',
                        '#size' => 30,
                        '#default_value' => $option['exposed_value'],
                        '#required' => true,
                    );

                    $form['options'][$option_id]['weight'] = array(
                        '#type' => 'weight',
                        '#delta' => 50,
                        '#default_value' => $option['weight'],
                    );

                    $form['options'][$option_id]['sub_options'] = array(
                        '#type' => 'select',
                        '#multiple' => true,
                        '#size' => 6,
                        '#options' => array_intersect_key($sub_options, array_merge((array)$stored_values[$option_id]['values'], $unused_sub_options)),
                        '#default_value' => array_keys((array)$stored_values[$option_id]['values']),
                    );

                    $form['options'][$option_id]['delete'] = array(
                        '#type' => 'submit',
                        '#value' => t("Delete"),
                        '#op' => 'delete',
                        '#name' => 'delete-' . $option_id,
                        '#option_id' => $option_id,
                    );
                }

                if(!empty($unused_sub_options)) {
                    $form['options']['new_option'] = array();

                    $form['options']['new_option']['exposed_value'] = array(
                        '#type' => 'textfield',
                        '#description' => t("If empty, checked value will be used."),
                        '#title' => t("Exposed value"),
                        '#size' => 30,
                    );

                    $form['options']['new_option']['sub_options'] = array(
                        '#type' => 'select',
                        '#title' => t("Available values"),
                        '#multiple' => true,
                        '#size' => 12,
                        '#options' => $unused_sub_options,
                    );

                    $form['options']['new_option']['add'] = array(
                        '#type' => 'submit',
                        '#value' => t("Add"),
                        '#op' => 'add',
                    );
                }

                break;

            case 3: // options custom à partir des valeurs existantes (usage multiple des valeurs)

                $form['options'] = array(
                    '#type' => 'fieldset',
                    '#title' => t("Check boxes options"),
                );

                $sub_options = array();
                $sub_options_values = array();

                foreach($available_values as $available_value) {
                    if(is_array($available_value)) {
                        $sub_option = $available_value['value'];
                        $alias = $available_value['alias'];
                    } else {
                        $sub_option = $available_value;
                    }

                    $sub_option_id = md5($sub_option);

                    $sub_options[$sub_option_id] = !empty($alias) ? $alias : $sub_option;
                    $sub_options_values[$sub_option_id] = $sub_option;
                }

                $form['sub_options_values'] = array(
                    '#type' => 'value',
                    '#value' => $sub_options_values,
                );

                $form['sub_options_alias'] = array(
                    '#type' => 'value',
                    '#value' => $sub_options,
                );

                foreach($stored_values as $option_id => $option) {
                    $form['options'][$option_id]['exposed_value'] = array(
                        '#type' => 'textfield',
                        '#size' => 30,
                        '#default_value' => $option['exposed_value'],
                        '#required' => true,
                    );

                    $form['options'][$option_id]['weight'] = array(
                        '#type' => 'weight',
                        '#delta' => 50,
                        '#default_value' => $option['weight'],
                    );

                    $form['options'][$option_id]['sub_options'] = array(
                        '#type' => 'select',
                        '#multiple' => true,
                        '#size' => 6,
                        '#options' => $sub_options,
                        '#default_value' => array_keys((array)$stored_values[$option_id]['values']),
                    );

                    $form['options'][$option_id]['delete'] = array(
                        '#type' => 'submit',
                        '#value' => t("Delete"),
                        '#op' => 'delete',
                        '#name' => 'delete-' . $option_id,
                        '#option_id' => $option_id,
                    );
                }

                $form['options']['new_option'] = array();

                $form['options']['new_option']['exposed_value'] = array(
                    '#type' => 'textfield',
                    '#description' => t("If empty, checked value will be used."),
                    '#title' => t("Exposed value"),
                    '#size' => 30,
                );

                $form['options']['new_option']['sub_options'] = array(
                    '#type' => 'select',
                    '#title' => t("Available values"),
                    '#multiple' => true,
                    '#size' => 12,
                    '#options' => $sub_options,
                );

                $form['options']['new_option']['add'] = array(
                    '#type' => 'submit',
                    '#value' => t("Add"),
                    '#op' => 'add',
                );

                break;
        }

        return $form;
    }

    public function widgetConfigurationFormValidate(array &$form, array &$form_state) {

        if($form_state['clicked_button']['#op'] == 'add') {
            if(empty($form_state['values']['options']['new_option']['exposed_value']) && !empty($form_state['values']['options']['new_option']['sub_options'])) {
                $form_state['values']['options']['new_option']['exposed_value'] = $form_state['values']['sub_options_alias'][current($form_state['values']['options']['new_option']['sub_options'])];
            }

            if(empty($form_state['values']['options']['new_option']['sub_options'])) {
                form_set_error('options][new_option][sub_options', t("You have to select at least one value"));
            }
        } else {
            unset($form_state['values']['options']['new_option']);
        }
    }

    public function widgetConfigurationFormSubmit(array $form, array &$form_state, array &$configuration, array &$stored_values) {

        $configuration['display_label'] = $form_state['values']['configuration']['display_label'];

        if($form_state['clicked_button']['#op'] == 'delete') {
            unset($stored_values[$form_state['clicked_button']['#option_id']]);

            // s'il n'y a plus de valeurs, on demande la désactivation du champ
            if(empty($stored_values)) {
                drupal_set_message(t("Field has no option anymore."), 'warning');
                return true;
            }

            return;
        }

        switch($configuration['options_mode']) {
            case 1: // méthode additive : sélection des valeurs souhaitées
                $stored_values = array();

                foreach(element_children($form_state['values']['options']) as $option_id) {
                    if($form_state['values']['options'][$option_id]['enabled'] == false) {
                        unset($form_state['input']['options'][$option_id]); // réinit de l'alias lors du rebuild
                        continue;
                    }

                    $stored_values[$option_id]['value'] = $form_state['values']['options'][$option_id]['value'];
                    $stored_values[$option_id]['exposed_value'] = $form_state['values']['options'][$option_id]['exposed_value'];

                    $stored_values[$option_id]['weight'] = $form_state['values']['options'][$option_id]['weight'];

                    if(array_key_exists('alias', $form_state['values']['options'][$option_id])) {
                        $stored_values[$option_id]['alias'] = $form_state['values']['options'][$option_id]['alias'];
                    }
                }

                // on stocke les options triées par poids : elles sont ainsi prêtes à être affichées
                $weights = array();
                foreach ($stored_values as $key => $value) {
                    $weights[$key] = $value['weight'];
                }
                array_multisort($weights, SORT_ASC, $stored_values);

                break;

            case 4: // méthode soustractive : désélection des valeurs non souhaitées

                $stored_values = array();

                foreach(element_children($form_state['values']['options']) as $option_id) {

                    if($form_state['values']['options'][$option_id]['enabled'] == false) {

                        $stored_values[$option_id] = array();

                        $stored_values[$option_id]['value'] = $form_state['values']['options'][$option_id]['value'];

                        if(array_key_exists('alias', $form_state['values']['options'][$option_id])) {
                            $stored_values[$option_id]['alias'] = $form_state['values']['options'][$option_id]['alias'];
                        }
                    }
                }

                break;

            case 2: // options custom à partir des valeurs existantes (usage unique des valeurs)
            case 3: // options custom à partir des valeurs existantes (usage multiple des valeurs)
                $stored_values = array();

                foreach(element_children($form_state['values']['options']) as $option_id) {

                    $stored_option_id = $option_id;
                    $weight = $form_state['values']['options'][$option_id]['weight'];

                    if($stored_option_id == 'new_option' && !empty($form_state['values']['options']['new_option'])) {
                        $stored_option_id = array_keys($form_state['values']['options']['new_option']['sub_options']);
                        $stored_option_id = md5(implode('', $stored_option_id));
                        $weight = 50;
                    }

                    $stored_values[$stored_option_id]['exposed_value'] = $form_state['values']['options'][$option_id]['exposed_value'];
                    $stored_values[$stored_option_id]['values'] = array_intersect_key((array)$form_state['values']['sub_options_values'], (array)$form_state['values']['options'][$option_id]['sub_options']);
                    $stored_values[$stored_option_id]['weight'] = $weight;
                }

                // on stocke les options triées par poids : elles sont ainsi prêtes à être affichées
                $weights = array();
                foreach ($stored_values as $key => $value) {
                    $weights[$key] = $value['weight'];
                }
                array_multisort($weights, SORT_ASC, $stored_values);

                break;
        }
    }

    public function render(array $configuration, array $stored_values, array $default_value) {

        if(empty($stored_values)) {return;}

        $options = array();

        foreach($stored_values as $option_id => $option) {
            $options[$option_id] = $option['exposed_value'];
        }

        $element = array(
            '#type' => 'checkboxes',
            '#options' => $options,
            '#default_value' => $default_value,
        );

        if($configuration['display_label'] === 0) {
            $element['#title'] = '';
        }

        return $element;
    }

    public function compileValues(array $raw_values, array $configuration, array $stored_values) {

        $compiled_values = array();

        switch($configuration['options_mode']) {
            case 1: // utilisation des valeurs existantes
                foreach($raw_values as $value) {
                    if($value == '0') {continue;}

                    $compiled_values[] = $stored_values[$value]['value'];
                }
                break;

            case 4: // méthode soustractive : désélection des valeurs non souhaitées
                foreach($raw_values as $value) {
                    if($value == '0') {continue;}

                    $compiled_values[] = $value;
                }
                break;

            case 2: // options custom à partir des valeurs existantes (usage unique des valeurs)
            case 3: // options custom à partir des valeurs existantes (usage multiple des valeurs)

                foreach($raw_values as $value) {
                    if($value == '0') {continue;}

                    $tmp_compiled_values = array();

                    foreach($stored_values[$value]['values'] as $sub_value) {
                        $tmp_compiled_values[] = $sub_value;
                    }

                    $compiled_values[] = $tmp_compiled_values;
                }
                break;
        }

        return $compiled_values;
    }

    public function themeWidgetConfigurationForm(array &$form) {

        if(empty($form['options'])) {return;}

        $output = "";

        // '#default_value' : si le form est en erreur '#value' va contenir la nouvelle option, mais le submit n'aura pas
        // eu lieu, et ce sont les valeurs de la précédente option qui seront fournie pour le rebuild
        switch($form['configuration']['options_mode']['#default_value']) {

            case 1: // méthode additive : sélection des valeurs souhaitées
                drupal_add_js("
                    (function ($) {
                        $(document).ready(function(){
                            $('input#check_uncheck_all').click(function(){
                                $(this).parents('table').find('input[type=checkbox]').attr('checked', this.checked);
                            });
                        });
                    })(jQuery);
                ", 'inline');

                $rows = array();

                foreach(element_children($form['options']) as $option_id) {

                    $form['options'][$option_id]['weight']['#attributes']['class'] = array('field-weight');

                    $rows[] = array(
                        'data' => array(
                            drupal_render($form['options'][$option_id]['enabled']),
                            drupal_render($form['options'][$option_id]['exposed_value']),
                            drupal_render($form['options'][$option_id]['weight']),
                            array(
                                'data' => drupal_render($form['options'][$option_id]['original_value']),
                                'title' => $form['options'][$option_id]['value']['#value'],
                            ),
                        ),
                        'class' => array('draggable'),
                    );
                }

                $header = array(
                    "<input id='check_uncheck_all' type='checkbox'/> " . t("Exposed"),
                    t("Exposed value"),
                    t("Weight"),
                    t("Value"),
                );

                $form['options']['#value'] = theme('table', array('header' => $header, 'rows' => $rows, 'attributes' => array('id' => 'widget_options')));

                drupal_add_tabledrag('widget_options', 'order', 'sibling', 'field-weight', null, null, true);

                $output .= drupal_render($form['options']); // fieldset

                break;

            case 4: // méthode soustractive : désélection des valeurs non souhaitées

                drupal_add_js("
                    (function ($) {
                        $(document).ready(function(){
                            $('input#check_uncheck_all').click(function(){
                                $(this).parents('table').find('input[type=checkbox]').attr('checked', this.checked);
                            });
                        });
                    })(jQuery);
                ", 'inline');

                $rows = array();

                foreach(element_children($form['options']) as $option_id) {

                    $rows[] = array(
                        drupal_render($form['options'][$option_id]['enabled']),
                        array(
                            'data' => drupal_render($form['options'][$option_id]['exposed_value']),
                            'title' => $form['options'][$option_id]['value']['#value'],
                        ),
                    );
                }

                $header = array(
                    "<input id='check_uncheck_all' type='checkbox' checked='checked'/> " . t("Exposed"),
                    t("Exposed value"),
                );

                $form['options']['#value'] = theme('table', array('header' => $header, 'rows' => $rows, 'attributes' => array('id' => 'widget_options')));

                $output .= drupal_render($form['options']); // fieldset

                break;

            case 2: // options custom à partir des valeurs existantes (usage unique des valeurs)
            case 3: // options custom à partir des valeurs existantes (usage multiple des valeurs)
                if(!empty($form['options']['new_option'])) {
                    $rows = array();

                    $rows[] = array(
                        array(
                            'data' => t("Add an option"),
                            'colspan' => 3,
                            'class' => array('container'),
                        ),
                    );

                    $rows[] = array(
                        drupal_render($form['options']['new_option']['exposed_value']),
                        drupal_render($form['options']['new_option']['sub_options']),
                        drupal_render($form['options']['new_option']['add']),
                    );

                    $form['options']['#value'] .= theme('table', array('header' => array(), 'rows' => $rows));

                    unset($form['options']['new_option']);
                }

                $rows = array();

                foreach(element_children($form['options']) as $option_id) {

                    $form['options'][$option_id]['weight']['#attributes']['class'] = array('field-weight');

                    $rows[] = array(
                        'data' => array(
                            drupal_render($form['options'][$option_id]['exposed_value']),
                            drupal_render($form['options'][$option_id]['weight']),
                            drupal_render($form['options'][$option_id]['sub_options']),
                            drupal_render($form['options'][$option_id]['delete']),
                        ),
                        'class' => array('draggable'),
                    );
                }

                if(!empty($rows)) {
                    $header = array(
                        t("Exposed value"),
                        t("Weight"),
                        t("Values"),
                        '',
                    );

                    $form['options']['#value'] = theme('table', array('header' => $header, 'rows' => $rows, 'attributes' => array('id' => 'widget_options'))) . $form['options']['#value'];

                    drupal_add_tabledrag('widget_options', 'order', 'sibling', 'field-weight', null, null, true);
                } else {
                    $header = null;
                }

                $output .= drupal_render($form['options']); // fieldset

                break;
        }

        return $output;
    }
}