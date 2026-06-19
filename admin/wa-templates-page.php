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
$saved_category      = 'MARKETING';
$saved_language      = 'en_US';
$saved_header_type   = 'TEXT';
$saved_header_text   = '';
$saved_body_template = '';
$saved_footer        = '';
$saved_enable_buttons = 'no';
$saved_buttons_json   = '[]';
$saved_header_handle  = '';
$saved_header_url     = '';
$saved_template_type  = 'STANDARD';
$saved_carousel_cards_json = '[]';

if ( $edit_id > 0 ) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'azguards_whatsapp_templates';
    $tpl = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE entity_id = %d", $edit_id), ARRAY_A);
    if ( $tpl ) {
        $saved_template_name = $tpl['template_name'];
        $page_title = 'Edit Template - ' . $saved_template_name;
        $saved_category      = $tpl['category'] ?: 'MARKETING';
        $saved_language      = $tpl['language'] ?: 'en_US';
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
            $saved_header_handle = $media_handle_data['document_id'] ?? $media_handle_data['id'] ?? $media_handle_data['handle'] ?? '';
            $saved_header_url    = $media_handle_data['preview_link'] ?? $media_handle_data['url'] ?? $media_handle_data['link'] ?? '';
        } else {
            $saved_header_handle = $tpl['media_handle'] ?? ''; // Fallback
        }
    }
} else {
    // If no edit ID, check if page explicitly requests a WooCommerce hook flow
    if ( $page_id === 'wa-template-builder' && empty($_GET['hook']) ) {
        // It's a fresh manual create from WhatTack Templates grid. Keep defaults clean.
        $saved_template_type = 'STANDARD';
        $page_title = 'Create Template';
    } else {
        $page_title = 'Create Template - ' . (isset($titles[$hook_type]) ? $titles[$hook_type] : 'Template Builder');

        $saved_template_name = get_option( "wa_template_{$hook_type}_template_name", '' );
        $saved_category      = get_option( "wa_template_{$hook_type}_category", 'MARKETING' );
        $saved_language      = get_option( "wa_template_{$hook_type}_language", 'en_US' );
        $saved_header_type   = get_option( "wa_template_{$hook_type}_header_type", 'TEXT' );
        $saved_header_text   = get_option( "wa_template_{$hook_type}_header_text", '' );
        $saved_body_template = get_option( "wa_template_{$hook_type}_body_template", "" );
        $saved_footer        = get_option( "wa_template_{$hook_type}_footer_template", "" );
        $saved_enable_buttons = get_option( "wa_template_{$hook_type}_enable_buttons", 'no' );
        $saved_buttons_json   = get_option( "wa_template_{$hook_type}_buttons_json", '[]' );
        $saved_header_handle  = get_option( "wa_template_{$hook_type}_header_handle", '' );
        $saved_header_url     = get_option( "wa_template_{$hook_type}_header_url", '' );
        $saved_template_type  = get_option( "wa_template_{$hook_type}_template_type", 'STANDARD' );
        $saved_carousel_cards_json = get_option( "wa_template_{$hook_type}_carousel_cards_json", '[]' );
    }
}

$buttons = json_decode( $saved_buttons_json, true );
if ( ! is_array( $buttons ) ) {
    $buttons = [];
}

wp_enqueue_media();
wp_enqueue_style( 'wa-google-fonts-inter', 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap', array(), null );
wp_enqueue_style( 'wa-template-builder-css', plugins_url( '../assets/template-builder.css', __FILE__ ), array(), WA_CONNECTOR_VERSION );
wp_enqueue_script( 'wa-template-builder-js', plugins_url( '../assets/template-builder.js', __FILE__ ), array( 'jquery' ), WA_CONNECTOR_VERSION, true );
?>
<div class="wrap wa-template-wrap">
    <h1 class="wp-heading-inline"><?php echo esc_html($page_title); ?></h1>
    <a href="<?php echo esc_url( admin_url( 'admin.php?page=wa-templates-grid' ) ); ?>" class="page-title-action">&larr; Back to Templates</a>
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
                <?php if ($edit_id > 0 && isset($tpl)):
                    $st = strtolower($tpl['status'] ?? 'unknown');
                    $st_icons = ['approved'=>'✅','rejected'=>'❌','pending'=>'⏳','local'=>'💾'];
                    $st_icon  = $st_icons[$st] ?? '•';
                ?>
                    <span><?php echo $st_icon; ?> &nbsp;<strong class="status-<?php echo esc_attr($st); ?>"><?php echo esc_html(strtoupper($st)); ?></strong></span>
                    <span style="color:#d1d5db">|</span>
                    <span style="font-size:12px;color:#6b7280;font-family:monospace"><?php echo esc_html($tpl['template_id'] ?? '—'); ?></span>
                <?php else: ?>
                    <span>🆕 &nbsp;<strong>NEW TEMPLATE</strong></span>
                <?php endif; ?>
            </div>

            <table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row"><label for="wa_template_name"><?php esc_html_e( 'Template Name', 'whatsapp-connector' ); ?> <span style="color:red;">*</span></label></th>
                        <td>
                            <input type="text" id="wa_template_name" class="regular-text" value="<?php echo esc_attr($saved_template_name ?? 'My Template'); ?>" />
                            <p class="description"><?php esc_html_e( 'Use a unique WhatsApp template key. Keep it lowercase and use only letters, numbers, and underscores, for example order_shipped_update.', 'whatsapp-connector' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="wa_template_type"><?php esc_html_e( 'Template Type', 'whatsapp-connector' ); ?> <span style="color:red;">*</span></label></th>
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
                                <option value="TEXT" <?php selected($combined_type, 'TEXT'); ?>><?php esc_html_e( 'Text', 'whatsapp-connector' ); ?></option>
                                <option value="IMAGE" <?php selected($combined_type, 'IMAGE'); ?>><?php esc_html_e( 'Image', 'whatsapp-connector' ); ?></option>
                                <option value="VIDEO" <?php selected($combined_type, 'VIDEO'); ?>><?php esc_html_e( 'Video', 'whatsapp-connector' ); ?></option>
                                <option value="DOCUMENT" <?php selected($combined_type, 'DOCUMENT'); ?>><?php esc_html_e( 'Document', 'whatsapp-connector' ); ?></option>
                                <option value="CAROUSEL" <?php selected($combined_type, 'CAROUSEL'); ?>><?php esc_html_e( 'Carousel', 'whatsapp-connector' ); ?></option>
                            </select>
                            <p class="description"><?php esc_html_e( 'Choose the message format to submit to WhatsApp. Text is for plain templates; Image, Video, and Document require header media; Carousel is for multi-card marketing templates.', 'whatsapp-connector' ); ?></p>
                        </td>
                    </tr>
                    <tr id="wa_category_notice_row" style="display: none;">
                        <td colspan="2">
                             <div style="background: #fff8e1; border-left: 4px solid #ffca28; padding: 10px; margin-bottom: 5px;">
                                <strong><?php esc_html_e( 'Category: Marketing (Auto-set for Media Templates)', 'whatsapp-connector' ); ?></strong>
                             </div>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="wa_template_category"><?php esc_html_e( 'Template Category', 'whatsapp-connector' ); ?> <span style="color:red;">*</span></label></th>
                        <td>
                            <select id="wa_template_category" class="regular-text">
                                <option value="MARKETING" <?php selected($saved_category, 'MARKETING'); ?>><?php esc_html_e( 'Marketing', 'whatsapp-connector' ); ?></option>
                                <option value="UTILITY" <?php selected($saved_category, 'UTILITY'); ?>><?php esc_html_e( 'Utility', 'whatsapp-connector' ); ?></option>
                                <option value="AUTHENTICATION" <?php selected($saved_category, 'AUTHENTICATION'); ?>><?php esc_html_e( 'Authentication', 'whatsapp-connector' ); ?></option>
                            </select>
                            <p class="description"><?php esc_html_e( 'Marketing is used for promotional campaign templates. Utility is used for order and service updates. Authentication is used for OTP or login-code templates.', 'whatsapp-connector' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="wa_language_code">Language Code <span style="color:red;">*</span></label></th>
                        <td>
                            <select id="wa_language_code" class="regular-text">
                                <option value="">-- Select Language --</option>
                                <option value="en" <?php selected($saved_language, 'en'); ?>>English</option>
                                <option value="en_US" <?php selected($saved_language, 'en_US'); ?>>English (US)</option>
                                <option value="en_GB" <?php selected($saved_language, 'en_GB'); ?>>English (UK)</option>
                                <option value="af" <?php selected($saved_language, 'af'); ?>>Afrikaans</option>
                                <option value="sq" <?php selected($saved_language, 'sq'); ?>>Albanian</option>
                                <option value="ar" <?php selected($saved_language, 'ar'); ?>>Arabic</option>
                                <option value="az" <?php selected($saved_language, 'az'); ?>>Azerbaijani</option>
                                <option value="bn" <?php selected($saved_language, 'bn'); ?>>Bengali</option>
                                <option value="bg" <?php selected($saved_language, 'bg'); ?>>Bulgarian</option>
                                <option value="ca" <?php selected($saved_language, 'ca'); ?>>Catalan</option>
                                <option value="zh_CN" <?php selected($saved_language, 'zh_CN'); ?>>Chinese (CHN)</option>
                                <option value="zh_HK" <?php selected($saved_language, 'zh_HK'); ?>>Chinese (HKG)</option>
                                <option value="zh_TW" <?php selected($saved_language, 'zh_TW'); ?>>Chinese (TAI)</option>
                                <option value="hr" <?php selected($saved_language, 'hr'); ?>>Croatian</option>
                                <option value="cs" <?php selected($saved_language, 'cs'); ?>>Czech</option>
                                <option value="da" <?php selected($saved_language, 'da'); ?>>Danish</option>
                                <option value="nl" <?php selected($saved_language, 'nl'); ?>>Dutch</option>
                                <option value="et" <?php selected($saved_language, 'et'); ?>>Estonian</option>
                                <option value="fil" <?php selected($saved_language, 'fil'); ?>>Filipino</option>
                                <option value="fi" <?php selected($saved_language, 'fi'); ?>>Finnish</option>
                                <option value="fr" <?php selected($saved_language, 'fr'); ?>>French</option>
                                <option value="de" <?php selected($saved_language, 'de'); ?>>German</option>
                                <option value="el" <?php selected($saved_language, 'el'); ?>>Greek</option>
                                <option value="gu" <?php selected($saved_language, 'gu'); ?>>Gujarati</option>
                                <option value="he" <?php selected($saved_language, 'he'); ?>>Hebrew</option>
                                <option value="hi" <?php selected($saved_language, 'hi'); ?>>Hindi</option>
                                <option value="hu" <?php selected($saved_language, 'hu'); ?>>Hungarian</option>
                                <option value="id" <?php selected($saved_language, 'id'); ?>>Indonesian</option>
                                <option value="ga" <?php selected($saved_language, 'ga'); ?>>Irish</option>
                                <option value="it" <?php selected($saved_language, 'it'); ?>>Italian</option>
                                <option value="ja" <?php selected($saved_language, 'ja'); ?>>Japanese</option>
                                <option value="kn" <?php selected($saved_language, 'kn'); ?>>Kannada</option>
                                <option value="kk" <?php selected($saved_language, 'kk'); ?>>Kazakh</option>
                                <option value="ko" <?php selected($saved_language, 'ko'); ?>>Korean</option>
                                <option value="lo" <?php selected($saved_language, 'lo'); ?>>Lao</option>
                                <option value="lv" <?php selected($saved_language, 'lv'); ?>>Latvian</option>
                                <option value="lt" <?php selected($saved_language, 'lt'); ?>>Lithuanian</option>
                                <option value="mk" <?php selected($saved_language, 'mk'); ?>>Macedonian</option>
                                <option value="ms" <?php selected($saved_language, 'ms'); ?>>Malay</option>
                                <option value="ml" <?php selected($saved_language, 'ml'); ?>>Malayalam</option>
                                <option value="mr" <?php selected($saved_language, 'mr'); ?>>Marathi</option>
                                <option value="nb" <?php selected($saved_language, 'nb'); ?>>Norwegian</option>
                                <option value="fa" <?php selected($saved_language, 'fa'); ?>>Persian</option>
                                <option value="pl" <?php selected($saved_language, 'pl'); ?>>Polish</option>
                                <option value="pt_BR" <?php selected($saved_language, 'pt_BR'); ?>>Portuguese (BR)</option>
                                <option value="pt_PT" <?php selected($saved_language, 'pt_PT'); ?>>Portuguese (PT)</option>
                                <option value="pa" <?php selected($saved_language, 'pa'); ?>>Punjabi</option>
                                <option value="ro" <?php selected($saved_language, 'ro'); ?>>Romanian</option>
                                <option value="ru" <?php selected($saved_language, 'ru'); ?>>Russian</option>
                                <option value="sr" <?php selected($saved_language, 'sr'); ?>>Serbian</option>
                                <option value="sk" <?php selected($saved_language, 'sk'); ?>>Slovak</option>
                                <option value="sl" <?php selected($saved_language, 'sl'); ?>>Slovenian</option>
                                <option value="es" <?php selected($saved_language, 'es'); ?>>Spanish</option>
                                <option value="sw" <?php selected($saved_language, 'sw'); ?>>Swahili</option>
                                <option value="sv" <?php selected($saved_language, 'sv'); ?>>Swedish</option>
                                <option value="ta" <?php selected($saved_language, 'ta'); ?>>Tamil</option>
                                <option value="te" <?php selected($saved_language, 'te'); ?>>Telugu</option>
                                <option value="th" <?php selected($saved_language, 'th'); ?>>Thai</option>
                                <option value="tr" <?php selected($saved_language, 'tr'); ?>>Turkish</option>
                                <option value="uk" <?php selected($saved_language, 'uk'); ?>>Ukrainian</option>
                                <option value="ur" <?php selected($saved_language, 'ur'); ?>>Urdu</option>
                                <option value="uz" <?php selected($saved_language, 'uz'); ?>>Uzbek</option>
                                <option value="vi" <?php selected($saved_language, 'vi'); ?>>Vietnamese</option>
                                <option value="zu" <?php selected($saved_language, 'zu'); ?>>Zulu</option>
                            </select>
                            <p class="description"><?php esc_html_e( 'Choose the same language used in the message text. WhatsApp approval is language-specific, so Gujarati content should use Gujarati, Hindi content should use Hindi, and so on.', 'whatsapp-connector' ); ?></p>
                        </td>
                    </tr>
                    <tr class="wa-body-heading-row">
                        <td colspan="2" style="padding-left: 0;">
                            <h3 style="border-bottom: 1px solid #ccc; padding-bottom: 10px; margin-top: 20px;"><?php esc_html_e( 'BODY (Required)', 'whatsapp-connector' ); ?></h3>
                        </td>
                    </tr>
                    <tr class="wa-standard-row">
                        <th scope="row"><label for="wa_header_type"><?php esc_html_e( 'Header Type', 'whatsapp-connector' ); ?></label></th>
                        <td>
                            <select id="wa_header_type" class="regular-text">
                                <option value="TEXT" <?php selected($saved_header_type, 'TEXT'); ?>><?php esc_html_e( 'Text', 'whatsapp-connector' ); ?></option>
                                <option value="IMAGE" <?php selected($saved_header_type, 'IMAGE'); ?>><?php esc_html_e( 'Image', 'whatsapp-connector' ); ?></option>
                                <option value="VIDEO" <?php selected($saved_header_type, 'VIDEO'); ?>><?php esc_html_e( 'Video', 'whatsapp-connector' ); ?></option>
                                <option value="DOCUMENT" <?php selected($saved_header_type, 'DOCUMENT'); ?>><?php esc_html_e( 'Document', 'whatsapp-connector' ); ?></option>
                            </select>
                            <p class="description"><?php esc_html_e( 'Select Text for a short title header, or choose Image, Video, or Document when the template should start with media.', 'whatsapp-connector' ); ?></p>
                        </td>
                    </tr>
                    <tr id="wa_header_text_row" class="wa-standard-row">
                        <th scope="row"><label for="wa_header_text"><?php esc_html_e( 'Header Text', 'whatsapp-connector' ); ?></label></th>
                        <td>
                            <input type="text" id="wa_header_text" class="large-text" value="<?php echo esc_attr($saved_header_text); ?>" maxlength="60" />
                            <p class="description"><?php esc_html_e( 'Optional short title shown above the message body. Use it for campaign title, order update heading, or alert title. Maximum 60 characters.', 'whatsapp-connector' ); ?></p>
                        </td>
                    </tr>
                    <tr id="wa_header_media_row" class="wa-standard-row" style="<?php echo (in_array($saved_header_type, ['IMAGE', 'VIDEO', 'DOCUMENT'])) ? '' : 'display:none;'; ?>">
                        <th scope="row"><label><?php esc_html_e( 'Header Media', 'whatsapp-connector' ); ?></label></th>
                        <td>
                            <input type="hidden" id="wa_header_media_handle" value="<?php echo esc_attr($saved_header_handle); ?>">
                            <input type="hidden" id="wa_header_media_url" value="<?php echo esc_url($saved_header_url); ?>">
                            <button type="button" class="button" id="wa_upload_media_btn"><?php esc_html_e( 'Choose Media', 'whatsapp-connector' ); ?></button>
                            <span id="wa_media_status" style="margin-left: 10px;">
                                <?php if ( $saved_header_handle ) : ?>
                                    ✅ <?php esc_html_e( 'Media saved', 'whatsapp-connector' ); ?>
                                    <span style="color:#666; font-size:11px; margin-left:6px;">(<?php echo esc_html( strlen($saved_header_handle) > 40 ? substr($saved_header_handle, 0, 40) . '…' : $saved_header_handle ); ?>)</span>
                                <?php else : ?>
                                    <?php esc_html_e( 'No media selected', 'whatsapp-connector' ); ?>
                                <?php endif; ?>
                            </span>
                            <div id="wa_media_preview" style="margin-top: 10px; max-width: 200px;">
                                <?php if ( $saved_header_url && preg_match('/\.(jpg|jpeg|png|gif|webp)(\?.*)?$/i', $saved_header_url) ) : ?>
                                    <img src="<?php echo esc_url($saved_header_url); ?>" alt="Header preview" style="max-width:200px; max-height:120px; border:1px solid #ddd; border-radius:4px;">
                                <?php elseif ( $saved_header_url ) : ?>
                                    <a href="<?php echo esc_url($saved_header_url); ?>" target="_blank"><?php esc_html_e( 'Preview Uploaded File', 'whatsapp-connector' ); ?></a>
                                <?php elseif ( $saved_header_handle ) : ?>
                                    <p style="color:#666; font-size:12px; margin:4px 0 0;"><?php esc_html_e( 'Media handle stored (from API sync). Re-upload if you want to change it.', 'whatsapp-connector' ); ?></p>
                                <?php endif; ?>
                            </div>
                            <p class="description"><?php esc_html_e( 'Required when Header Type is Image, Video, or Document. Upload the file that should appear at the top of the WhatsApp message.', 'whatsapp-connector' ); ?></p>
                        </td>
                    </tr>
                    <tr class="wa-standard-row">
                        <th>
                            <label for="wa_message_body"><?php esc_html_e( 'Body Content', 'whatsapp-connector' ); ?> <span style="color:red;">*</span></label>
                        </th>
                        <td>
                            <textarea id="wa_message_body" rows="6" class="large-text"><?php echo esc_textarea($saved_body_template); ?></textarea>
                            <p class="description"><?php esc_html_e( 'Write the customer-facing WhatsApp message here. If dynamic values are needed, type the approved placeholders manually, for example {{firstname}}, {{lastname}}, {{name}}, {{email}}, {{phone}}, {{customer_id}}, {{mobileNumber}}, {{countryCode}}, {{businessName}}, or {{website}}. No insert-variable grid is shown on this screen.', 'whatsapp-connector' ); ?></p>
                        </td>
                    </tr>
                    <tr class="wa-standard-row">
                        <th scope="row"><label for="wa_footer_text"><?php esc_html_e( 'Footer', 'whatsapp-connector' ); ?></label></th>
                        <td>
                            <input type="text" id="wa_footer_text" class="large-text" value="<?php echo esc_attr($saved_footer); ?>" maxlength="60" />
                            <p class="description"><?php esc_html_e( 'Optional small text shown below the body. Use it for unsubscribe note, store name, or a short reminder. Maximum 60 characters.', 'whatsapp-connector' ); ?></p>
                        </td>
                    </tr>
                    <tr class="wa-standard-row">
                        <th scope="row"><label><?php esc_html_e( 'Enable Buttons', 'whatsapp-connector' ); ?></label></th>
                        <td>
                            <label class="wa-switch">
                              <input type="checkbox" id="wa_enable_buttons" <?php checked($saved_enable_buttons, 'yes'); ?>>
                              <span class="wa-slider round"></span>
                            </label>
                            <span class="wa-switch-label"><?php esc_html_e( 'Yes', 'whatsapp-connector' ); ?></span>
                            <p class="description"><?php esc_html_e( 'Turn this on when the message needs customer actions such as open website, quick reply, call, or copy coupon code.', 'whatsapp-connector' ); ?></p>
                        </td>
                    </tr>
                    <tr id="wa_buttons_row" class="wa-standard-row">
                        <td colspan="2" style="padding-left: 0;">
                            <div id="wa_buttons_container">
                                <?php foreach ($buttons as $btn) : ?>
                                <div class="wa-button-item" style="display:flex; gap:8px; margin-bottom:8px; align-items:center;">
                                    <select class="wa-button-type">
                                        <option value="URL" <?php selected($btn['type'] ?? 'URL', 'URL'); ?>><?php esc_html_e( 'URL (Website Link)', 'whatsapp-connector' ); ?></option>
                                        <option value="QUICK_REPLY" <?php selected($btn['type'] ?? 'URL', 'QUICK_REPLY'); ?>><?php esc_html_e( 'Quick Reply', 'whatsapp-connector' ); ?></option>
                                        <option value="PHONE_NUMBER" <?php selected($btn['type'] ?? 'URL', 'PHONE_NUMBER'); ?>><?php esc_html_e( 'Phone Number (Call Button)', 'whatsapp-connector' ); ?></option>
                                        <option value="COPY_CODE" <?php selected($btn['type'] ?? 'URL', 'COPY_CODE'); ?>><?php esc_html_e( 'Copy Coupon Code (COPY_CODE)', 'whatsapp-connector' ); ?></option>
                                    </select>
                                    <input type="text" class="wa-button-text" value="<?php echo esc_attr($btn['text'] ?? ''); ?>" placeholder="<?php esc_attr_e( 'Button Label', 'whatsapp-connector' ); ?>" style="width:160px;" />
                                    <input type="text" class="wa-button-url" value="<?php echo esc_attr($btn['url'] ?? $btn['button_url'] ?? ''); ?>" placeholder="<?php esc_attr_e( 'URL / Phone / Coupon', 'whatsapp-connector' ); ?>" style="flex:1;" />
                                    <button type="button" class="button wa-remove-button"><?php esc_html_e( 'Remove', 'whatsapp-connector' ); ?></button>
                                </div>
                                <?php endforeach; ?>
                                <!-- Hidden template for JS cloning -->
                                <div class="wa-button-item" style="display:none; gap:8px; margin-bottom:8px; align-items:center;">
                                    <select class="wa-button-type">
                                        <option value="URL"><?php esc_html_e( 'URL (Website Link)', 'whatsapp-connector' ); ?></option>
                                        <option value="QUICK_REPLY"><?php esc_html_e( 'Quick Reply', 'whatsapp-connector' ); ?></option>
                                        <option value="PHONE_NUMBER"><?php esc_html_e( 'Phone Number (Call Button)', 'whatsapp-connector' ); ?></option>
                                        <option value="COPY_CODE"><?php esc_html_e( 'Copy Coupon Code (COPY_CODE)', 'whatsapp-connector' ); ?></option>
                                    </select>
                                    <input type="text" class="wa-button-text" value="" placeholder="<?php esc_attr_e( 'Button Label', 'whatsapp-connector' ); ?>" style="width:160px;" />
                                    <input type="text" class="wa-button-url" value="" placeholder="<?php esc_attr_e( 'URL / Phone / Coupon', 'whatsapp-connector' ); ?>" style="flex:1;" />
                                    <button type="button" class="button wa-remove-button"><?php esc_html_e( 'Remove', 'whatsapp-connector' ); ?></button>
                                </div>
                            </div>
                            <button type="button" class="button" id="wa_add_button" style="margin-top: 10px;"><?php esc_html_e( 'Add Button', 'whatsapp-connector' ); ?></button>
                            <span class="description" style="margin-left: 10px;"><?php esc_html_e( 'Maximum 3 buttons allowed. URL opens a page, Quick Reply sends a preset reply, Phone Number starts a call, and Copy Coupon Code copies the offer code.', 'whatsapp-connector' ); ?></span>
                        </td>
                    </tr>
                    <tr id="wa_carousel_row" style="display:none;">
                        <td colspan="2" style="padding-left: 0;">
                            <h3 style="border-bottom: 1px solid #ccc; padding-bottom: 10px; margin-top: 20px;">Carousel Cards (For Carousel Template)</h3>
                            <div id="wa_carousel_cards_container">
                                <!-- Cards will be dynamically inserted here -->
                            </div>
                            <div style="margin-top: 10px;">
                                <button type="button" class="button" id="wa_add_card_btn">Add Card</button>
                                <span class="description" style="margin-left: 10px;">Maximum 10 cards allowed.</span>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
            
            <div class="wa-save-bar">
                <button type="button" id="wa_save_template">
                    <span class="wa-save-icon">💾</span>
                    <span class="wa-save-label"><?php esc_html_e( 'Save Template', 'whatsapp-connector' ); ?></span>
                    <span class="wa-save-spinner" style="display:none;">⏳</span>
                </button>
                <span id="wa_save_status"></span>
            </div>
        </div>

        <!-- Right Side: Live Preview -->
        <div class="wa-live-preview">
            <h3><?php esc_html_e( 'Live Preview', 'whatsapp-connector' ); ?></h3>
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
        <h4 style="margin-top:0; border-bottom: 1px solid #eee; padding-bottom: 10px;">Card <span class="card-num"></span></h4>
        
        <table class="form-table" style="margin-bottom: 10px; border-collapse: collapse;">
            <tr>
                <th scope="row" style="padding: 10px 10px 10px 0; width: 200px;"><label>Body Content <span style="color:red;">*</span></label></th>
                <td style="padding: 10px;">
                    <textarea class="wa-card-body large-text" rows="3" maxlength="160" placeholder="Required. Card body text. Use {{1}}, {{2}} etc. for dynamic variables."></textarea>
                    <p class="description" style="font-size: 11px;">Required. Card body text. Use {{1}}, {{2}} etc. for dynamic variables.</p>
                </td>
            </tr>
            <tr>
                <th scope="row" style="padding: 10px 10px 10px 0;"><label>Header Format</label></th>
                <td style="padding: 10px;">
                    <select class="wa-card-header-type regular-text" style="vertical-align: top; margin-right: 10px;">
                        <option value="TEXT">Text</option>
                        <option value="IMAGE">Image</option>
                        <option value="VIDEO">Video</option>
                    </select>
                    <p class="description" style="font-size: 11px;">Select the header media type for this card (IMAGE or VIDEO).</p>
                </td>
            </tr>
            <tr class="wa-card-media-upload-row">
                <th scope="row" style="padding: 10px 10px 10px 0;"><label>Header Media Upload</label></th>
                <td style="padding: 10px;">
                    <input type="hidden" class="wa-card-header-handle" value="">
                    <input type="hidden" class="wa-card-header-url" value="">
                    <button type="button" class="button wa-card-upload-media">Upload</button>
                    <span class="wa-card-media-status" style="margin-left: 10px; font-size: 12px;"></span>
                    <p class="description" style="font-size: 11px;">Upload an image or video for this card. Required if Header Format is IMAGE or VIDEO. Allowed: jpg, jpeg, png, mp4, 3gp.</p>
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
        
        <div style="text-align: right;">
            <button type="button" class="button button-link-delete wa-remove-card" style="color: #d63638;">Remove Card</button>
        </div>
    </div>
</script>
