jQuery(document).ready(function ($) {
    if ($('.wa-template-wrap').length === 0) {
        return;
    }

    function updatePreview() {
        var headerText = $('#wa_header_text').val() || '';
        var footerText = $('#wa_footer_text').val() || '';
        var bodyText = $('#wa_message_body').val() || '';
        var hookType = $('#wa_current_hook').val() || 'order_shipment';

        $('#preview_header').text(headerText);
        $('#preview_footer').text(footerText);

        // Demo value replacements
        bodyText = bodyText.replace(/\{\{customer_firstname\}\}/g, 'Zubair');
        bodyText = bodyText.replace(/\{\{order\.customer_firstname\}\}/g, 'Zubair');
        bodyText = bodyText.replace(/\{\{increment_id\}\}/g, '10001');
        bodyText = bodyText.replace(/\{\{order\.increment_id\}\}/g, '10001');

        if (hookType === 'order_shipment') {
            bodyText = bodyText.replace(/\{\{tracking_number\}\}/g, 'CA-123456');
            bodyText = bodyText.replace(/\{\{shipment\.tracking_number\}\}/g, 'CA-123456');
            bodyText = bodyText.replace(/\{\{carrier_name\}\}/g, 'FedEx');
            bodyText = bodyText.replace(/\{\{shipment\.carrier_name\}\}/g, 'FedEx');
        } else if (hookType === 'order_created') {
            bodyText = bodyText.replace(/\{\{order\.grand_total\}\}/g, '$150.00');
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

        bodyText = bodyText.replace(/\*(.*?)\*/g, '<b>$1</b>');
        bodyText = bodyText.replace(/\n/g, '<br>');
        $('#preview_body').html(bodyText);

        // Buttons
        if ($('#wa_enable_buttons').is(':checked')) {
            var buttonsHTML = '';
            $('.wa-button-item').each(function () {
                var btnText = $(this).find('.wa-button-text').val() || 'Button';
                buttonsHTML += '<div class="wa-preview-btn"><span class="wa-btn-icon">&#128279;</span> ' + btnText + '</div>';
            });
            $('#preview_buttons').html(buttonsHTML).show();
        } else {
            $('#preview_buttons').hide();
        }
    }

    $('#wa_header_text, #wa_message_body, #wa_footer_text').on('input', updatePreview);
    $('#wa_enable_buttons').on('change', function() {
        if ($(this).is(':checked')) {
            $('#wa_buttons_row').show();
        } else {
            $('#wa_buttons_row').hide();
        }
        updatePreview();
    });

    $('#wa_template_type').on('change', function () {
        var type = $(this).val();
        if (type === 'CAROUSEL') {
            $('.wa-standard-row').hide();
            $('#wa_carousel_row').show();
            // Load from RAW_CAROUSEL_CARDS if empty
            if ($('.wa-carousel-card').length === 0) {
                if (typeof RAW_CAROUSEL_CARDS !== 'undefined' && Array.isArray(RAW_CAROUSEL_CARDS) && RAW_CAROUSEL_CARDS.length > 0) {
                    RAW_CAROUSEL_CARDS.forEach(function (cardData) {
                        addCard(cardData);
                    });
                } else {
                    addCard();
                }
            }
        } else {
            $('.wa-standard-row').show();
            $('#wa_carousel_row').hide();

            // Auto-set header type and show correct row based on template type
            if (type === 'TEXT') {
                $('#wa_header_type').val('TEXT').trigger('change');
            } else if (type === 'IMAGE' || type === 'VIDEO' || type === 'DOCUMENT') {
                $('#wa_header_type').val(type).trigger('change');
            }

            if ($('#wa_enable_buttons').length) {
                $('#wa_enable_buttons').trigger('change');
            }
        }
        updatePreview();
    }).trigger('change');

    $('#wa_header_type').on('change', function () {
        var type = $(this).val();
        if (type === 'TEXT') {
            $('#wa_header_text_row').show();
            $('#wa_header_media_row').hide();
        } else if (type === 'NONE') {
            $('#wa_header_text_row').hide();
            $('#wa_header_media_row').hide();
        } else {
            $('#wa_header_text_row').hide();
            $('#wa_header_media_row').show();
        }
        updatePreview();
    }).trigger('change');

    var custom_uploader;
    $('#wa_upload_media_btn').on('click', function (e) {
        e.preventDefault();

        var headerType = $('#wa_header_type').val();
        var mimeTypes = {
            'IMAGE': ['image/jpeg', 'image/png'],
            'VIDEO': ['video/mp4', 'video/3gpp'],
            'DOCUMENT': ['application/pdf', 'application/msword', 'application/vnd.mspowerpoint', 'application/vnd.msexcel', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']
        };

        if (custom_uploader) {
            custom_uploader.open();
            return;
        }

        custom_uploader = wp.media({
            title: 'Choose Template Media',
            button: { text: 'Select Media' },
            library: { type: mimeTypes[headerType] || mimeTypes['IMAGE'] },
            multiple: false
        });

        custom_uploader.on('select', function () {
            var attachment = custom_uploader.state().get('selection').first().toJSON();
            $('#wa_media_status').text('Uploading to Meta...').css('color', 'orange');
            $('#wa_upload_media_btn').prop('disabled', true);

            var data = {
                'action': 'wa_upload_template_media',
                'attachment_id': attachment.id
            };

            $.post(ajaxurl, data, function (response) {
                if (response.success) {
                    $('#wa_header_media_handle').val(response.data.document_id);
                    $('#wa_header_media_url').val(response.data.preview_link);
                    $('#wa_media_status').text('Upload Complete ✅').css('color', 'green');
                    $('#wa_media_preview').html('<a href="' + response.data.preview_link + '" target="_blank">Preview Uploaded File</a>');
                } else {
                    $('#wa_media_status').text('Failed: ' + response.data).css('color', 'red');
                }
            }).fail(function () {
                $('#wa_media_status').text('Server error during upload.').css('color', 'red');
            }).always(function () {
                $('#wa_upload_media_btn').prop('disabled', false);
            });
        });

        custom_uploader.open();
    });

    $(document).on('input', '.wa-button-text', updatePreview);
    $(document).on('change', '.wa-button-type', updatePreview);

    $('#wa_add_button').on('click', function () {
        var numButtons = $('.wa-button-item:visible').length;
        if (numButtons < 3) {
            var $template = $('.wa-button-item').last();
            var clone = $template.clone();
            clone.find('input').val('');
            clone.find('select').prop('selectedIndex', 0);
            clone.css('display', 'flex');
            $('#wa_buttons_container').append(clone);
            updatePreview();
        } else {
            alert('Maximum 3 buttons allowed.');
        }
    });

    $(document).on('click', '.wa-remove-button', function () {
        var $visibleBtns = $('.wa-button-item:visible');
        if ($visibleBtns.length > 1) {
            $(this).closest('.wa-button-item').remove();
            updatePreview();
        } else {
            // Hide the last one instead of removing (preserve template)
            $(this).closest('.wa-button-item').hide();
            $(this).closest('.wa-button-item').find('input').val('');
            updatePreview();
        }
    });

    // CAROUSEL LOGIC
    function addCard(data = null) {
        var numCards = $('.wa-carousel-card').length;
        if (numCards >= 10) {
            alert('Maximum 10 cards allowed.');
            return;
        }
        var tmpl = $('#wa-carousel-card-template').html();
        var $card = $(tmpl);
        $card.find('.card-num').text(numCards + 1);

        if (data) {
            $card.find('.wa-card-header-type').val(data.header_type || 'IMAGE');
            $card.find('.wa-card-header-handle').val(data.header_handle || '');
            $card.find('.wa-card-header-url').val(data.header_image || '');
            if (data.header_handle || data.header_image) {
                var previewUrl = data.header_image || '#';
                $card.find('.wa-card-media-status').html('<a href="' + previewUrl + '" target="_blank">View File ✅</a>').css('color', 'green');
            }
            $card.find('.wa-card-body').val(data.body || '');

            if (data.buttons && Array.isArray(data.buttons)) {
                data.buttons.forEach(function (btn) {
                    var btnHtml = `
                        <div class="wa-card-button-item" style="margin-bottom: 5px; display: flex; gap: 5px;">
                            <select class="wa-card-btn-type">
                                <option value="URL" ${btn.type === 'URL' ? 'selected' : ''}>URL (Website Link)</option>
                                <option value="QUICK_REPLY" ${btn.type === 'QUICK_REPLY' ? 'selected' : ''}>Quick Reply</option>
                                <option value="PHONE_NUMBER" ${btn.type === 'PHONE_NUMBER' ? 'selected' : ''}>Phone Number (Call Button)</option>
                                <option value="COPY_CODE" ${btn.type === 'COPY_CODE' ? 'selected' : ''}>Copy Coupon Code</option>
                            </select>
                            <input type="text" class="wa-card-btn-text" value="${btn.text || ''}" placeholder="Text" style="width: 100px;">
                            <input type="text" class="wa-card-btn-val" value="${btn.button_url || btn.phone_number || ''}" placeholder="URL/Phone" style="width: 120px;">
                            <button type="button" class="button wa-card-remove-button">X</button>
                        </div>
                    `;
                    $card.find('.wa-card-buttons-container').append(btnHtml);
                });
            }
        }

        $('#wa_carousel_cards_container').append($card);
    }

    function renumberCards() {
        $('.wa-carousel-card').each(function (idx) {
            $(this).find('.card-num').text(idx + 1);
        });
    }

    $('#wa_add_card_btn').on('click', function () { addCard(); });

    $(document).on('click', '.wa-remove-card', function () {
        if ($('.wa-carousel-card').length > 1) {
            $(this).closest('.wa-carousel-card').remove();
            renumberCards();
        } else {
            alert('You must have at least one card in a Carousel Template.');
        }
    });

    $(document).on('click', '.wa-card-add-button', function () {
        var $container = $(this).closest('td').find('.wa-card-buttons-container');
        var btnCount = $container.find('.wa-card-button-item').length;
        if (btnCount >= 2) {
            alert('Maximum 2 buttons per Carousel card allowed.');
            return;
        }
        var btnHtml = `
            <div class="wa-card-button-item" style="margin-bottom: 5px; display: flex; gap: 5px;">
                <select class="wa-card-btn-type">
                    <option value="URL">URL (Website Link)</option>
                    <option value="QUICK_REPLY">Quick Reply</option>
                    <option value="PHONE_NUMBER">Phone Number (Call Button)</option>
                    <option value="COPY_CODE">Copy Coupon Code</option>
                </select>
                <input type="text" class="wa-card-btn-text" placeholder="Text" style="width: 100px;">
                <input type="text" class="wa-card-btn-val" placeholder="URL/Phone" style="width: 120px;">
                <button type="button" class="button wa-card-remove-button">X</button>
            </div>
        `;
        $container.append(btnHtml);
    });

    $(document).on('click', '.wa-card-remove-button', function () {
        $(this).closest('.wa-card-button-item').remove();
    });

    // Card Media Upload logic
    $(document).on('click', '.wa-card-upload-media', function (e) {
        e.preventDefault();
        var $btn = $(this);
        var $card = $btn.closest('.wa-carousel-card');
        var headerType = $card.find('.wa-card-header-type').val();

        var uploader = wp.media({
            title: 'Choose Card Media',
            button: { text: 'Select Media' },
            library: { type: headerType === 'VIDEO' ? ['video/mp4'] : ['image/jpeg', 'image/png'] },
            multiple: false
        });

        uploader.on('select', function () {
            var attachment = uploader.state().get('selection').first().toJSON();
            $card.find('.wa-card-media-status').text('Uploading...').css('color', 'orange');
            $btn.prop('disabled', true);

            $.post(ajaxurl, {
                'action': 'wa_upload_template_media',
                'attachment_id': attachment.id
            }, function (response) {
                if (response.success) {
                    $card.find('.wa-card-header-handle').val(response.data.document_id);
                    $card.find('.wa-card-header-url').val(response.data.preview_link);
                    $card.find('.wa-card-media-status').html('<a href="' + response.data.preview_link + '" target="_blank">View File ✅</a>').css('color', 'green');
                } else {
                    $card.find('.wa-card-media-status').text('Failed').css('color', 'red');
                }
            }).always(function () {
                $btn.prop('disabled', false);
            });
        });

        uploader.open();
    });

    // Variable Popup Logic
    $('#wa_insert_variable').on('click', function (e) {
        e.stopPropagation();
        $('#wa_variable_menu').toggle();
    });

    $(document).on('click', function (e) {
        if (!$(e.target).closest('.wa-variable-inserter').length) {
            $('#wa_variable_menu').hide();
        }
    });

    $('.wa-premium-variable-menu').on('click', function (e) {
        e.stopPropagation();
    });

    // Tab switching
    $('.wa-var-tab-btn').on('click', function () {
        var targetId = $(this).data('target');
        $('.wa-var-tab-btn').removeClass('active');
        $('.wa-var-panel').removeClass('active');

        $(this).addClass('active');
        $('#' + targetId).addClass('active');
    });

    // Insert variable
    $('.wa-var-item').on('click', function (e) {
        e.preventDefault();
        var varString = $(this).data('val');

        var textarea = $('#wa_message_body')[0];
        var start = textarea.selectionStart;
        var end = textarea.selectionEnd;

        textarea.value = textarea.value.substring(0, start) + varString + textarea.value.substring(end);
        textarea.selectionStart = textarea.selectionEnd = start + varString.length;
        textarea.focus();

        $('#wa_variable_menu').hide();
        updatePreview();
    });

    $('#wa_save_template').on('click', function () {
        var $btn = $(this);
        $btn.text('Saving...').prop('disabled', true);

        var templateData = {
            action: 'wa_save_builder_template',
            security: $('#wa_save_template_nonce').val(),
            is_standalone: 'yes',
            entity_id: $('#wa_edit_entity_id').val() || 0,
            hook: $('#wa_current_hook').val() || 'marketing',
            template_name: $('#wa_template_name').val() || 'Unnamed',
            template_type: $('#wa_template_type').val() || 'STANDARD',
            header_type: $('#wa_header_type').val(),
            header_text: $('#wa_header_text').val(),
            header_handle: $('#wa_header_media_handle').val(),
            header_url: $('#wa_header_media_url').val(),
            body_template: $('#wa_message_body').val(),
            footer_template: $('#wa_footer_text').val(),
            enable_buttons: $('#wa_enable_buttons').is(':checked') ? 'yes' : 'no',
            buttons: []
        };

        if (templateData.template_type === 'STANDARD') {
            $('.wa-button-item').each(function () {
                templateData.buttons.push({
                    type: $(this).find('.wa-button-type').val(),
                    text: $(this).find('.wa-button-text').val(),
                    url: $(this).find('.wa-button-url').val()
                });
            });
        } else {
            // Parse CAROUSEL cards
            templateData.carousel_cards = [];
            $('.wa-carousel-card').each(function () {
                var $card = $(this);
                var cardObj = {
                    header_type: $card.find('.wa-card-header-type').val(),
                    header_handle: $card.find('.wa-card-header-handle').val(),
                    header_image: $card.find('.wa-card-header-url').val(),
                    body: $card.find('.wa-card-body').val(),
                    buttons: []
                };

                $card.find('.wa-card-button-item').each(function () {
                    cardObj.buttons.push({
                        type: $(this).find('.wa-card-btn-type').val(),
                        text: $(this).find('.wa-card-btn-text').val(),
                        button_url: $(this).find('.wa-card-btn-type').val() === 'URL' ? $(this).find('.wa-card-btn-val').val() : '',
                        phone_number: $(this).find('.wa-card-btn-type').val() === 'PHONE_NUMBER' ? $(this).find('.wa-card-btn-val').val() : ''
                    });
                });

                templateData.carousel_cards.push(cardObj);
            });
            templateData.carousel_cards = JSON.stringify(templateData.carousel_cards);
        }

        $.post(ajaxurl, templateData, function (response) {
            if (response.success) {
                var msg = response.data.message || 'Template saved successfully!';
                var apiIcon = response.data.api_synced ? '✅ ' : '⚠️ ';
                alert(apiIcon + msg);
                if (response.data.api_synced) {
                    // Refresh the page after a brief delay so the grid picks up the new template
                    setTimeout(function () { window.location.reload(); }, 1500);
                }
            } else {
                alert('❌ Error: ' + (response.data || 'Unknown error'));
            }
        }).fail(function () {
            alert('Server error while saving template.');
        }).always(function () {
            $btn.text('Save Template').prop('disabled', false);
        });
    });

    // Ensure correct initial state for buttons
    if ($('#wa_enable_buttons').length) {
        if ($('#wa_enable_buttons').is(':checked')) {
            $('#wa_buttons_row').show();
        } else {
            $('#wa_buttons_row').hide();
        }
    }

    // Initial render
    updatePreview();
});
