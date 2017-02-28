<?php

class FormWidgetSlider extends FormWidgetAbstract
{
    function widgetConfigurationForm(array $form_state, array $configuration, $lang, array $stored_values, array $available_values = array()) {

        if(empty($form_state)) {return true;}

        $available_values = array_filter($available_values, 'strlen');

        $min_val = str_replace('.', ',', min($available_values));
        $max_val = str_replace('.', ',', max($available_values));

        $form = array(
            'configuration' => array(
                'slider_type' => array(
                    '#type' => 'radios',
                    '#title' => t("Slider type"),
                    '#options' => array(
                        'number' => t("Exact number (ex : \"<strong>X</strong> people\")"),
                        'number_max' => t("Maximum number (ex : \"from 0 to <strong>X</strong>€\")"),
                        'absolute_number_max' => t("Absolute maximum number (ex : \"<strong>X</strong>€ and less\")"),
                        'number_min' => t("Minimum number (ex : \"from <strong>X</strong> to 200€\")"),
                        'absolute_number_min' => t("Absolute minimum number (ex : \"<strong>X</strong>€ and more\")"),
                        'range' => t("Numbers range (ex : \"from <strong>X</strong> to <strong>Y</strong> days\")"),
                    ),
                    '#default_value' => array_key_exists('slider_type', $configuration) ? $configuration['slider_type'] : null,
                    '#weight' => 0,
                ),

                'slider_min' => array(
                    '#type' => 'textfield',
                    '#title' => t("Minimum value"),
                    '#description' => !empty($available_values) ? t("Minimum available value : '@val'", array('@val' => $min_val)) : null,
                    '#size' => 5,
                    '#default_value' => array_key_exists('slider_min', $configuration) ?
                                        $configuration['slider_min'] :
                                        (!empty($available_values) ? $min_val : 0),
                    '#weight' => 2,
                ),

                'slider_max' => array(
                    '#type' => 'textfield',
                    '#title' => t("Maximum value"),
                    '#description' => !empty($available_values) ? t("Maximum available value : '@val'", array('@val' => $max_val)) : null,
                    '#size' => 5,
                    '#default_value' => array_key_exists('slider_max', $configuration) ?
                                        $configuration['slider_max'] :
                                        (!empty($available_values) ? $max_val : 100),
                    '#weight' => 4,
                ),

                'display_limits' => array(
                    '#type' => 'checkbox',
                    '#title' => t("Display the limits"),
                    '#default_value' => array_key_exists('display_limits', $configuration) ? $configuration['display_limits'] : false,
                    '#weight' => 6,
                ),

                'slider_step' => array(
                    '#type' => 'textfield',
                    '#title' => t("Interval between two values"),
                    '#description' => t("Can be decimal"),
                    '#field_suffix' => t("unit"),
                    '#size' => 3,
                    '#default_value' => array_key_exists('slider_step', $configuration) ? $configuration['slider_step'] : 1,
                    '#weight' => 8,
                ),

                'slider_text' => array(
                    '#type' => 'textfield',
                    '#title' => t("Value label"),
                    '#description' => t("Ex : \"Number of people\" will be displayed as this : \"<strong>Number of people</strong> : 3 people\""),
                    '#default_value' => array_key_exists('slider_text', $configuration) ? $configuration['slider_text'] : "",
                    '#weight' => 10,
                ),

                'slider_conversion' => array(
                    '#type' => 'select',
                    '#title' => t("Value conversion"),
                    '#description' => t("Display value in another unit. Ex : if the original unit of field value is '2500' (meters), if you choose 'Meters to kilometers', widget will display '2km500'"),
                    '#options' => array(
                        '' => "- " . t("No conversion") . " -",
                        t("Time conversion") => array(
                            'minutes_2_hours' => t("Minutes to hours"),
                            'seconds_2_hours' => t("Seconds to hours"),
                        ),
                        t("Distance conversion") => array(
                            'meters_2_kilometers' => t("Meters to kilometers"),
                        ),
                    ),
                    '#default_value' => array_key_exists('slider_conversion', $configuration) ? $configuration['slider_conversion'] : "",
                    '#weight' => 12,
                ),

                'slider_unit' => array(
                    '#type' => 'textfield',
                    '#title' => t("Value unit"),
                    '#description' => t("Ex : \" bedrooms\" will be displayed as this : \"Minimum number of bedrooms : 3 <strong>bedrooms</strong>\"") .
                    "<br />" .
                    t("You can use a space to separate the unit from the number."),
                    '#default_value' => array_key_exists('slider_unit', $configuration) ? $configuration['slider_unit'] : "",
                    '#size' => 15,
                    '#weight' => 14,
                    '#states' => array(
                        'visible' => array(
                            ':input[name="configuration[slider_conversion]"]' => array('value' => ''),
                        ),
                    ),
                ),
            ),
        );

        return $form;
    }

    public function render(array $configuration, array $stored_values, array $default_value) {

        drupal_add_library('system', 'ui.slider');
        drupal_add_js(drupal_get_path('module', 'so_forms_enhanced_numerical') . '/js/so_forms_enhanced_numerical.js', array('scope' => 'header', 'type' => 'file'));
        drupal_add_css(drupal_get_path('module', 'so_forms_enhanced_numerical') . '/css/so_forms_enhanced_numerical.css', array('type' => 'file'));

        $slider_text = !empty($configuration['slider_text']) ? $configuration['slider_text'] . " : " : "";
        $slider_min = (int)floor(str_replace(',', '.', $configuration['slider_min']));
        $slider_max = (int)floor(str_replace(',', '.', $configuration['slider_max']));
        $slider_step = (float)str_replace(',', '.', $configuration['slider_step']);
        $slider_unit = $configuration['slider_conversion'] == '' ? $configuration['slider_unit'] : '';

        $slider_js_options = array(
            'data-min' => $slider_min,
            'data-max' => $slider_max,
            'data-step' => $slider_step,
            'data-display_limits' => $configuration['display_limits'],
            'data-unit' => $slider_unit,
            'data-conversion' => $configuration['slider_conversion'],
        );

        if(array_key_exists('value_1', $default_value) && !empty($default_value['value_1'])) {
            $val_1 = $default_value['value_1'];
            $slider_initial_value = str_replace('.', ',', $val_1);
        } else {
            $val_1 = "?";
            $slider_initial_value = ($slider_max - $slider_min) / 2;
        }

        switch($configuration['slider_type']) {

            case 'number':
                $slider_js_options['data-text'] = $slider_text . "#1" . $slider_unit;
                $init_value_text = str_replace('#1', $val_1, $slider_js_options['data-text']);

                $slider_js_options['data-value'] = $slider_initial_value;
                break;

            case 'number_max':
                $slider_js_options['data-text'] = $slider_text . t("from @slider_min to #1", array('@slider_min' => str_replace('.', ',', $slider_min))) . $slider_unit;
                $init_value_text = str_replace('#1', $val_1, $slider_js_options['data-text']);

                $slider_js_options['data-range'] = 'min';
                $slider_js_options['data-value'] = $slider_initial_value;
                break;

            case 'absolute_number_max':
                $slider_js_options['data-text'] = $slider_text . t("#1@unit and less", array('@unit' => $slider_unit));
                $init_value_text = str_replace('#1', $val_1, $slider_js_options['data-text']);

                $slider_js_options['data-range'] = 'min';
                $slider_js_options['data-value'] = $slider_initial_value;
                break;

            case 'number_min':
                $slider_js_options['data-text'] = $slider_text . t("from #1 to @slider_max", array('@slider_max' => str_replace('.', ',', $slider_max))) . $slider_unit;
                $init_value_text = str_replace('#1', $val_1, $slider_js_options['data-text']);

                $slider_js_options['data-range'] = 'max';
                $slider_js_options['data-value'] = $slider_initial_value;
                break;

            case 'absolute_number_min':
                $slider_js_options['data-text'] = $slider_text . t("#1@unit and more", array('@unit' => $slider_unit));
                $init_value_text = str_replace('#1', $val_1, $slider_js_options['data-text']);

                $slider_js_options['data-range'] = 'max';
                $slider_js_options['data-value'] = $slider_initial_value;
                break;

            case 'range':

                $slider_js_options['data-text'] = $slider_text . t("from #1 to #2") . $slider_unit;

                $slider_js_options['data-range'] = 'true';

                if(array_key_exists('value_1', $default_value) && !empty($default_value['value_1'])) {
                    $val_2 = $default_value['value_2'];
                    $slider_js_options['data-values'] = "[" . $default_value['value_1'] . "," . $default_value['value_2'] . "]";

                } else {
                    $val_2 = $val_1;
                    $slider_js_options['data-values'] = array(
                        "[" . (($slider_max - $slider_min) / 3) . "," . ($slider_max - ($slider_max - $slider_min) / 3) . "]",
                    );
                }

                $init_value_text = str_replace('#1', str_replace('.', ',', $val_1), $slider_js_options['data-text']);
                $init_value_text = str_replace('#2', str_replace('.', ',', $val_2), $init_value_text);

                break;
        }

        $element = array(
            '#type' => 'fieldset',

            'value_display' => array(
                '#prefix' => "<div class='value_display'>",
                '#markup' => $init_value_text,
                '#suffix' => "</div>",
            ),

            'slider' => array(
                '#type' => 'container',
                '#attributes' => array('class' => array('so_forms_slider_wrapper')) + $slider_js_options,
            ),

            'value_1' => array(
                '#type' => 'hidden',
                '#attributes' => array('class' => array('value_1')),
                '#default_value' => array_key_exists('value_1', $default_value) ? $default_value['value_1'] : null,
            ),

            'value_2' => array(
                '#type' => 'hidden',
                '#attributes' => array('class' => array('value_2')),
                '#default_value' => array_key_exists('value_2', $default_value) ? $default_value['value_2'] : null,
            ),
        );

        return $element;
    }

    public function compileValues(array $raw_values, array $configuration, array $stored_values) {

        if(empty($raw_values['value_1']) && empty($raw_values['value_2'])) {return array();}

        return $raw_values + array(
            'type' => $configuration['slider_type'],
            'min' => $configuration['slider_min'],
            'max' => $configuration['slider_max'],
        );
    }
}