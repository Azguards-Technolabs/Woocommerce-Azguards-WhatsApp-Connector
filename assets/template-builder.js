jQuery(document).ready(function ($) {
    if ($('.wa-template-wrap').length === 0) {
        return;
    }

    function updateLivePreview(container) {
        var headerText = container.find('.wa-bind-header').val() || '';
        var footerText = container.find('.wa-bind-footer').val() || '';

        container.find('.wa-preview-header').text(headerText);
        container.find('.wa-preview-footer').text(footerText);

        // Update Body
        var bodyText = container.find('.wa-bind-body').val() || '';
        var hookType = container.find('.wa_current_hook').val() || 'order_shipment';

        // Demo value replacements
        bodyText = bodyText.replace(/\{\{customer_firstname\}\}/g, 'Zubair');
        bodyText = bodyText.replace(/\{\{order\.customer_firstname\}\}/g, 'Zubair');
        bodyText = bodyText.replace(/\{\{increment_id\}\}/g, '10001');
        bodyText = bodyText.replace(/\{\{order\.increment_id\}\}/g, '10001');

        // Hook Specifics
        if (hookType === 'order_shipment') {
            bodyText = bodyText.replace(/\{\{tracking_number\}\}/g, 'CA-123456');
            bodyText = bodyText.replace(/\{\{shipment\.tracking_number\}\}/g, 'CA-123456');
            bodyText = bodyText.replace(/\{\{carrier_name\}\}/g, 'FedEx');
            bodyText = bodyText.replace(/\{\{shipment\.carrier_name\}\}/g, 'FedEx');
        } else if (hookType === 'order_created') {
            bodyText = bodyText.replace(/\{\{order\.grand_total\}\}/g, '$150.00');
            // Mock Item loop parsing
            bodyText = bodyText.replace(/\{\{#items\}\}([\s\S]*?)\{\{\/items\}\}/g, function (match, p1) {
                var item1 = p1.replace(/\{\{items\.name\}\}/g, 'Classic T-Shirt').replace(/\{\{items\.qty_ordered\}\}/g, '1').replace(/\{\{items\.row_total\}\}/g, '$50.00');
                var item2 = p1.replace(/\{\{items\.name\}\}/g, 'Blue Jeans').replace(/\{\{items\.qty_ordered\}\}/g, '2').replace(/\{\{items\.row_total\}\}/g, '$100.00');
                return item1 + '\n' + item2;
            });
        } else if (hookType === 'order_invoice') {
            bodyText = bodyText.replace(/\{\{invoice\.increment_id\}\}/g, 'INV-10001');
            bodyText = bodyText.replace(/\{\{invoice\.grand_total\}\}/g, '$150.00');
        } else if (hookType === 'order_creditmemo') {
            bodyText = bodyText.replace(/\{\{creditmemo\.increment_id\}\}/g, 'CM-10001');
            bodyText = bodyText.replace(/\{\{creditmemo\.grand_total\}\}/g, '$50.00');
        }

        // bold markers * * -> <b> </b>
        bodyText = bodyText.replace(/\*(.*?)\*/g, '<b>$1</b>');
        // Attach events
        $('#wa_header_text, #wa_message_body, #wa_footer_text').on('input', updatePreview);
        $('#wa_enable_buttons').on('change', updatePreview);

        $(document).on('input', '.wa-button-text', updatePreview);
        $(document).on('change', '.wa-button-type', updatePreview);

        // Add button logic
        $('#wa_add_button').on('click', function () {
            var numButtons = $('.wa-button-item').length;
            if (numButtons < 3) {
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
                alert('You must have at least one button if Enable Buttons is Yes.');
            }
        });

        $('#wa_save_template').on('click', function () {
            $(this).text('Saving...');
            setTimeout(() => { $(this).text('Save Template'); alert("Template configuration saved locally!"); }, 500);
        });

        // Initial render
        updatePreview();
    });
