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
                            valueDisplay = valueDisplay.replace('#1', ui.value.toString().replace('.', ','));
                        } else {
                            valueDisplay = valueDisplay.replace('#1', ui.values[0].toString().replace('.', ','));
                            valueDisplay = valueDisplay.replace('#2', ui.values[1].toString().replace('.', ','));
                        }

                        $(this).siblings('div.value_display').text(valueDisplay);
                    }
                };

                var options = $.extend({}, hardOptions, widgetOptions);

                $(this).slider(options);

                if(options.display_limits == true) {
                    $(this).after("<div class='slider_limit slider_min'>" + options.min + options.unit + "</div>");
                    $(this).after("<div class='slider_limit slider_max'>" + options.max + options.unit + "</div>");
                }
            });
        }
    };

})(jQuery, Drupal, this, this.document);