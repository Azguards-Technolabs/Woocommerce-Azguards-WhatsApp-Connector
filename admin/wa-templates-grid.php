<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Fetch templates from database
global $wpdb;
$table_name = $wpdb->prefix . 'azguards_whatsapp_templates';
$templates = $wpdb->get_results( "SELECT * FROM $table_name ORDER BY created_at DESC", ARRAY_A );
// Handle Actions
if ( ! empty( $_REQUEST['action'] ) && ! empty( $_REQUEST['template_ids'] ) && is_array( $_REQUEST['template_ids'] ) ) {
    $ids      = array_map( 'intval', $_REQUEST['template_ids'] );
    $action   = sanitize_text_field( $_REQUEST['action'] );
    $api_base = rtrim( get_option( 'wa_template_api_url', 'https://whatatalk-api.azguardstech.com' ), '/' );
    
    if ( ! function_exists( 'wa_get_valid_token' ) ) {
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/wc-templates.php';
    }
    $token = wa_get_valid_token();

    foreach ( $ids as $entity_id ) {
        $tpl = $wpdb->get_row( $wpdb->prepare( "SELECT template_id FROM $table_name WHERE entity_id = %d", $entity_id ) );

        if ( 'delete' === $action ) {
            if ( $tpl && $tpl->template_id ) {
                $business_id = get_option( 'wa_business_id', '' );
                $user_id     = get_option( 'wa_user_id', '' );
                $url = $api_base . '/meta-service/v1/template/' . urlencode($tpl->template_id);
                wp_remote_request( $url, [
                    'method'  => 'DELETE',
                    'headers' => [ 'Authorization' => 'Bearer ' . $token, 'businessId' => $business_id, 'userId' => $user_id ],
                    'timeout' => 15,
                ] );
            }
            $wpdb->query( $wpdb->prepare( "DELETE FROM $table_name WHERE entity_id = %d", $entity_id ) );
        }
    }
}

$templates = $wpdb->get_results( "SELECT * FROM $table_name ORDER BY created_at DESC", ARRAY_A );
$record_count = count( $templates );
?>
<div class="wrap">
    <h1 class="wp-heading-inline">WhatTack Templates</h1>
    <a href="?page=wa-template-builder" class="page-title-action">Create Template</a>
    <a href="#" id="wa-sync-templates-btn" data-nonce="<?php echo wp_create_nonce( 'wa_sync_templates' ); ?>" class="page-title-action">Sync Templates</a>
    <hr class="wp-header-end">
    
    <form method="get" id="posts-filter">
        <input type="hidden" name="page" value="wa-templates-grid" />

        <div class="tablenav top">
            <div class="alignleft actions bulkactions">
                <select name="action" id="bulk-action-selector-top">
                    <option value="-1">Actions</option>
                    <option value="delete">Delete</option>
                </select>
                <input type="submit" id="doaction" class="button action" value="Apply">
            </div>
        <div class="tablenav-pages">
            <span class="displaying-num"><?php echo esc_html( $record_count ); ?> items</span>
        </div>
    </div>

    <table class="wp-list-table widefat fixed striped table-view-list">
        <thead>
            <tr>
                <td id="cb" class="manage-column column-cb check-column"><input id="cb-select-all-1" type="checkbox"></td>
                <th scope="col" class="manage-column column-title column-primary">Template Name</th>
                <th scope="col" class="manage-column column-type">Type</th>
                <th scope="col" class="manage-column column-category">Category</th>
                <th scope="col" class="manage-column column-language">Language Code</th>
                <th scope="col" class="manage-column column-status">Status</th>
                <th scope="col" class="manage-column column-date">Created At</th>
                <th scope="col" class="manage-column column-action">Action</th>
            </tr>
        </thead>
        <tbody id="the-list">
            <?php if ( ! empty( $templates ) ) : ?>
                <?php foreach($templates as $tmp): ?>
                <tr id="template-<?php echo esc_attr($tmp['entity_id'] ?? ''); ?>">
                    <th scope="row" class="check-column"><input type="checkbox" name="template_ids[]" value="<?php echo esc_attr($tmp['entity_id'] ?? ''); ?>"></th>
                    <td class="column-title column-primary" data-colname="Template Name">
                        <strong><a href="?page=wa-template-builder&id=<?php echo esc_attr($tmp['entity_id'] ?? ''); ?>"><?php echo esc_html($tmp['template_name']); ?></a></strong>
                        <div class="row-actions">
                            <span class="edit"><a href="?page=wa-template-builder&id=<?php echo esc_attr( $tmp['entity_id'] ?? '' ); ?>">Edit</a> | </span>
                            <span class="trash"><a href="#" class="submitdelete" onclick="if(confirm('Delete this template?')) { jQuery(this).closest('form').find('select[name=action]').val('delete'); jQuery(this).closest('tr').find('input[type=checkbox]').prop('checked', true); jQuery(this).closest('form').submit(); } return false;">Delete</a></span>
                        </div>
                    </td>
                    <td class="column-type" data-colname="Type"><?php echo esc_html($tmp['template_type']); ?></td>
                    <td class="column-category" data-colname="Category"><?php echo esc_html($tmp['category']); ?></td>
                    <td class="column-language" data-colname="Language Code"><?php echo esc_html($tmp['language']); ?></td>
                    <td class="column-status" data-colname="Status">
                        <?php 
                        $status_class = (strtolower($tmp['status']) === 'approved') ? 'status-processing' : 'status-on-hold';
                        echo '<mark class="' . $status_class . '"><span>' . esc_html($tmp['status']) . '</span></mark>';
                        ?>
                    </td>
                    <td class="column-date" data-colname="Created At"><?php echo esc_html(date('M d, Y g:i a', strtotime($tmp['created_at']))); ?></td>
                    <td class="column-action" data-colname="Action">
                        <a href="?page=wa-template-builder&hook=<?php echo esc_attr($tmp['entity_id'] ?? ''); ?>">Select ▼</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr class="no-items">
                    <td class="colspanchange" colspan="8"><?php _e( 'No templates found. Please click "Sync Templates" to fetch from API.', 'whatsapp-connector' ); ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    </form>
</div>
<style>
mark.status-on-hold { background: #f8dda7; color: #94660c; border-radius: 4px; padding: 2px 6px; font-weight:600; font-size:12px; }
mark.status-processing { background: #c6e1c6; color: #5b841b; border-radius: 4px; padding: 2px 6px; font-weight:600; font-size:12px; }
</style>
<script>
document.getElementById('cb-select-all-1').addEventListener('change', function() {
    document.querySelectorAll('input[name="template_ids[]"]').forEach(cb => cb.checked = this.checked);
});
</script>
