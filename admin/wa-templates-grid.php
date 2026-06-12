<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Fetch templates from database
global $wpdb;
$table_name = $wpdb->prefix . 'azguards_whatsapp_templates';

// Ensure table exists
if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) !== $table_name ) {
    WA_Database::create_tables();
}

$templates = $wpdb->get_results( "SELECT * FROM $table_name ORDER BY created_at DESC", ARRAY_A );
$record_count = count( $templates );
?>
<div class="wrap" style="background:#fff; padding:20px 30px; border:1px solid #ccc; font-family:sans-serif;">
    <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:2px solid #ea5c0b; padding-bottom:15px; margin-bottom:20px;">
        <h1 style="font-size:24px; color:#333; margin:0;">WhatsApp Templates</h1>
        <div>
            <a href="#" id="wa-sync-templates-btn" data-nonce="<?php echo wp_create_nonce( 'wa_sync_templates' ); ?>" style="text-decoration:none; color:#333; font-weight:bold; margin-right:20px;">Sync Templates</a>
            <a href="?page=wa-template-builder" class="button" style="background:#ea5c0b; color:#fff; border:none; padding:8px 20px; font-weight:bold; border-radius:3px;">Create Template</a>
        </div>
    </div>

    <!-- Toolbar Exact Match -->
    <div style="display:flex; justify-content:space-between; margin-bottom:10px;">
        <div>
            <select><option>Actions</option></select>
            <span style="margin-left:15px; color:#777;"><?php echo $record_count; ?> records found</span>
        </div>
        <div>
            <button class="button">Filters</button>
            <select><option>Default View</option></select>
            <select><option>Columns</option></select>
            <span style="margin-left:20px;">
                <select><option>50</option></select> per page
                <button class="button">&lt;</button>
                <input type="text" value="1" style="width:40px; text-align:center;"> of 1
                <button class="button">&gt;</button>
            </span>
        </div>
    </div>

    <table class="wp-list-table widefat fixed striped table-view-list" style="margin-top:10px; border:none;">
        <thead style="background:#4d443c; color:#fff;">
            <tr>
                <td style="width:40px; text-align:center;"><input type="checkbox"></td>
                <th style="color:#fff; padding:10px;">Template Name</th>
                <th style="color:#fff; padding:10px;">Type</th>
                <th style="color:#fff; padding:10px;">Category</th>
                <th style="color:#fff; padding:10px;">Language Code</th>
                <th style="color:#fff; padding:10px;">Status</th>
                <th style="color:#fff; padding:10px;">Created At</th>
                <th style="color:#fff; padding:10px;">Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if ( ! empty( $templates ) ) : ?>
                <?php foreach($templates as $tmp): ?>
                <tr>
                    <td style="text-align:center;"><input type="checkbox"></td>
                    <td style="padding:10px; vertical-align:middle; color:#0b6a9c;"><?php echo esc_html($tmp['template_name']); ?></td>
                    <td style="padding:10px; vertical-align:middle;"><?php echo esc_html($tmp['template_type']); ?></td>
                    <td style="padding:10px; vertical-align:middle;"><?php echo esc_html($tmp['category']); ?></td>
                    <td style="padding:10px; vertical-align:middle;"><?php echo esc_html($tmp['language']); ?></td>
                    <td style="padding:10px; vertical-align:middle;"><?php echo esc_html($tmp['status']); ?></td>
                    <td style="padding:10px; vertical-align:middle;"><?php echo esc_html($tmp['created_at']); ?></td>
                    <td style="padding:10px; vertical-align:middle; color:#0b6a9c;">
                        Select ▼
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="8" style="text-align:center; padding:20px;"><?php _e( 'No templates found. Please click "Sync Templates" to fetch from API.', 'whatsapp-connector' ); ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
