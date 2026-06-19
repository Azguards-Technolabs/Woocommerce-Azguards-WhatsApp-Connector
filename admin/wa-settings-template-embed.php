<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$hook_type = isset($_GET['hook']) ? sanitize_text_field($_GET['hook']) : 'order_shipment';
$unique_suffix = '_' . $hook_type; // Create unique suffix for HTML scoping

wp_enqueue_style( 'wa-template-builder-css', plugins_url( '../assets/template-builder.css', __FILE__ ) );
wp_enqueue_script( 'wa-template-builder-js', plugins_url( '../assets/template-builder.js', __FILE__ ), array( 'jquery' ), null, true );

// Load saved values or defaults
$defaults = WA_CONNECTOR_DEFAULTS['templates'][$hook_type] ?? [];

$template_name   = get_option("wa_template_{$hook_type}_template_name", $defaults['template_name'] ?? '');
$category        = get_option("wa_template_{$hook_type}_category", $defaults['category'] ?? 'Utility');
$language        = get_option("wa_template_{$hook_type}_language", $defaults['language'] ?? 'en_US');
$header_type     = get_option("wa_template_{$hook_type}_header_type", $defaults['header_type'] ?? 'text');
$header_text     = get_option("wa_template_{$hook_type}_header_text", $defaults['header_text'] ?? '');
$body_template   = get_option("wa_template_{$hook_type}_body_template", $defaults['body_template'] ?? '');
$footer_template = get_option("wa_template_{$hook_type}_footer_template", $defaults['footer_template'] ?? '');
$buttons_json    = get_option("wa_template_{$hook_type}_buttons_json", $defaults['buttons_json'] ?? '[]');

$buttons = json_decode($buttons_json, true) ?: [];

// --- Dynamic Status from DB ---
global $wpdb;
$_tbl_tmpl   = $wpdb->prefix . 'azguards_whatsapp_templates';
$_tmpl_row   = ! empty( $template_name )
    ? $wpdb->get_row( $wpdb->prepare( "SELECT status FROM {$_tbl_tmpl} WHERE template_name = %s ORDER BY entity_id DESC LIMIT 1", $template_name ) )
    : null;
$_tmpl_status = $_tmpl_row ? strtoupper( $_tmpl_row->status ) : 'NOT CREATED';
$_status_colors = [
    'APPROVED'    => '#1a7f64',
    'PENDING'     => '#b28900',
    'REJECTED'    => '#c53030',
    'LOCAL'       => '#666',
    'NOT CREATED' => '#aaa',
];
$_status_color = $_status_colors[ $_tmpl_status ] ?? '#666';
?>

<div class="wa-template-wrap" style="padding:0; margin:0;" data-hook="<?php echo esc_attr($hook_type); ?>">
    <input type="hidden" class="wa_current_hook" value="<?php echo esc_attr($hook_type); ?>">
    <input type="hidden" id="wa_save_template_nonce_<?php echo esc_attr($hook_type); ?>" value="<?php echo wp_create_nonce( 'wa_save_builder_template' ); ?>">

    <div class="wa-builder-container" id="wa-builder-<?php echo esc_attr($hook_type); ?>" style="display:flex; gap:20px;">
        <!-- Left Side: Form Elements -->
        <div class="wa-builder-form" style="flex:1;">
            <div style="display:flex; justify-content:space-between; margin-bottom:10px;">
                <label><strong>Template Builder</strong></label>
                <span style="color:<?php echo esc_attr($_status_color); ?>; font-size:11px; font-weight:600;">Meta Status: <?php echo esc_html($_tmpl_status); ?></span>
            </div>

            <div class="wa-form-row" style="margin-bottom:15px;">
                <label style="display:block; margin-bottom:5px;">Template Name</label>
                <input type="text" name="wa_template_<?php echo esc_attr($hook_type); ?>_template_name" value="<?php echo esc_attr($template_name); ?>" style="width:100%;">
                <span class="description" style="font-size:11px; display:block; margin-top:5px;">Unique template key for this event. Use lowercase letters, numbers, and underscores only.</span>
            </div>

            <div style="display:flex; gap:15px; margin-bottom:15px;">
                <div class="wa-form-row" style="flex:1;">
                    <label style="display:block; margin-bottom:5px;">Category</label>
                    <select name="wa_template_<?php echo esc_attr($hook_type); ?>_category" style="width:100%;">
                        <option value="Utility" <?php selected($category, 'Utility'); ?>>Utility</option>
                        <option value="Marketing" <?php selected($category, 'Marketing'); ?>>Marketing</option>
                        <option value="Authentication" <?php selected($category, 'Authentication'); ?>>Authentication</option>
                    </select>
                    <span class="description" style="font-size:11px; display:block; margin-top:5px;">Utility is best for order updates. Marketing is for promotional messages. Authentication is for OTP/login messages.</span>
                </div>
                <div class="wa-form-row" style="flex:1;">
                    <label style="display:block; margin-bottom:5px;">Language</label>
                    <input type="text" name="wa_template_<?php echo esc_attr($hook_type); ?>_language" value="<?php echo esc_attr(get_locale()); ?>" style="width:100%; background:#eee;" readonly>
                    <span class="description" style="font-size:11px; display:block; margin-top:5px;">Read-only WordPress locale used for this automatic template.</span>
                </div>
            </div>

            <div class="wa-form-row" style="margin-bottom:15px;">
                <label style="display:block; margin-bottom:5px;">Header Type</label>
                <select name="wa_template_<?php echo esc_attr($hook_type); ?>_header_type" class="regular-text" style="width:100%;">
                    <option value="text" <?php selected($header_type, 'text'); ?>>Text</option>
                </select>
                <span class="description" style="font-size:11px; display:block; margin-top:5px;">Automatic event templates currently use a text header.</span>
            </div>

            <div class="wa-form-row" style="margin-bottom:15px;">
                <label style="display:block; margin-bottom:5px;">Header Text</label>
                <textarea name="wa_template_<?php echo esc_attr($hook_type); ?>_header_text" class="wa-bind-header" rows="2" style="width:100%;"><?php echo esc_textarea($header_text); ?></textarea>
                <span class="description" style="font-size:11px; display:block; margin-top:5px;">Short title shown before the message body, for example Order Confirmed or Shipment Update.</span>
            </div>

            <div class="wa-form-row" style="margin-bottom:15px;">
                <div class="wa-message-toolbar">
                    <label>Message Body</label>
                    <?php
                    require_once dirname(__FILE__) . '/../includes/wc-woocommerce-variables.php';
                    $embed_hook_map = [
                        'order_created'      => 'wa_order_creation_table_data',
                        'order_shipment'     => 'wa_order_shipment_table_data',
                        'order_invoice'      => 'wa_order_invoice_table_data',
                        'order_creditmemo'   => 'wa_order_credit_memo_table_data',
                        'order_cancellation' => 'wa_order_creation_table_data',
                        'abandon_cart'       => 'wa_abandon_cart_table_data',
                        'marketing'          => 'wa_marketing_campaign_table_data',
                    ];
                    $embed_hook_key = $embed_hook_map[ $hook_type ] ?? 'wa_order_creation_table_data';
                    $embed_vars     = WA_WoocommerceOptions::get_woocommerce_options( $embed_hook_key );
                    $embed_groups   = [ 'Order' => [], 'Customer & Address' => [] ];
                    foreach ( $embed_vars as $k => $lbl ) {
                        if ( strpos($k,'billing_') === 0 || strpos($k,'shipping_') === 0 || strpos($k,'customer_') === 0 || in_array( $k, [ 'firstname', 'lastname', 'name', 'email', 'phone', 'mobileNumber', 'countryCode', 'businessName', 'website' ], true ) ) {
                            $embed_groups['Customer & Address'][$k] = $lbl;
                        } else {
                            $embed_groups['Order'][$k] = $lbl;
                        }
                    }
                    $vpid = 'wa_varpicker_' . esc_attr($hook_type);
                    ?>
                    <div class="wa-embed-var-control">
                        <button type="button" class="button button-small wa-embed-var-btn" data-picker="<?php echo $vpid; ?>" data-textarea="wa_template_<?php echo esc_attr($hook_type); ?>_body_template">
                            &#123;&#123; Insert Variable &#125;&#125;
                        </button>
                        <div id="<?php echo $vpid; ?>" class="wa-embed-var-popup" style="display:none; position:fixed; width:340px; background:#fff; border:1px solid #ddd; border-radius:8px; box-shadow:0 8px 24px rgba(0,0,0,.12); z-index:99999; font-weight:normal; text-align:left; overflow:hidden;">
                            <div style="display:flex; background:#f8f9fa; border-bottom:1px solid #eee;">
                                <?php $first = true; foreach ( $embed_groups as $gname => $gvars ) : ?>
                                <button type="button"
                                    class="wa-embed-tab-btn<?php echo $first ? ' wa-embed-tab-active' : ''; ?>"
                                    data-panel="<?php echo $vpid . '_' . sanitize_title($gname); ?>"
                                    style="flex:1; padding:10px; border:none; background:transparent; font-weight:600; font-size:12px; color:<?php echo $first?'#006bb4':'#555';?>; cursor:pointer; border-bottom:<?php echo $first?'2px solid #006bb4':'2px solid transparent';?>;"
                                ><?php echo esc_html($gname); ?></button>
                                <?php $first = false; endforeach; ?>
                            </div>
                            <div style="max-height:220px; overflow-y:auto; padding:8px;">
                                <?php $first = true; foreach ( $embed_groups as $gname => $gvars ) : ?>
                                <div id="<?php echo $vpid . '_' . sanitize_title($gname); ?>" class="wa-embed-var-panel" style="<?php echo $first?'':'display:none;';?>">
                                    <?php foreach ( $gvars as $k => $lbl ) : ?>
                                    <a href="#" class="wa-embed-var-item"
                                        data-val="{{<?php echo esc_attr($k); ?>}}"
                                        data-textarea="wa_template_<?php echo esc_attr($hook_type); ?>_body_template"
                                        style="display:flex; justify-content:space-between; align-items:center; padding:7px 10px; border-radius:5px; text-decoration:none; color:#333; font-size:12px; transition:background .15s;"
                                        onmouseover="this.style.background='#f0f8ff'" onmouseout="this.style.background=''">
                                        <span><?php echo esc_html($lbl); ?></span>
                                        <span style="font-size:11px; color:#006bb4; background:#e6f2fc; padding:2px 5px; border-radius:4px; font-family:monospace;"><?php echo esc_html($k); ?></span>
                                    </a>
                                    <?php endforeach; ?>
                                </div>
                                <?php $first = false; endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <textarea name="wa_template_<?php echo esc_attr($hook_type); ?>_body_template"
                          id="wa_body_<?php echo esc_attr($hook_type); ?>"
                          class="wa-bind-body" rows="6" style="width:100%;"><?php echo esc_textarea($body_template); ?></textarea>
                <span class="description" style="font-size:11px; display:block; margin-top:5px;">Main customer message for this event. Place the cursor in the text area, then use Insert Variable to add supported placeholders.</span>
            </div>

            <div class="wa-form-row" style="margin-bottom:15px;">
                <label style="display:block; margin-bottom:5px;">Footer</label>
                <textarea name="wa_template_<?php echo esc_attr($hook_type); ?>_footer_template" class="wa-bind-footer" rows="2" style="width:100%;"><?php echo esc_textarea($footer_template); ?></textarea>
                <span class="description" style="font-size:11px; display:block; margin-top:5px;">Optional small note below the message, such as store name, support note, or unsubscribe instruction.</span>
            </div>

            <div class="wa-form-row" style="margin-bottom:20px;">
                <label style="display:block; margin-bottom:5px;">Enable Buttons</label>
                <select class="wa-enable-buttons-select"><option>Yes</option></select>
                <span class="description" style="font-size:11px; display:block; margin-top:5px;">Buttons let customers open a URL, send a quick reply, or call a phone number from WhatsApp.</span>
                <div id="wa-buttons-container-<?php echo esc_attr($hook_type); ?>" class="wa-buttons-container">
                    <?php
                    $btn_types = ['URL' => 'URL Button', 'QUICK_REPLY' => 'Quick Reply', 'PHONE_NUMBER' => 'Phone Number'];
                    if (!empty($buttons)):
                        foreach ($buttons as $button):
                            $saved_type = strtoupper($button['type'] ?? 'URL');
                            $saved_url  = $button['url'] ?? $button['button_url'] ?? '';
                    ?>
                            <div class="wa-button-row">
                                <select class="wa-button-type">
                                    <?php foreach ($btn_types as $val => $label): ?>
                                        <option value="<?php echo esc_attr($val); ?>" <?php selected($saved_type, $val); ?>><?php echo esc_html($label); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="text" class="wa-button-text-field" value="<?php echo esc_attr($button['text'] ?? ''); ?>" placeholder="Button Text">
                                <input type="text" class="wa-button-target-field" value="<?php echo esc_attr($saved_url); ?>" placeholder="URL / Phone">
                                <button type="button" class="button wa-remove-btn" aria-label="Remove button">Remove</button>
                            </div>
                    <?php
                        endforeach;
                    else:
                    ?>
                        <div class="wa-button-row">
                            <select class="wa-button-type">
                                <?php foreach ($btn_types as $val => $label): ?>
                                    <option value="<?php echo esc_attr($val); ?>"><?php echo esc_html($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <input type="text" class="wa-button-text-field" placeholder="Button Text">
                            <input type="text" class="wa-button-target-field" placeholder="URL / Phone">
                            <button type="button" class="button wa-remove-btn" aria-label="Remove button">Remove</button>
                        </div>
                    <?php endif; ?>
                </div>
                <!-- Hidden input to store JSON for saving -->
                <input type="hidden" name="wa_template_<?php echo esc_attr($hook_type); ?>_buttons_json" value="<?php echo esc_attr($buttons_json); ?>">
            </div>

            <p class="submit" style="padding:0; margin-top:20px;">
                <button type="button" id="wa_save_settings_template_<?php echo esc_attr($hook_type); ?>"
                    class="button button-primary wa-save-settings-btn"
                    data-hook="<?php echo esc_attr($hook_type); ?>"
                    style="background:#ea5c0b; border:none; cursor:pointer;">
                    Save Template Settings
                </button>
                <span class="wa-settings-save-status" id="wa_settings_status_<?php echo esc_attr($hook_type); ?>" style="margin-left:10px;"></span>
            </p>
        </div>

        <!-- Right Side: iPhone Live Preview -->
        <div class="wa-live-preview" style="width:300px; flex-shrink:0;">
            <div style="border:1px solid #ddd; background:#f9f9f9; padding:15px; border-radius:30px; box-shadow:0 10px 20px rgba(0,0,0,0.1); height:500px;">
                <div style="background:#e5ddd5; height:100%; border-radius:15px; padding:10px; overflow-y:auto; font-family:Helvetica, sans-serif;">
                    <div style="background:#075e54; color:#fff; padding:10px; border-radius:10px 10px 0 0; font-weight:bold; text-align:center;">
                        WhatsApp Preview
                    </div>
                    <div style="background:#fff; margin-top:10px; padding:10px; border-radius:5px 5px 5px 0; box-shadow:0 1px 1px rgba(0,0,0,0.1);">
                        <strong class="wa-preview-header" style="display:block; margin-bottom:5px; font-size:14px;"><?php echo esc_html($header_text); ?></strong>
                        <p class="wa-preview-body" style="font-size:13px; margin:0 0 5px 0; line-height:1.4; white-space: pre-wrap;"><?php echo esc_html($body_template); ?></p>
                        <span class="wa-preview-footer" style="font-size:11px; color:#999; display:block; margin-bottom:10px;"><?php echo esc_html($footer_template); ?></span>
                        <div class="wa-preview-buttons" style="border-top:1px solid #eee; text-align:center; padding-top:8px;">
                            <?php foreach ($buttons as $button): ?>
                                <a href="#" style="color:#00a884; font-size:13px; text-decoration:none; display:flex; align-items:center; justify-content:center; margin-bottom:5px;">
                                    <span style="margin-right:5px;">🔗</span> <?php echo esc_html($button['text']); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(function($) {
    var hookType = '<?php echo esc_js($hook_type); ?>';
    var prefix   = 'wa_template_' + hookType + '_';

    // Register shared handlers once per page.
    if (!$(document).data('wa-handlers-init')) {
        $(document).data('wa-handlers-init', true);

        $(document).ready(function() {
            $('.wa-embed-var-popup').each(function() {
                $('body').append($(this).detach());
            });
        });

        $(document).on('click', '.wa-embed-var-btn', function(e) {
            e.stopPropagation();
            var $btn     = $(this);
            var pickerId = $btn.data('picker');
            var $popup   = $('#' + pickerId);

            $('.wa-embed-var-popup').not($popup).hide();

            if ($popup.is(':visible')) {
                $popup.hide();
                return;
            }

            var rect = $btn[0].getBoundingClientRect();
            $popup.css({
                position: 'fixed',
                top: rect.bottom + 4,
                left: Math.max(4, rect.right - 340),
                zIndex: 999999
            }).show();
        });

        $(window).on('scroll resize', function() {
            $('.wa-embed-var-popup:visible').each(function() {
                var pid  = $(this).attr('id');
                var $btn = $('[data-picker="' + pid + '"]');
                if ($btn.length) {
                    var rect = $btn[0].getBoundingClientRect();
                    $(this).css({ top: rect.bottom + 4, left: Math.max(4, rect.right - 340) });
                }
            });
        });

        $(document).on('mousedown', function(e) {
            if (!$(e.target).closest('.wa-embed-var-btn, .wa-embed-var-popup').length) {
                $('.wa-embed-var-popup').hide();
            }
        });

        $(document).on('mousedown', '.wa-embed-var-popup', function(e) {
            e.stopPropagation();
        });

        $(document).on('click', '.wa-embed-tab-btn', function(e) {
            e.stopPropagation();
            var $popup  = $(this).closest('.wa-embed-var-popup');
            var panelId = $(this).data('panel');
            $popup.find('.wa-embed-tab-btn').css({ color:'#555', borderBottom:'2px solid transparent', background:'transparent' });
            $popup.find('.wa-embed-var-panel').hide();
            $(this).css({ color:'#006bb4', borderBottom:'2px solid #006bb4' });
            $('#' + panelId).show();
        });

        $(document).on('click', '.wa-embed-var-item', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var varStr = $(this).data('val');
            var taName = $(this).data('textarea');
            var $popup = $(this).closest('.wa-embed-var-popup');
            var $ta    = $('textarea[name="' + taName + '"]');
            if (!$ta.length) return;
            var el    = $ta[0];
            var start = el.selectionStart;
            var end   = el.selectionEnd;
            el.value  = el.value.substring(0, start) + varStr + el.value.substring(end);
            el.selectionStart = el.selectionEnd = start + varStr.length;
            el.focus();
            var previewBody = $ta.closest('.wa-template-wrap').find('.wa-preview-body');
            if (previewBody.length) previewBody.text(el.value);
            $popup.hide();
        });

        // Remove button row
        $(document).on('click', '.wa-remove-btn', function() {
            $(this).closest('.wa-button-row').remove();
        });
    }

    $('#wa_save_settings_template_' + hookType).on('click', function() {
        var $btn    = $(this);
        var $status = $('#wa_settings_status_' + hookType);

        $btn.text('Saving...').prop('disabled', true);
        $status.text('').css('color', '');

        // Collect button rows
        var buttons = [];
        $('#wa-buttons-container-' + hookType + ' .wa-button-row').each(function() {
            var selects = $(this).find('select');
            var inputs  = $(this).find('input[type="text"]');
            buttons.push({
                type: selects.eq(0).val() || 'URL',
                text: inputs.eq(0).val() || '',
                url:  inputs.eq(1).val() || ''
            });
        });

        var postData = {
            action:         'wa_save_builder_template',
            security:       $('#wa_save_template_nonce_' + hookType).val(),
            hook:           hookType,
            template_name:  $('input[name="' + prefix + 'template_name"]').val(),
            category:       $('select[name="' + prefix + 'category"]').val(),
            language:       $('input[name="' + prefix + 'language"]').val(),
            header_type:    $('select[name="' + prefix + 'header_type"]').val(),
            header_text:    $('textarea[name="' + prefix + 'header_text"]').val(),
            body_template:  $('textarea[name="' + prefix + 'body_template"]').val(),
            footer_template:$('textarea[name="' + prefix + 'footer_template"]').val(),
            enable_buttons: 'yes',
            buttons:        buttons
        };

        $.post(ajaxurl, postData, function(resp) {
            if (resp.success) {
                var icon = resp.data.api_synced ? '✅' : '⚠️';
                $status.text(icon + ' ' + (resp.data.message || 'Saved!')).css('color', resp.data.api_synced ? '#1a7f64' : '#b28900');
            } else {
                $status.text('❌ ' + (resp.data || 'Error')).css('color', '#c53030');
            }
        }).fail(function() {
            $status.text('❌ Server error').css('color', '#c53030');
        }).always(function() {
            $btn.text('Save Template Settings').prop('disabled', false);
        });
    });
});
</script>
