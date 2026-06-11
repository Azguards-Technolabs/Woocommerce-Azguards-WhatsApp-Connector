<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$hook_type = isset($_GET['hook']) ? sanitize_text_field($_GET['hook']) : 'order_shipment';
$unique_suffix = '_' . $hook_type; // Create unique suffix for HTML scoping

wp_enqueue_style( 'wa-template-builder-css', plugins_url( '../assets/template-builder.css', __FILE__ ) );
wp_enqueue_script( 'wa-template-builder-js', plugins_url( '../assets/template-builder.js', __FILE__ ), array( 'jquery' ), null, true );
?>

<div class="wa-template-wrap" style="padding:0; margin:0;" data-hook="<?php echo esc_attr($hook_type); ?>">
    <input type="hidden" class="wa_current_hook" value="<?php echo esc_attr($hook_type); ?>">

    <div class="wa-builder-container" id="wa-builder-<?php echo esc_attr($hook_type); ?>" style="display:flex; gap:20px;">
        <!-- Left Side: Form Elements -->
        <div class="wa-builder-form" style="flex:1;">
            <div style="display:flex; justify-content:space-between; margin-bottom:10px;">
                <label><strong>Template Builder</strong></label>
                <span style="color:green; font-size:11px;">Meta Status: approved</span>
            </div>

            <div class="wa-form-row" style="margin-bottom:15px;">
                <label style="display:block; margin-bottom:5px;">Header Type</label>
                <select class="regular-text"><option>Text</option></select>
            </div>

            <div class="wa-form-row" style="margin-bottom:15px;">
                <label style="display:block; margin-bottom:5px;">Header Text</label>
                <textarea class="wa-bind-header" rows="2" style="width:100%;">Your Order is Shipped!</textarea>
            </div>

            <div class="wa-form-row" style="margin-bottom:15px;">
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <label style="display:block; margin-bottom:5px;">Message Body</label>
                    <button type="button" class="button button-small">{{ Insert Variable }}</button>
                </div>
                <!-- Dynamic body text based on hook -->
                <textarea class="wa-bind-body" rows="6" style="width:100%;">Hi {{order.customer_firstname}}, your order #{{order.increment_id}} is on its way!</textarea>
                <span class="description" style="font-size:11px; display:block; margin-top:5px;">Place cursor in text area and click a variable from the popup above to insert it.</span>
            </div>

            <div class="wa-form-row" style="margin-bottom:15px;">
                <label style="display:block; margin-bottom:5px;">Footer</label>
                <textarea class="wa-bind-footer" rows="2" style="width:100%;">Track your package for updates.</textarea>
            </div>

            <div class="wa-form-row" style="margin-bottom:20px;">
                <label style="display:block; margin-bottom:5px;">Enable Buttons</label>
                <select><option>Yes</option></select>
                <div style="margin-top:10px; display:flex; gap:10px;">
                    <select><option>URL Button</option></select>
                    <input type="text" value="View Order" style="width:120px;">
                    <input type="text" value="https://store.local/sales/order/view" style="flex:1;">
                    <button class="button">Remove</button>
                </div>
            </div>

            <button class="button button-primary" style="background:#ea5c0b; border:none;">Save Template</button>
        </div>

        <!-- Right Side: iPhone Live Preview -->
        <div class="wa-live-preview" style="width:300px; flex-shrink:0;">
            <div style="border:1px solid #ddd; background:#f9f9f9; padding:15px; border-radius:30px; box-shadow:0 10px 20px rgba(0,0,0,0.1); height:500px;">
                <div style="background:#e5ddd5; height:100%; border-radius:15px; padding:10px; overflow-y:auto; font-family:Helvetica, sans-serif;">
                    <div style="background:#075e54; color:#fff; padding:10px; border-radius:10px 10px 0 0; font-weight:bold; text-align:center;">
                        WhatsApp Preview
                    </div>
                    <div style="background:#fff; margin-top:10px; padding:10px; border-radius:5px 5px 5px 0; box-shadow:0 1px 1px rgba(0,0,0,0.1);">
                        <strong class="wa-preview-header" style="display:block; margin-bottom:5px; font-size:14px;">Your Order is Shipped!</strong>
                        <p class="wa-preview-body" style="font-size:13px; margin:0 0 5px 0; line-height:1.4;">Hi Zubair, your order #10001 is on its way!</p>
                        <span class="wa-preview-footer" style="font-size:11px; color:#999; display:block; margin-bottom:10px;">Track your package for updates.</span>
                        <div style="border-top:1px solid #eee; text-align:center; padding-top:8px;">
                            <a href="#" style="color:#00a884; font-size:13px; text-decoration:none; display:flex; align-items:center; justify-content:center;">
                                <span style="margin-right:5px;">🔗</span> View Order
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
