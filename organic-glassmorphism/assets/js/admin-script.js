jQuery(document).ready(function($){
    // Initialize the WordPress color picker on our input fields.
    $('.og-color-picker').wpColorPicker();

    // Handle preset selection changes.
    $('input.og-preset-radio').on('change', function() {
        const presetName = $(this).data('preset');

        // Check if the preset data is available.
        if ( window.ogPresetData && window.ogPresetData[presetName] ) {
            const presetSettings = window.ogPresetData[presetName];

            // Loop through the settings for the selected preset.
            for ( const settingId in presetSettings ) {
                const value = presetSettings[settingId];
                const $field = $('#' + settingId);

                if ( $field.length ) {
                    // Update the field's value.
                    $field.val(value);

                    // If it's a color picker, we also need to programmatically update its color.
                    if ( $field.hasClass('og-color-picker') ) {
                        $field.iris('color', value);
                    }
                }
            }
        }
    });
});
