jQuery(document).ready(function ($) {
    if ($('.wa-template-wrap').length === 0) {
        return;
    }

    function updatePreview() {
        var container = $('.wa-template-wrap');
        var hookType = $('#wa_current_hook').val() || 'order_shipment';

        var headerType = $('#wa_header_type').val() || 'TEXT';
        var headerText = $('#wa_header_text').val() || '';
        var footerText = $('#wa_footer_text').val() || '';
        var bodyText = $('#wa_message_body').val() || '';

        $('#preview_header').text(headerText);
        $('#preview_footer').text(footerText);

        // Demo value replacements
        bodyText = bodyText.replace(/\{\{customer_firstname\}\}/g, 'Zubair');
        bodyText = bodyText.replace(/\{\{order\.customer_firstname\}\}/g, 'Zubair');
        bodyText = bodyText.replace(/\{\{increment_id\}\}/g, '10001');
        bodyText = bodyText.replace(/\{\{order\.increment_id\}\}/g, '10001');

        if (hookType === 'order_shipment') {
            bodyText = bodyText.replace(/\{\{tracking_number\}\}/g, 'CA-123456');
            bodyText = bodyText.replace(/\{\{carrier_name\}\}/g, 'FedEx');
        } else if (hookType === 'order_created') {
            bodyText = bodyText.replace(/\{\{order\.grand_total\}\}/g, '$150.00');
            bodyText = bodyText.replace(/\{\{#items\}\}([\s\S]*?)\{\{\/items\}\}/g, function (match, p1) {
                var item1 = p1.replace(/\{\{items\.name\}\}/g, 'Classic T-Shirt').replace(/\{\{items\.qty_ordered\}\}/g, '1').replace(/\{\{items\.row_total\}\}/g, '$50.00');
                var item2 = p1.replace(/\{\{items\.name\}\}/g, 'Blue Jeans').replace(/\{\{items\.qty_ordered\}\}/g, '2').replace(/\{\{items\.row_total\}\}/g, '$100.00');
                return item1 + '\n' + item2;
            });
        }

        bodyText = bodyText.replace(/\*(.*?)\*/g, '<b>$1</b>');
        bodyText = bodyText.replace(/\n/g, '<br>');
        $('#preview_body').html(bodyText);

        // Buttons preview
        var buttonsHtml = '';
        if ($('#wa_enable_buttons').is(':checked')) {
            $('.wa-button-item').each(function () {
                var btnText = $(this).find('.wa-button-text').val() || 'Button';
                var btnType = $(this).find('.wa-button-type').val();
                var icon = btnType === 'URL' ? '&#128279;' : '&#10149;';
                buttonsHtml += '<div class="wa-preview-btn"><span class="wa-btn-icon">' + icon + '</span> ' + btnText + '</div>';
            });
        }
        $('#preview_buttons').html(buttonsHtml);
    }

    $('#wa_header_text, #wa_message_body, #wa_footer_text').on('input', updatePreview);
    $('#wa_enable_buttons').on('change', updatePreview);
    $(document).on('input', '.wa-button-text', updatePreview);
    $(document).on('change', '.wa-button-type', updatePreview);

    $('#wa_add_button').on('click', function () {
        if ($('.wa-button-item').length < 3) {
            var clone = $('.wa-button-item').first().clone();
            clone.find('input').val('');
            $('#wa_buttons_container').append(clone);
            updatePreview();
        } else {
            alert('Maximum 3 buttons allowed.');
        }
    });

    $(document).on('click', '.wa-remove-button', function () {
        if ($('.wa-button-item').length > 1) {
            $(this).closest('.wa-button-item').remove();
            updatePreview();
        } else {
            alert('At least one button is required if Enabled.');
        }
    });

    $('#wa_save_template').on('click', function () {
        var $btn = $(this);
        $btn.text('Saving...').prop('disabled', true);

        var buttonsData = [];
        $('.wa-button-item').each(function () {
            buttonsData.push({
                type: $(this).find('.wa-button-type').val(),
                text: $(this).find('.wa-button-text').val(),
                url: $(this).find('.wa-button-url').val()
            });
        });

        $.post(ajaxurl, {
            action: 'wa_save_template_config',
            hook_type: $('#wa_current_hook').val(),
            header_type: $('#wa_header_type').val(),
            header_text: $('#wa_header_text').val(),
            message_body: $('#wa_message_body').val(),
            footer_text: $('#wa_footer_text').val(),
            enable_buttons: $('#wa_enable_buttons').is(':checked') ? 'yes' : 'no',
            buttons_data: JSON.stringify(buttonsData)
        }, function (response) {
            $btn.text('Save Template').prop('disabled', false);
            if (response.success) {
                alert(response.data.message);
            } else {
                alert('Error: ' + response.data.message);
            }
        }).fail(function () {
            $btn.text('Save Template').prop('disabled', false);
            alert('Connection error');
        });
    });

    updatePreview();
});
