(function($, Drupal, window, document) {

    Drupal.behaviors.soFormsEnhancedNumerical = {

        attach: function(context, settings) {

            $('fieldset.so_forms_numerical_slider div.so_forms_slider_wrapper').each(function() {

                var widgetOptions = $(this).data();

                var hardOptions = {
                    'animate': 'fast',
                    'change': function(event, ui) {

                        if(typeof(ui.values) == 'undefined') {
                            $(this).siblings('input.value_1').val(ui.value);
                        } else {
                            $(this).siblings('input.value_1').val(ui.values[0]);
                            $(this).siblings('input.value_2').val(ui.values[1]);
                        }
                    },
                    'slide': function(event, ui) {

                        var valueDisplay = widgetOptions.text;

                        if(typeof(ui.values) == 'undefined') {
                            valueDisplay = valueDisplay.replace('#1', convertValue(ui.value, widgetOptions.conversion).replace('.', ','));
                        } else {
                            valueDisplay = valueDisplay.replace('#1', convertValue(ui.values[0], widgetOptions.conversion).replace('.', ','));
                            valueDisplay = valueDisplay.replace('#2', convertValue(ui.values[1], widgetOptions.conversion).replace('.', ','));
                        }

                        $(this).siblings('div.value_display').text(valueDisplay);
                    }
                };

                var options = $.extend({}, hardOptions, widgetOptions);

                $(this).slider(options);

                if(options.display_limits == true) {
                    $(this).after("<div class='slider_limit slider_min'>" + convertValue(options.min, widgetOptions.conversion) + options.unit + "</div>");
                    $(this).after("<div class='slider_limit slider_max'>" + convertValue(options.max, widgetOptions.conversion) + options.unit + "</div>");
                }
            });

            function convertValue(value, conversionType) {

                switch(conversionType) {

                    case 'seconds_2_hours':
                        value = value / 60;

                    case 'minutes_2_hours':

                        var hours = Math.floor(value / 60);
                        var minutes = Math.floor(value % 60);

                        if(hours == 0) {
                            return minutes + "min";
                        } else {
                            minutes = ("0" + minutes).slice(-2);
                            return hours + "h" + minutes + "min";
                        }

                        break;

                    case 'meters_2_kilometers':
                        var kilometers = Math.floor(value / 1000);
                        var meters = Math.floor(value % 1000);

                        if(kilometers == 0) {
                            return meters + "m";
                        } else {
                            if(meters == 0) {
                                return kilometers + "km";
                            } else {
                                meters = ("00" + meters).slice(-3);
                                return kilometers + "km" + meters;
                            }
                        }
                        break;

                    default:
                        return value.toString();
                }
            }
        }
    };

})(jQuery, Drupal, this, this.document);