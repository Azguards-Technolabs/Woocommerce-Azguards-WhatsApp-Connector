<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;
$table    = $wpdb->prefix . 'azguards_whatsapp_campaigns';
$tpl_tbl  = $wpdb->prefix . 'azguards_whatsapp_templates';

// Handle Actions
if ( ! empty( $_REQUEST['bulk_action'] ) && ! empty( $_REQUEST['campaign_ids'] ) && is_array( $_REQUEST['campaign_ids'] ) ) {
    $ids      = array_map( 'intval', $_REQUEST['campaign_ids'] );
    $action   = sanitize_text_field( $_REQUEST['bulk_action'] );
    $api_base = rtrim( get_option( 'wa_template_api_url', 'https://whatatalk-api.azguardstech.com' ), '/' );
    
    if ( ! function_exists( 'wa_get_valid_token' ) ) {
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/wc-templates.php';
    }
    $token = wa_get_valid_token();

    foreach ( $ids as $campaign_id ) {
        $camp = $wpdb->get_row( $wpdb->prepare( "SELECT scheduler_id FROM $table WHERE campaign_id = %d", $campaign_id ) );
        $scheduler_id = $camp ? $camp->scheduler_id : null;

        if ( 'delete' === $action ) {
            if ( $scheduler_id ) {
                $url = $api_base . '/scheduler-service/api/v1/schedule/' . $scheduler_id;
                wp_remote_request( $url, [
                    'method'  => 'DELETE',
                    'headers' => [ 'Authorization' => 'Bearer ' . $token ],
                    'timeout' => 15,
                ] );
            }
            $wpdb->query( $wpdb->prepare( "DELETE FROM $table WHERE campaign_id = %d", $campaign_id ) );
        } elseif ( 'pause' === $action ) {
            if ( $scheduler_id ) {
                $url = $api_base . '/scheduler-service/api/v1/schedule/' . $scheduler_id;
                $payload = [ 'status' => 'PAUSED' ];
                wp_remote_request( $url, [
                    'method'  => 'PUT',
                    'headers' => [ 'Authorization' => 'Bearer ' . $token, 'Content-Type' => 'application/json' ],
                    'body'    => wp_json_encode( $payload ),
                    'timeout' => 15,
                ] );
                $wpdb->query( $wpdb->prepare( "UPDATE $table SET status='PAUSED' WHERE campaign_id = %d", $campaign_id ) );
            }
        } elseif ( 'resume' === $action ) {
            if ( $scheduler_id ) {
                $url = $api_base . '/scheduler-service/api/v1/schedule/' . $scheduler_id;
                $payload = [ 'status' => 'RESUME' ];
                wp_remote_request( $url, [
                    'method'  => 'PUT',
                    'headers' => [ 'Authorization' => 'Bearer ' . $token, 'Content-Type' => 'application/json' ],
                    'body'    => wp_json_encode( $payload ),
                    'timeout' => 15,
                ] );
                $wpdb->query( $wpdb->prepare( "UPDATE $table SET status='SCHEDULED' WHERE campaign_id = %d", $campaign_id ) );
            }
        } elseif ( 'retry' === $action ) {
            $wpdb->query( $wpdb->prepare( "UPDATE $table SET failed_count=0, status='SCHEDULED' WHERE campaign_id = %d", $campaign_id ) );
        }
    }
}

$search_query = isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : '';
$where = 'WHERE 1=1';
if ( $search_query !== '' ) {
    $where .= $wpdb->prepare( " AND (c.campaign_name LIKE %s OR t.template_name LIKE %s)", '%' . $wpdb->esc_like( $search_query ) . '%', '%' . $wpdb->esc_like( $search_query ) . '%' );
}

$campaigns = $wpdb->get_results( "
    SELECT c.*, t.template_name
    FROM $table c
    LEFT JOIN $tpl_tbl t ON c.template_entity_id = t.entity_id
    $where
    ORDER BY c.created_at DESC
" );

$total = count( $campaigns );
?>
<div class="wrap">
    <h1 class="wp-heading-inline">WhatTack Campaigns</h1>
    <a href="?page=wa-campaign-edit" class="page-title-action">Create Campaign</a>
    <a href="#" id="wa_sync_campaigns_btn" class="page-title-action">Get All Campaign</a>
    <hr class="wp-header-end">

    <form method="get" id="posts-filter">
        <input type="hidden" name="page" value="wa-campaigns" />

        <p class="search-box">
            <label class="screen-reader-text" for="post-search-input">Search Campaigns:</label>
            <input type="search" id="post-search-input" name="s" value="<?php echo esc_attr( $search_query ); ?>">
            <input type="submit" id="search-submit" class="button" value="Search Campaigns">
        </p>

        <div class="tablenav top">
            <div class="alignleft actions bulkactions">
                <label for="bulk-action-selector-top" class="screen-reader-text">Select bulk action</label>
                <select name="bulk_action" id="bulk-action-selector-top">
                    <option value="">Bulk actions</option>
                    <option value="pause">Pause</option>
                    <option value="resume">Resume</option>
                    <option value="retry">Retry</option>
                    <option value="delete">Delete</option>
                </select>
                <input type="submit" id="doaction" class="button action" value="Apply">
            </div>
            <div class="tablenav-pages">
                <span class="displaying-num"><?php echo esc_html( $total ); ?> items</span>
            </div>
        </div>

        <table class="wp-list-table widefat fixed striped table-view-list">
            <thead>
                <tr>
                    <td id="cb" class="manage-column column-cb check-column"><input id="cb-select-all-1" type="checkbox"></td>
                    <th scope="col" class="manage-column column-id">ID</th>
                    <th scope="col" class="manage-column column-title column-primary">Campaign Name</th>
                    <th scope="col" class="manage-column column-template">Template</th>
                    <th scope="col" class="manage-column column-groups">Customer Groups</th>
                    <th scope="col" class="manage-column column-date">Schedule Time</th>
                    <th scope="col" class="manage-column column-status">Status</th>
                </tr>
            </thead>
            <tbody id="the-list">
                <?php if ( empty( $campaigns ) ): ?>
                <tr class="no-items"><td class="colspanchange" colspan="7">No campaigns found.</td></tr>
                <?php else: ?>
                <?php foreach ( $campaigns as $camp ): ?>
                <tr id="campaign-<?php echo esc_attr( $camp->campaign_id ); ?>">
                    <th scope="row" class="check-column">
                        <input type="checkbox" name="campaign_ids[]" value="<?php echo esc_attr( $camp->campaign_id ); ?>">
                    </th>
                    <td class="column-id"><?php echo esc_html( $camp->campaign_id ); ?></td>
                    <td class="column-title column-primary" data-colname="Campaign Name">
                        <strong>
                            <?php if ( $camp->sent_count == 0 ): ?>
                                <a class="row-title" href="?page=wa-campaign-edit&id=<?php echo esc_attr( $camp->campaign_id ); ?>"><?php echo esc_html( $camp->campaign_name ); ?></a>
                            <?php else: ?>
                                <span class="row-title"><?php echo esc_html( $camp->campaign_name ); ?></span>
                            <?php endif; ?>
                        </strong>
                        <div class="row-actions">
                            <?php if ( $camp->sent_count == 0 ): ?>
                                <span class="edit"><a href="?page=wa-campaign-edit&id=<?php echo esc_attr( $camp->campaign_id ); ?>">Edit</a> | </span>
                            <?php endif; ?>
                            <?php if ( strtolower($camp->status) === 'paused' ): ?>
                                <span class="resume"><a href="#" onclick="jQuery(this).closest('form').find('select[name=bulk_action]').val('resume'); jQuery(this).closest('tr').find('input[type=checkbox]').prop('checked', true); jQuery(this).closest('form').submit(); return false;">Resume</a> | </span>
                            <?php else: ?>
                                <span class="pause"><a href="#" onclick="jQuery(this).closest('form').find('select[name=bulk_action]').val('pause'); jQuery(this).closest('tr').find('input[type=checkbox]').prop('checked', true); jQuery(this).closest('form').submit(); return false;">Pause</a> | </span>
                            <?php endif; ?>
                            <?php if ( $camp->failed_count > 0 ): ?>
                                <span class="retry"><a href="#" onclick="jQuery(this).closest('form').find('select[name=bulk_action]').val('retry'); jQuery(this).closest('tr').find('input[type=checkbox]').prop('checked', true); jQuery(this).closest('form').submit(); return false;">Retry</a> | </span>
                            <?php endif; ?>
                            <span class="trash"><a href="#" class="submitdelete" onclick="if(confirm('Delete this campaign?')) { jQuery(this).closest('form').find('select[name=bulk_action]').val('delete'); jQuery(this).closest('tr').find('input[type=checkbox]').prop('checked', true); jQuery(this).closest('form').submit(); } return false;">Delete</a></span>
                        </div>
                    </td>
                    <td class="column-template" data-colname="Template"><?php echo esc_html( $camp->template_name ?: '—' ); ?></td>
                    <td class="column-groups" data-colname="Customer Groups"><?php echo esc_html( $camp->target_type == 'all_customers' ? 'General' : 'Retailer' ); ?></td>
                    <td class="column-date" data-colname="Schedule Time"><?php echo esc_html( date('M d, Y g:i a', strtotime($camp->schedule_time)) ); ?></td>
                    <td class="column-status" data-colname="Status">
                        <?php 
                        $raw_status = strtoupper($camp->status);
                        $status_class = 'status-processing'; // default

                        if ( in_array( $raw_status, ['PAUSED', 'DELETED', 'FAILED'] ) ) {
                            $status_class = 'status-on-hold';
                        } elseif ( in_array( $raw_status, ['COMPLETED', 'APPROVED'] ) ) {
                            $status_class = 'status-processing';
                        }

                        echo '<mark class="' . $status_class . '"><span>' . esc_html( $raw_status ) . '</span></mark>';
                        ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </form>
</div>
<style>
/* Mimic WooCommerce status marks for consistency */
mark.status-on-hold { background: #f8dda7; color: #94660c; border-radius: 4px; padding: 2px 6px; font-weight:600; font-size:12px; }
mark.status-processing { background: #c6e1c6; color: #5b841b; border-radius: 4px; padding: 2px 6px; font-weight:600; font-size:12px; }
</style>
<script>
document.getElementById('cb-select-all-1').addEventListener('change', function() {
    document.querySelectorAll('input[name="campaign_ids[]"]').forEach(cb => cb.checked = this.checked);
});

// Sync campaigns from Meta API
var waCampSyncNonce = <?php echo wp_json_encode( wp_create_nonce( 'wa_sync_campaigns' ) ); ?>;
jQuery('#wa_sync_campaigns_btn').on('click', function(e) {
    e.preventDefault();
    var $btn = jQuery(this);
    $btn.text('Syncing...');
    jQuery.post(ajaxurl, {
        action:   'wa_sync_campaigns',
        security: waCampSyncNonce
    }, function(resp) {
        if (resp.success) {
            alert('✅ ' + (resp.data || 'Sync complete!'));
            location.reload();
        } else {
            alert('❌ Sync error: ' + (resp.data || 'Unknown error'));
            $btn.text('Get All Campaign');
        }
    }).fail(function() {
        alert('❌ Network error during sync.');
        $btn.text('Get All Campaign');
    });
});
</script>




