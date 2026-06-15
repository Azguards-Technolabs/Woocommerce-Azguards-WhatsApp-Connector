<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$page_id = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';

$hook_type = 'marketing';
if (strpos($page_id, '-created') !== false) {
    $hook_type = 'order_created';
} elseif (strpos($page_id, '-invoice') !== false) {
    $hook_type = 'order_invoice';
} elseif (strpos($page_id, '-shipment') !== false) {
    $hook_type = 'order_shipment';
} elseif (strpos($page_id, '-creditmemo') !== false) {
    $hook_type = 'order_creditmemo';
}

$titles = [
    'order_created'    => 'Order Created Template',
    'order_invoice'    => 'Order Invoice Template',
    'order_shipment'   => 'Order Shipment Template',
    'order_creditmemo' => 'Order Credit Memo Template',
    'abandoned_cart'   => 'Abandoned Cart Reminder Template',
    'marketing'        => 'Marketing Campaign Template'
];

$page_title = isset($titles[$hook_type]) ? $titles[$hook_type] : 'Template Builder';

$edit_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$saved_template_name = '';
$saved_header_type = 'TEXT';
$saved_header_text = '';
$saved_body_template = '';
$saved_footer = '';
$saved_enable_buttons = 'no';
$saved_buttons_json = '[]';
$saved_header_handle = '';
$saved_header_url = '';
$saved_template_type = 'STANDARD';
$saved_carousel_cards_json = '[]';

if ( $edit_id > 0 ) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'azguards_whatsapp_templates';
    $tpl = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE entity_id = %d", $edit_id), ARRAY_A);
    if ( $tpl ) {
        $saved_template_name = $tpl['template_name'];
        // Fix for when template_type inadvertently contains header_format due to old bug
        $saved_template_type = (isset($tpl['template_type']) && in_array($tpl['template_type'], ['STANDARD', 'CAROUSEL'])) ? $tpl['template_type'] : 'STANDARD';
        $saved_header_type = $tpl['header_format'] ?: 'TEXT';
        // Add new database columns correctly
        $saved_header_text = $tpl['header_text'] ?? '';
        $saved_body_template = $tpl['body'];
        $saved_footer = $tpl['footer'] ?? '';
        $saved_buttons_json = $tpl['buttons'] ?? '[]';
        if (empty($saved_buttons_json) || $saved_buttons_json === 'null') $saved_buttons_json = '[]';
        $saved_carousel_cards_json = $tpl['carousel_cards'] ?? '[]';
        if (empty($saved_carousel_cards_json) || $saved_carousel_cards_json === 'null') $saved_carousel_cards_json = '[]';
        
        $saved_enable_buttons = ($saved_buttons_json !== '[]' && !empty($saved_buttons_json)) ? 'yes' : 'no';
        
        $media_handle_data = json_decode($tpl['media_handle'] ?? '', true);
        if ( is_array($media_handle_data) && !empty($media_handle_data) ) {
            $saved_header_handle = $media_handle_data['document_id'] ?? '';
            $saved_header_url = $media_handle_data['preview_link'] ?? '';
        } else {
            $saved_header_handle = $tpl['media_handle'] ?? ''; // Fallback
        }

        // Fix for header_text population if it's stored as plain string but might have been header_format
        if ( empty($saved_header_text) && $saved_header_type === 'TEXT' ) {
             // Maybe it was in header_format by mistake in some rows?
             // Unlikely with new schema but let's be safe.
        }
    }
} else {
    // If no edit ID, check if page explicitly requests a WooCommerce hook flow
    if ( $page_id === 'wa-template-builder' && empty($_GET['hook']) ) {
        // It's a fresh manual create from WhatTack Templates grid. Keep defaults clean.
        $saved_template_type = 'STANDARD';
    } else {
        $saved_template_name = get_option( "wa_template_{$hook_type}_template_name", '' );
        $saved_header_type = get_option( "wa_template_{$hook_type}_header_type", 'TEXT' );
        $saved_header_text = get_option( "wa_template_{$hook_type}_header_text", '' );
        $saved_body_template = get_option( "wa_template_{$hook_type}_body_template", "" );
        $saved_footer = get_option( "wa_template_{$hook_type}_footer_template", "" );
        $saved_enable_buttons = get_option( "wa_template_{$hook_type}_enable_buttons", 'no' );
        $saved_buttons_json = get_option( "wa_template_{$hook_type}_buttons_json", '[]' );
        $saved_header_handle = get_option( "wa_template_{$hook_type}_header_handle", '' );
        $saved_header_url = get_option( "wa_template_{$hook_type}_header_url", '' );
        $saved_template_type = get_option( "wa_template_{$hook_type}_template_type", 'STANDARD' );
        $saved_carousel_cards_json = get_option( "wa_template_{$hook_type}_carousel_cards_json", '[]' );
    }
}

$buttons = json_decode( $saved_buttons_json, true );
if ( ! is_array( $buttons ) ) {
    $buttons = [];
}

wp_enqueue_media();
wp_enqueue_style( 'wa-template-builder-css', plugins_url( '../assets/template-builder.css', __FILE__ ), array(), time() );
wp_enqueue_script( 'wa-template-builder-js', plugins_url( '../assets/template-builder.js', __FILE__ ), array( 'jquery' ), time(), true );
?>
<div class="wrap wa-template-wrap">
    <h1 class="wp-heading-inline"><?php echo esc_html($page_title); ?></h1>
    <h2 class="nav-tab-wrapper">
        <a href="#" class="nav-tab nav-tab-active">Template Builder</a>
    </h2>
    
    <input type="hidden" id="wa_current_hook" value="<?php echo esc_attr($hook_type); ?>">
    <input type="hidden" id="wa_edit_entity_id" value="<?php echo esc_attr($edit_id); ?>">
    <input type="hidden" id="wa_save_template_nonce" value="<?php echo wp_create_nonce( 'wa_save_builder_template' ); ?>">
    <script>
        var RAW_CAROUSEL_CARDS = <?php echo empty($saved_carousel_cards_json) ? '[]' : $saved_carousel_cards_json; ?>;
    </script>

    <div class="wa-builder-container">
        <!-- Left Side: Form Elements -->
        <div class="wa-builder-form">
            <div class="wa-status-bar">
                <?php if ($edit_id > 0 && isset($tpl)): ?>
                    <span>Status: <strong class="status-<?php echo esc_attr(strtolower($tpl['status'] ?? 'unknown')); ?>"><?php echo esc_html($tpl['status'] ?? 'UNKNOWN'); ?></strong> (<?php echo esc_html($tpl['template_id'] ?? 'No Meta ID'); ?>)</span>
                <?php else: ?>
                    <span>Status: <strong>NEW TEMPLATE</strong></span>
                <?php endif; ?>
            </div>

            <table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row"><label for="wa_template_type">Template Type</label></th>
                        <td>
                            <select id="wa_template_type" class="regular-text">
                                <?php
                                // Map combined type
                                if ($saved_template_type === 'CAROUSEL') {
                                    $combined_type = 'CAROUSEL';
                                } elseif (in_array(strtoupper($saved_header_type), ['IMAGE', 'VIDEO', 'DOCUMENT'])) {
                                    $combined_type = strtoupper($saved_header_type);
                                } else {
                                    $combined_type = 'TEXT';
                                }
                                ?>
                                <option value="TEXT" <?php selected($combined_type, 'TEXT'); ?>>Text</option>
                                <option value="IMAGE" <?php selected($combined_type, 'IMAGE'); ?>>Image</option>
                                <option value="VIDEO" <?php selected($combined_type, 'VIDEO'); ?>>Video</option>
                                <option value="DOCUMENT" <?php selected($combined_type, 'DOCUMENT'); ?>>Document</option>
                                <option value="CAROUSEL" <?php selected($combined_type, 'CAROUSEL'); ?>>Carousel</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="wa_template_name">Template Name</label></th>
                        <td>
                            <input type="text" id="wa_template_name" class="regular-text" value="<?php echo esc_attr($saved_template_name ?? 'My Template'); ?>" />
                        </td>
                    </tr>
                    <tr class="wa-standard-row">
                        <th scope="row"><label for="wa_header_type">Header Type</label></th>
                        <td>
                            <select id="wa_header_type" class="regular-text">
                                <option value="TEXT" <?php selected($saved_header_type, 'TEXT'); ?>>Text</option>
                                <option value="IMAGE" <?php selected($saved_header_type, 'IMAGE'); ?>>Image</option>
                                <option value="VIDEO" <?php selected($saved_header_type, 'VIDEO'); ?>>Video</option>
                                <option value="DOCUMENT" <?php selected($saved_header_type, 'DOCUMENT'); ?>>Document</option>
                            </select>
                        </td>
                    </tr>
                    <tr id="wa_header_text_row" class="wa-standard-row">
                        <th scope="row"><label for="wa_header_text">Header Text</label></th>
                        <td>
                            <input type="text" id="wa_header_text" class="large-text" value="<?php echo esc_attr($saved_header_text); ?>" maxlength="60" />
                        </td>
                    </tr>
                    <tr id="wa_header_media_row" class="wa-standard-row" style="<?php echo (in_array($saved_header_type, ['IMAGE', 'VIDEO', 'DOCUMENT'])) ? '' : 'display:none;'; ?>">
                        <th scope="row"><label>Header Media</label></th>
                        <td>
                            <input type="hidden" id="wa_header_media_handle" value="<?php echo esc_attr($saved_header_handle); ?>">
                            <input type="hidden" id="wa_header_media_url" value="<?php echo esc_url($saved_header_url); ?>">
                            <button type="button" class="button" id="wa_upload_media_btn">Choose Media</button>
                            <span id="wa_media_status" style="margin-left: 10px;">
                                <?php echo $saved_header_handle ? 'Media exists' : 'No media selected'; ?>
                            </span>
                            <div id="wa_media_preview" style="margin-top: 10px; max-width: 200px;">
                                <?php if ($saved_header_url): ?>
                                    <a href="<?php echo esc_url($saved_header_url); ?>" target="_blank">Preview Uploaded File</a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <tr class="wa-standard-row">
                        <th>
                            <label for="wa_message_body">Message Body</label>
                            <br>
                            <div class="wa-variable-inserter" style="position: relative; display: inline-block;">
                                <button type="button" class="button" id="wa_insert_variable">Insert Variable {{}}</button>
                                
                                <div class="wa-premium-variable-menu" id="wa_variable_menu" style="display: none;">
                                    <style>
                                        .wa-premium-variable-menu {
                                            position: absolute; left: 0; top: 100%; margin-top: 5px; width: 350px; 
                                            background: #ffffff; border: 1px solid #e0e0e0; border-radius: 8px;
                                            box-shadow: 0 10px 30px rgba(0,0,0,0.1); z-index: 1000; overflow: hidden;
                                            font-family: inherit; font-weight: normal; text-align: left;
                                        }
                                        .wa-var-tabs {
                                            display: flex; overflow-x: auto; background: #f8f9fa; 
                                            border-bottom: 1px solid #eee; scrollbar-width: none;
                                        }
                                        .wa-var-tabs::-webkit-scrollbar { display: none; }
                                        .wa-var-tab-btn {
                                            flex: 0 0 auto; padding: 12px 16px; border: none; background: transparent;
                                            font-weight: 600; font-size: 13px; color: #555; cursor: pointer;
                                            border-bottom: 2px solid transparent; transition: all 0.2s;
                                        }
                                        .wa-var-tab-btn:hover { color: #006bb4; }
                                        .wa-var-tab-btn.active { color: #006bb4; border-bottom-color: #006bb4; background: #fff; }
                                        .wa-var-content-area { padding: 0; max-height: 250px; overflow-y: auto; }
                                        .wa-var-panel { display: none; padding: 12px; }
                                        .wa-var-panel.active { display: block; }
                                        .wa-var-list { display: flex; flex-direction: column; gap: 4px; }
                                        .wa-var-item {
                                            cursor: pointer; padding: 8px 12px; color: #333; font-size: 13px; 
                                            border-radius: 6px; transition: all 0.2s ease; border: 1px solid transparent;
                                            display: flex; justify-content: space-between; align-items: center; text-decoration: none;
                                        }
                                        .wa-var-item:hover {
                                            background: #f0f8ff; color: #005690; border-color: #d0e7ff; text-decoration: none;
                                        }
                                        .wa-var-badge {
                                            font-size: 11px; color: #006bb4; background: #e6f2fc; 
                                            padding: 2px 6px; border-radius: 4px; font-family: monospace;
                                        }
                                    </style>
                                    
                                    <?php
                                    require_once dirname(__FILE__) . '/../includes/wc-woocommerce-variables.php';
                                    $hook_map = [
                                        'order_created' => 'wa_order_creation_table_data',
                                        'order_shipment' => 'wa_order_shipment_table_data',
                                        'order_invoice' => 'wa_order_invoice_table_data',
                                        'order_creditmemo' => 'wa_order_credit_memo_table_data',
                                        'marketing' => 'wa_order_creation_table_data', // fallback
                                    ];
                                    $hook_key = $hook_map[$hook_type] ?? 'wa_order_creation_table_data';
                                    $available_variables = WA_WoocommerceOptions::get_woocommerce_options($hook_key);
                                    
                                    $groups = ['Order' => [], 'Customer & Address' => []];
                                    foreach ( $available_variables as $key => $label ) {
                                        if ( strpos($key, 'billing_') === 0 || strpos($key, 'shipping_') === 0 || strpos($key, 'customer_') === 0 ) {
                                            $groups['Customer & Address'][$key] = $label;
                                        } else {
                                            $groups['Order'][$key] = $label;
                                        }
                                    }
                                    ?>
                                    
                                    <div class="wa-var-tabs">
                                        <?php $first = true; foreach ($groups as $groupName => $vars): ?>
                                            <button type="button" class="wa-var-tab-btn<?= $first ? ' active' : '' ?>"
                                                    data-target="panel-<?= str_replace([' ', '&'], ['-', ''], strtolower($groupName)) ?>">
                                                <?= esc_html($groupName) ?>
                                            </button>
                                        <?php $first = false; endforeach; ?>
                                    </div>
                                    
                                    <div class="wa-var-content-area">
                                        <?php $first = true; foreach ($groups as $groupName => $vars): ?>
                                            <div id="panel-<?= str_replace([' ', '&'], ['-', ''], strtolower($groupName)) ?>"
                                                 class="wa-var-panel<?= $first ? ' active' : '' ?>">
                                                <div class="wa-var-list">
                                                    <?php foreach ($vars as $key => $label): ?>
                                                        <a class="wa-var-item" data-val="{{<?= esc_attr($key) ?>}}">
                                                            <span><?= esc_html($label) ?></span>
                                                            <span class="wa-var-badge"><?= esc_html($key) ?></span>
                                                        </a>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        <?php $first = false; endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </th>
                        <td>
                            <textarea id="wa_message_body" rows="6" class="large-text"><?php echo esc_textarea($saved_body_template); ?></textarea>
                            <p class="description">Place cursor in text area and click insert variable.</p>
                        </td>
                    </tr>
                    <tr class="wa-standard-row">
                        <th scope="row"><label for="wa_footer_text">Footer</label></th>
                        <td>
                            <input type="text" id="wa_footer_text" class="large-text" value="<?php echo esc_attr($saved_footer); ?>" maxlength="60" />
                        </td>
                    </tr>
                    <tr class="wa-standard-row">
                        <th scope="row"><label>Enable Buttons</label></th>
                        <td>
                            <label class="wa-switch">
                              <input type="checkbox" id="wa_enable_buttons" <?php checked($saved_enable_buttons, 'yes'); ?>>
                              <span class="wa-slider round"></span>
                            </label>
                            <span class="wa-switch-label">Yes</span>
                        </td>
                    </tr>
                    <tr id="wa_buttons_row" class="wa-standard-row">
                        <td colspan="2" style="padding-left: 0;">
                            <div id="wa_buttons_container">
                                <?php foreach ($buttons as $btn) : ?>
                                <div class="wa-button-item" style="display:flex; gap:8px; margin-bottom:8px; align-items:center;">
                                    <select class="wa-button-type">
                                        <option value="URL" <?php selected($btn['type'] ?? 'URL', 'URL'); ?>>URL (Website Link)</option>
                                        <option value="QUICK_REPLY" <?php selected($btn['type'] ?? 'URL', 'QUICK_REPLY'); ?>>Quick Reply</option>
                                        <option value="PHONE_NUMBER" <?php selected($btn['type'] ?? 'URL', 'PHONE_NUMBER'); ?>>Phone Number (Call Button)</option>
                                        <option value="COPY_CODE" <?php selected($btn['type'] ?? 'URL', 'COPY_CODE'); ?>>Copy Coupon Code (COPY_CODE)</option>
                                    </select>
                                    <input type="text" class="wa-button-text" value="<?php echo esc_attr($btn['text'] ?? ''); ?>" placeholder="Button Label" style="width:160px;" />
                                    <input type="text" class="wa-button-url" value="<?php echo esc_attr($btn['url'] ?? $btn['button_url'] ?? ''); ?>" placeholder="URL / Phone / Coupon" style="flex:1;" />
                                    <button type="button" class="button wa-remove-button">Remove</button>
                                </div>
                                <?php endforeach; ?>
                                <!-- Hidden template for JS cloning -->
                                <div class="wa-button-item" style="display:none; gap:8px; margin-bottom:8px; align-items:center;">
                                    <select class="wa-button-type">
                                        <option value="URL">URL (Website Link)</option>
                                        <option value="QUICK_REPLY">Quick Reply</option>
                                        <option value="PHONE_NUMBER">Phone Number (Call Button)</option>
                                        <option value="COPY_CODE">Copy Coupon Code (COPY_CODE)</option>
                                    </select>
                                    <input type="text" class="wa-button-text" value="" placeholder="Button Label" style="width:160px;" />
                                    <input type="text" class="wa-button-url" value="" placeholder="URL / Phone / Coupon" style="flex:1;" />
                                    <button type="button" class="button wa-remove-button">Remove</button>
                                </div>
                            </div>
                            <button type="button" class="button" id="wa_add_button" style="margin-top: 10px;">Add Button</button>
                            <span class="description" style="margin-left: 10px;">Maximum 3 buttons allowed.</span>
                        </td>
                    </tr>
                    <tr id="wa_carousel_row" style="display:none;">
                        <td colspan="2" style="padding-left: 0;">
                            <h3>Carousel Cards</h3>
                            <div id="wa_carousel_cards_container">
                                <!-- Cards will be dynamically inserted here -->
                            </div>
                            <button type="button" class="button" id="wa_add_card_btn" style="margin-top: 10px;">Add Card</button>
                            <span class="description" style="margin-left: 10px;">Maximum 10 cards allowed.</span>
                        </td>
                    </tr>
                </tbody>
            </table>
            
            <p class="submit">
                <button type="button" class="button button-primary" id="wa_save_template">Save Template</button>
            </p>
        </div>

        <!-- Right Side: Live Preview -->
        <div class="wa-live-preview">
            <h3>Live Preview</h3>
            <div class="wa-iphone-mockup">
                <div class="wa-iphone-header">
                    <span class="wa-iphone-time">9:41</span>
                    <span class="wa-iphone-icons">LTE 100%</span>
                </div>
                <div class="wa-chat-header">
                    <div class="wa-chat-avatar">WA</div>
                    <div class="wa-chat-name">WooCommerce Store</div>
                </div>
                <div class="wa-chat-body">
                    <div class="wa-message-bubble">
                        <div class="wa-message-header" id="preview_header">Your Order is Shipped!</div>
                        <div class="wa-message-text" id="preview_body">
                            Hi Zubair, your order #10001 is on its way!<br><br>
                            Tracking: CA-123456<br>
                            Carrier: Thanks
                        </div>
                        <div class="wa-message-footer" id="preview_footer">Track your package for updates.</div>
                        <div class="wa-message-buttons" id="preview_buttons">
                            <div class="wa-preview-btn"><span class="wa-btn-icon">&#128279;</span> View Order</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/template" id="wa-carousel-card-template">
    <div class="wa-carousel-card" style="border:1px solid #ccc; padding:15px; margin-bottom:15px; border-radius: 8px; background: #fafafa;">
        <h4 style="margin-top:0;">Card <span class="card-num"></span></h4>
        
        <table class="form-table" style="margin-bottom: 10px;">
            <tr>
                <th scope="row" style="padding: 10px 10px 10px 0;"><label>Header Image / Video</label></th>
                <td style="padding: 10px;">
                    <select class="wa-card-header-type regular-text" style="vertical-align: top; margin-right: 10px;">
                        <option value="IMAGE">Image</option>
                        <option value="VIDEO">Video</option>
                    </select>
                    <input type="hidden" class="wa-card-header-handle" value="">
                    <input type="hidden" class="wa-card-header-url" value="">
                    <button type="button" class="button wa-card-upload-media">Choose Media</button>
                    <span class="wa-card-media-status" style="margin-left: 10px; font-size: 12px;"></span>
                </td>
            </tr>
            <tr>
                <th scope="row" style="padding: 10px 10px 10px 0;"><label>Body</label></th>
                <td style="padding: 10px;">
                    <textarea class="wa-card-body large-text" rows="3" maxlength="160"></textarea>
                </td>
            </tr>
            <tr>
                <th scope="row" style="padding: 10px 10px 10px 0;"><label>Buttons</label></th>
                <td style="padding: 10px;">
                    <div class="wa-card-buttons-container"></div>
                    <button type="button" class="button wa-card-add-button" style="margin-top:5px;">Add Button</button>
                </td>
            </tr>
        </table>
        
        <button type="button" class="button button-link-delete wa-remove-card" style="color: #d63638;">Remove Card</button>
    </div>
</script>
