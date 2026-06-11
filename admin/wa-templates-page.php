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

// Enqueue specific CSS and JS for the builder
wp_enqueue_style( 'wa-template-builder-css', plugins_url( '../assets/template-builder.css', __FILE__ ) );
wp_enqueue_script( 'wa-template-builder-js', plugins_url( '../assets/template-builder.js', __FILE__ ), array( 'jquery' ), null, true );
?>
<div class="wrap wa-template-wrap">
    <h1 class="wp-heading-inline"><?php echo esc_html($page_title); ?></h1>
    <h2 class="nav-tab-wrapper">
        <a href="#" class="nav-tab nav-tab-active">Template Builder</a>
    </h2>
    
    <input type="hidden" id="wa_current_hook" value="<?php echo esc_attr($hook_type); ?>">

    <div class="wa-builder-container">
        <!-- Left Side: Form Elements -->
        <div class="wa-builder-form">
            <div class="wa-status-bar">
                <span>Meta Status: <strong>approved</strong> (e8dfd6...)</span>
            </div>

            <table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row"><label for="wa_header_type">Header Type</label></th>
                        <td>
                            <select id="wa_header_type" class="regular-text">
                                <option value="TEXT">Text</option>
                                <option value="IMAGE">Image</option>
                                <option value="VIDEO">Video</option>
                                <option value="DOCUMENT">Document</option>
                            </select>
                        </td>
                    </tr>
                    <tr id="wa_header_text_row">
                        <th scope="row"><label for="wa_header_text">Header Text</label></th>
                        <td>
                            <input type="text" id="wa_header_text" class="large-text" value="Your Order is Shipped!" maxlength="60" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="wa_message_body">Message Body</label>
                            <br>
                            <button type="button" class="button" id="wa_insert_variable">Insert Variable {{}}</button>
                        </th>
                        <td>
                            <textarea id="wa_message_body" rows="6" class="large-text">Hi {{customer_firstname}}, your order #{{increment_id}} is on its way!

Tracking: {{tracking_number}}
Carrier: {{carrier_name}}

Thanks,</textarea>
                            <p class="description">Place cursor in text area and click insert variable.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="wa_footer_text">Footer</label></th>
                        <td>
                            <input type="text" id="wa_footer_text" class="large-text" value="Track your package for updates." maxlength="60" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label>Enable Buttons</label></th>
                        <td>
                            <label class="wa-switch">
                              <input type="checkbox" id="wa_enable_buttons" checked>
                              <span class="wa-slider round"></span>
                            </label>
                            <span class="wa-switch-label">Yes</span>
                        </td>
                    </tr>
                    <tr id="wa_buttons_row">
                        <td colspan="2" style="padding-left: 0;">
                            <div id="wa_buttons_container">
                                <div class="wa-button-item">
                                    <select class="wa-button-type">
                                        <option value="URL">URL Button</option>
                                        <option value="QUICK_REPLY">Quick Reply</option>
                                    </select>
                                    <input type="text" class="wa-button-text" value="View Order" placeholder="Button Text" />
                                    <input type="text" class="wa-button-url" value="https://mysite.local/view/{{order_id}}" placeholder="URL" />
                                    <button type="button" class="button wa-remove-button">Remove</button>
                                </div>
                            </div>
                            <button type="button" class="button" id="wa_add_button" style="margin-top: 10px;">Add Button</button>
                            <span class="description" style="margin-left: 10px;">Maximum 3 buttons allowed.</span>
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
                    <div class="wa-chat-name">Magento Store</div>
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
