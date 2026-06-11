<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;
$table_name = $wpdb->prefix . 'azguards_whatsapp_templates';

// If table doesn't exist yet, we prevent fatals
if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) != $table_name ) {
    $templates = [];
} else {
    $templates = $wpdb->get_results( "SELECT * FROM $table_name ORDER BY created_at DESC", ARRAY_A );
}
?>
<div class="wrap" style="background:#fff; padding:20px 30px; border:1px solid #ccc; font-family:sans-serif;">
    <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:2px solid #ea5c0b; padding-bottom:15px; margin-bottom:20px;">
        <h1 style="font-size:24px; color:#333; margin:0;">WhatsApp Templates</h1>
        <div>
            <a href="#" id="wa_sync_templates_btn" style="text-decoration:none; color:#333; font-weight:bold; margin-right:20px;">Sync Templates</a>
            <a href="?page=wa-template-builder" class="button" style="background:#ea5c0b; color:#fff; border:none; padding:8px 20px; font-weight:bold; border-radius:3px;">Create Template</a>
        </div>
    </div>

    <!-- Toolbar Exact Match -->
    <div style="display:flex; justify-content:space-between; margin-bottom:10px;">
        <div>
            <select><option>Actions</option></select>
            <span style="margin-left:15px; color:#777;"><?php echo count($templates); ?> records found</span>
        </div>
    </div>

    <table class="wp-list-table widefat fixed striped table-view-list" style="margin-top:10px; border:none;">
        <thead style="background:#4d443c; color:#fff;">
            <tr>
                <td style="width:40px; text-align:center;"><input type="checkbox"></td>
                <th style="color:#fff; padding:10px;">Template Name</th>
                <th style="color:#fff; padding:10px;">Type</th>
                <th style="color:#fff; padding:10px;">Format</th>
                <th style="color:#fff; padding:10px;">Status</th>
                <th style="color:#fff; padding:10px;">Created At</th>
            </tr>
        </thead>
        <tbody>
            <?php if ( empty( $templates ) ) : ?>
                <tr><td colspan="6" style="text-align:center; padding:15px;">No templates found. Please Sync Templates.</td></tr>
            <?php else : ?>
                <?php foreach($templates as $tmp): ?>
                <tr>
                    <td style="text-align:center;"><input type="checkbox"></td>
                    <td style="padding:10px; vertical-align:middle; color:#0b6a9c;"><?php echo esc_html($tmp['template_name']); ?></td>
                    <td style="padding:10px; vertical-align:middle;"><?php echo esc_html($tmp['template_type']); ?></td>
                    <td style="padding:10px; vertical-align:middle;"><?php echo esc_html($tmp['header_format']); ?></td>
                    <td style="padding:10px; vertical-align:middle;"><?php echo esc_html($tmp['status']); ?></td>
                    <td style="padding:10px; vertical-align:middle;"><?php echo esc_html($tmp['created_at']); ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
jQuery(document).ready(function($) {
    $('#wa_sync_templates_btn').on('click', function(e) {
        e.preventDefault();
        var $btn = $(this);
        $btn.text('Syncing...').css('pointer-events', 'none');
        
        $.post(ajaxurl, {
            action: 'wa_sync_templates'
        }, function(response) {
            $btn.text('Sync Templates').css('pointer-events', 'auto');
            if (response.success) {
                alert(response.data.message);
                location.reload();
            } else {
                alert('Error: ' + response.data.message);
            }
        }).fail(function() {
            $btn.text('Sync Templates').css('pointer-events', 'auto');
            alert('Failed to connect to the server.');
        });
    });
});
</script>
