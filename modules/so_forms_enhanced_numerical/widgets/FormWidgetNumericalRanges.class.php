<?php

class FormWidgetNumericalRanges extends FormWidgetAbstract
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
                'custom' => t("Custom option"),
            ),
            '#weight' => 3,
        );

        $form['configuration']['custom_select_all_option'] = array(
            '#type' => 'textfield',
            '#title' => t("Custom option \"Select all\""),
            '#default_value' => array_key_exists('custom_select_all_option', $configuration) ? $configuration['custom_select_all_option'] : "",
            '#size' => 20,
            '#states' => array(
                'visible' => array(
                    ':input[name="configuration[select_all_option]"]' => array('value' => 'custom'),
                ),
            ),
            '#weight' => 4,
        );

        $form['configuration']['display_label'] = array(
            '#type' => 'checkbox',
            '#title' => t("Display the select box label"),
            '#default_value' => $configuration['display_label'] === null ? 1 : $configuration['display_label'],
            '#weight' => 5,
        );

        $form['options'] = array(
            '#type' => 'fieldset',
            '#title' => t("Select box options"),
            '#description' => t("Define ranges to use as options.") . "<br />" . t("For open range, let one of the 'Limit' fields empty."),
        );

        $weight = -99;

        foreach($stored_values as $index => $stored_value) {

            $form['options'][$index] = array(

                'exposed_value' => array(
                    '#type' => 'textfield',
                    '#size' => 30,
                    '#default_value' => $stored_value['exposed_value'],
                ),

                'lower' => array(
                    '#type' => 'textfield',
                    '#size' => 10,
                    '#default_value' => $stored_value['lower'],
                ),

                'lower_included' => array(
                    '#type' => 'checkbox',
                    '#title' => t("included"),
                    '#default_value' => $stored_value['lower_included'],
                ),

                'upper' => array(
                    '#type' => 'textfield',
                    '#size' => 10,
                    '#default_value' => $stored_value['upper'],
                ),

                'upper_included' => array(
                    '#type' => 'checkbox',
                    '#title' => t("included"),
                    '#default_value' => $stored_value['upper_included'],
                ),

                'weight' => array(
                    '#type' => 'weight',
                    '#delta' => 100,
                    '#default_value' => $weight++,
                ),

                'delete' => array(
                    '#type' => 'submit',
                    '#value' => t("Delete"),
                    '#op' => 'delete',
                    '#name' => 'delete-' . $index,
                    '#option_id' => $index,
                ),
            );
        }

        $form['options']['new_option'] = array(
            'exposed_value' => array(
                '#type' => 'textfield',
                '#title' => t("Label"),
                '#size' => 30,
            ),

            'lower' => array(
                '#type' => 'textfield',
                '#title' => t("Lower limit"),
                '#size' => 10,
            ),

            'lower_included' => array(
                '#type' => 'checkbox',
                '#title' => t("included"),
            ),

            'upper' => array(
                '#type' => 'textfield',
                '#title' => t("Upper limit"),
                '#size' => 10,
            ),

            'upper_included' => array(
                '#type' => 'checkbox',
                '#title' => t("included"),
            ),

            'add' => array(
                '#type' => 'submit',
                '#value' => t("Add"),
                '#op' => 'add',
            ),
        );

        return $form;
    }

    public function widgetConfigurationFormValidate(array &$form, array &$form_state) {

        if($form_state['clicked_button']['#op'] == 'add') {
            if(empty($form_state['values']['options']['new_option']['exposed_value'])) {
                form_set_error('options][new_option][exposed_value', t("You have to set a label"));
            }
        }
    }

    public function widgetConfigurationFormSubmit(array $form, array &$form_state, array &$configuration, array &$stored_values) {

        if($form_state['clicked_button']['#op'] == 'delete') {
            unset($stored_values[$form_state['clicked_button']['#option_id']]);
            return;
        }

        $configuration['display_label'] = $form_state['values']['configuration']['display_label'];

        $stored_values = array();

        foreach($form_state['values']['options'] as $index => $option) {

            $weight = $option['weight'];

            if($index === 'new_option') {

                if(!empty($option['exposed_value'])) {
                    $weight = 99;
                } else {
                    continue;
                }
            }

            $stored_values[$index] = array(
                'exposed_value' => $option['exposed_value'],
                'lower' => $option['lower'],
                'lower_included' => $option['lower_included'],
                'upper' => $option['upper'],
                'upper_included' => $option['upper_included'],
                'weight' => $weight,
            );
        }

        $stored_values = array_values($stored_values); // ré-indexation

        // on stocke les options triées par poids : elles sont ainsi prêtes à être affichées
        $weights = array();
        foreach ($stored_values as $key => $value) {
            $weights[$key] = $value['weight'];
        }
        array_multisort($weights, SORT_ASC, $stored_values);

        $form_state['input'] = array(); // on vide le formulaire d'ajout
    }

    public function render(array $configuration, array $stored_values, array $default_value) {

        if(empty($stored_values)) {return;}

        global $language;
        $options = array();

        switch($configuration['select_all_option']) {
            case 1:
                $options = array('select_all' => "- " . t("Indifferent") . " -");
                break;

            case 2:
                if($language->language == 'en') {
                    $options = array('select_all' => "- All -");
                } else {
                    $options = array('select_all' => "- " . t("All (male)") . " -");
                }

                break;

            case 3:
                if($language->language == 'en') {
                    $options = array('select_all' => "- All -");
                } else {
                    $options = array('select_all' => "- " . t("All (female)") . " -");
                }
                break;

            case 'custom':
                $options = array('select_all' => $configuration['custom_select_all_option']);
                break;
        }

        foreach($stored_values as $index => $stored_option) {
            $options[$index] = $stored_option['exposed_value'];
        }

        $element = array(
            '#type' => 'select',
            '#options' => $options,
            '#default_value' => $default_value,
        );

        if($configuration['display_label'] === 0) {
            $element['#title'] = '';
        }

        return $element;
    }

    public function compileValues(array $raw_values, array $configuration, array $stored_values) {

        if($raw_values[0] == 'select_all') {return array();}

        $values = $stored_values[$raw_values[0]];

        if(empty($values['lower']) && empty($values['upper'])) {return array();}

        if(empty($values['lower']) && !empty($values['upper'])) {
            if($values['upper_included'] == true) {
                return array(
                    'type' => 'number_max_included',
                    'max' => $values['upper']
                );
            } else {
                return array(
                    'type' => 'number_max',
                    'max' => $values['upper']
                );
            }
        } elseif(empty($values['upper']) && !empty($values['lower'])) {
            if($values['lower_included'] == true) {
                return array(
                    'type' => 'number_min_included',
                    'min' => $values['lower'],
                );
            } else {
                return array(
                    'type' => 'number_min',
                    'min' => $values['lower'],
                );
            }
        } elseif(!empty($values['upper']) && !empty($values['lower'])) {
            if($values['upper_included'] == true && $values['lower_included'] == false) {
                return array(
                    'type' => 'range_upper_included',
                    'min' => $values['lower'],
                    'max' => $values['upper'],
                );
            } elseif($values['upper_included'] == false && $values['lower_included'] == true) {
                return array(
                    'type' => 'range_lower_included',
                    'min' => $values['lower'],
                    'max' => $values['upper'],
                );
            } elseif($values['upper_included'] == true && $values['lower_included'] == true) {
                return array(
                    'type' => 'range_both_included',
                    'min' => $values['lower'],
                    'max' => $values['upper'],
                );
            } elseif($values['upper_included'] == false && $values['lower_included'] == false) {
                return array(
                    'type' => 'range_none_included',
                    'min' => $values['lower'],
                    'max' => $values['upper'],
                );
            }
        }
    }

    public function themeWidgetConfigurationForm(array &$form) {

        $output = "";

        $rows = array();

        $rows[] = array(
            array(
                'data' => t("Add an option"),
                'colspan' => 4,
                'class' => array('container'),
            ),
        );

        $rows[] = array(
            drupal_render($form['options']['new_option']['exposed_value']),
            drupal_render($form['options']['new_option']['lower']) . drupal_render($form['options']['new_option']['lower_included']),
            drupal_render($form['options']['new_option']['upper']) . drupal_render($form['options']['new_option']['upper_included']),
            drupal_render($form['options']['new_option']['add']),
        );

        $add_option = array(
            '#markup' => theme('table', array('header' => array(), 'rows' => $rows)),
        );

        unset($form['options']['new_option']);

        $rows = array();

        foreach(element_children($form['options']) as $index) {

            $form['options'][$index]['weight']['#attributes']['class'] = array('field-weight');

            $rows[] = array(
                'data' => array(
                    drupal_render($form['options'][$index]['exposed_value']),
                    drupal_render($form['options'][$index]['weight']),
                    drupal_render($form['options'][$index]['lower']) . drupal_render($form['options'][$index]['lower_included']),
                    drupal_render($form['options'][$index]['upper']) . drupal_render($form['options'][$index]['upper_included']),
                    drupal_render($form['options'][$index]['delete']),
                ),
                'class' => array('draggable'),
            );
        }

        if(!empty($rows)) {
            $header = array(
                t("Exposed value"),
                t("Weight"),
                t("Lower limit"),
                t("Upper limit"),
                '',
            );

            $stored_options = array(
                '#markup' => theme('table', array('header' => $header, 'rows' => $rows, 'attributes' => array('id' => 'widget_options'))) . $form['options']['#value'],
            );

            drupal_add_tabledrag('widget_options', 'order', 'sibling', 'field-weight', null, null, true);

        } else {
            $header = null;
        }

        $form['options']['stored_options'] = $stored_options;
        $form['options']['new_option'] = $add_option;
    }
}