<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;
$table_campaigns = $wpdb->prefix . 'azguards_whatsapp_campaigns';
$table_templates = $wpdb->prefix . 'azguards_whatsapp_templates';

// If table doesn't exist yet, we prevent fatals
if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_campaigns'" ) != $table_campaigns ) {
    $campaigns = [];
} else {
    $campaigns = $wpdb->get_results( "
        SELECT c.*, t.template_name 
        FROM $table_campaigns c
        LEFT JOIN $table_templates t ON c.template_entity_id = t.entity_id
        ORDER BY c.created_at DESC
    ", ARRAY_A );
}
?>
<div class="wrap" style="background:#fff; padding:20px 30px; border:1px solid #ccc; font-family:sans-serif;">
    <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:2px solid #ea5c0b; padding-bottom:15px; margin-bottom:20px;">
        <h1 style="font-size:24px; color:#333; margin:0;">WhatsApp Campaigns</h1>
        <div>
            <a href="?page=wa-campaign-edit" class="button" style="background:#ea5c0b; color:#fff; border:none; padding:8px 20px; font-weight:bold; border-radius:3px;">Create Campaign</a>
        </div>
    </div>

    <div style="display:flex; justify-content:space-between; margin-bottom:10px;">
        <div>
            <select><option>Actions</option></select>
            <span style="margin-left:15px; color:#777;"><?php echo count($campaigns); ?> records found</span>
        </div>
    </div>

    <table class="wp-list-table widefat fixed striped table-view-list" style="margin-top:10px; border:none;">
        <thead style="background:#4d443c; color:#fff;">
            <tr>
                <td style="width:40px; text-align:center;"><input type="checkbox"></td>
                <th style="color:#fff; padding:10px;">ID</th>
                <th style="color:#fff; padding:10px;">Campaign Name</th>
                <th style="color:#fff; padding:10px;">Template</th>
                <th style="color:#fff; padding:10px;">Customer Groups</th>
                <th style="color:#fff; padding:10px;">Schedule Time</th>
                <th style="color:#fff; padding:10px;">Status</th>
                <th style="color:#fff; padding:10px;">Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if ( empty( $campaigns ) ) : ?>
                <tr><td colspan="8" style="text-align:center; padding:15px;">No campaigns found.</td></tr>
            <?php else : ?>
                <?php foreach($campaigns as $camp): ?>
                <tr>
                    <td style="text-align:center;"><input type="checkbox"></td>
                    <td style="padding:10px; vertical-align:middle;"><?php echo esc_html($camp['campaign_id']); ?></td>
                    <td style="padding:10px; vertical-align:middle; color:#0073aa;"><?php echo esc_html($camp['campaign_name']); ?></td>
                    <td style="padding:10px; vertical-align:middle;"><?php echo esc_html($camp['template_name']); ?></td>
                    <td style="padding:10px; vertical-align:middle;"><?php echo esc_html($camp['target_type']); ?></td>
                    <td style="padding:10px; vertical-align:middle;"><?php echo esc_html($camp['schedule_time']); ?></td>
                    <td style="padding:10px; vertical-align:middle;"><?php echo esc_html($camp['status']); ?></td>
                    <td style="padding:10px; vertical-align:middle; color:#0073aa;">
                        <a href="?page=wa-campaign-edit&id=<?php echo $camp['campaign_id']; ?>">Edit</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
