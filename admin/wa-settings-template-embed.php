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
                <label style="display:block; margin-bottom:5px;">Template Name</label>
                <input type="text" name="wa_template_<?php echo esc_attr($hook_type); ?>_template_name" value="<?php echo esc_attr($template_name); ?>" style="width:100%;">
            </div>

            <div style="display:flex; gap:15px; margin-bottom:15px;">
                <div class="wa-form-row" style="flex:1;">
                    <label style="display:block; margin-bottom:5px;">Category</label>
                    <select name="wa_template_<?php echo esc_attr($hook_type); ?>_category" style="width:100%;">
                        <option value="Utility" <?php selected($category, 'Utility'); ?>>Utility</option>
                        <option value="Marketing" <?php selected($category, 'Marketing'); ?>>Marketing</option>
                        <option value="Authentication" <?php selected($category, 'Authentication'); ?>>Authentication</option>
                    </select>
                </div>
                <div class="wa-form-row" style="flex:1;">
                    <label style="display:block; margin-bottom:5px;">Language</label>
                    <input type="text" name="wa_template_<?php echo esc_attr($hook_type); ?>_language" value="<?php echo esc_attr($language); ?>" style="width:100%;">
                </div>
            </div>

            <div class="wa-form-row" style="margin-bottom:15px;">
                <label style="display:block; margin-bottom:5px;">Header Type</label>
                <select name="wa_template_<?php echo esc_attr($hook_type); ?>_header_type" class="regular-text" style="width:100%;">
                    <option value="text" <?php selected($header_type, 'text'); ?>>Text</option>
                </select>
            </div>

            <div class="wa-form-row" style="margin-bottom:15px;">
                <label style="display:block; margin-bottom:5px;">Header Text</label>
                <textarea name="wa_template_<?php echo esc_attr($hook_type); ?>_header_text" class="wa-bind-header" rows="2" style="width:100%;"><?php echo esc_textarea($header_text); ?></textarea>
            </div>

            <div class="wa-form-row" style="margin-bottom:15px;">
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <label style="display:block; margin-bottom:5px;">Message Body</label>
                    <button type="button" class="button button-small">{{ Insert Variable }}</button>
                </div>
                <textarea name="wa_template_<?php echo esc_attr($hook_type); ?>_body_template" class="wa-bind-body" rows="6" style="width:100%;"><?php echo esc_textarea($body_template); ?></textarea>
                <span class="description" style="font-size:11px; display:block; margin-top:5px;">Place cursor in text area and click a variable from the popup above to insert it.</span>
            </div>

            <div class="wa-form-row" style="margin-bottom:15px;">
                <label style="display:block; margin-bottom:5px;">Footer</label>
                <textarea name="wa_template_<?php echo esc_attr($hook_type); ?>_footer_template" class="wa-bind-footer" rows="2" style="width:100%;"><?php echo esc_textarea($footer_template); ?></textarea>
            </div>

            <div class="wa-form-row" style="margin-bottom:20px;">
                <label style="display:block; margin-bottom:5px;">Enable Buttons</label>
                <select><option>Yes</option></select>
                <div id="wa-buttons-container-<?php echo esc_attr($hook_type); ?>" style="margin-top:10px;">
                    <?php if (!empty($buttons)): ?>
                        <?php foreach ($buttons as $button): ?>
                            <div class="wa-button-row" style="margin-bottom:10px; display:flex; gap:10px;">
                                <select style="width:120px;"><option><?php echo esc_html($button['type']); ?></option></select>
                                <input type="text" value="<?php echo esc_attr($button['text']); ?>" style="width:120px;">
                                <input type="text" value="<?php echo esc_attr($button['button_url'] ?? ''); ?>" style="flex:1;">
                                <button type="button" class="button">Remove</button>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="margin-top:10px; display:flex; gap:10px;">
                            <select><option>URL Button</option></select>
                            <input type="text" placeholder="Button Text" style="width:120px;">
                            <input type="text" placeholder="URL" style="flex:1;">
                            <button type="button" class="button">Remove</button>
                        </div>
                    <?php endif; ?>
                </div>
                <!-- Hidden input to store JSON for saving -->
                <input type="hidden" name="wa_template_<?php echo esc_attr($hook_type); ?>_buttons_json" value="<?php echo esc_attr($buttons_json); ?>">
            </div>

            <p class="submit" style="padding:0; margin-top:20px;">
                <input type="submit" name="save" class="button button-primary" value="Save Template Settings" style="background:#ea5c0b; border:none;">
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
