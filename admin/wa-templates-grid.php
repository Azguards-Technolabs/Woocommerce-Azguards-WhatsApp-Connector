<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Fetch templates from database
global $wpdb;
$table_name = $wpdb->prefix . 'azguards_whatsapp_templates';

// Ensure table exists
if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) !== $table_name ) {
    if ( class_exists( 'WA_Database' ) ) {
        WA_Database::create_tables();
    }
}

// Handle Pagination, Search, Filter and Sort
$per_page     = 20;
$current_page = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;
$offset       = ( $current_page - 1 ) * $per_page;

$search   = isset( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : '';
$status   = isset( $_GET['status'] ) ? sanitize_text_field( $_GET['status'] ) : '';
$category = isset( $_GET['category'] ) ? sanitize_text_field( $_GET['category'] ) : '';
$language = isset( $_GET['language'] ) ? sanitize_text_field( $_GET['language'] ) : '';

$allowed_orderby = [ 'entity_id', 'template_id', 'template_name', 'template_type', 'category', 'language', 'status', 'created_at', 'last_synced_at' ];
$orderby = isset( $_GET['orderby'] ) && in_array( $_GET['orderby'], $allowed_orderby ) ? $_GET['orderby'] : 'created_at';
$order   = isset( $_GET['order'] ) && strtolower( $_GET['order'] ) === 'asc' ? 'ASC' : 'DESC';

$where = [ '1=1' ];
if ( ! empty( $search ) ) {
    $where[] = $wpdb->prepare( "(template_name LIKE %s OR template_id LIKE %s)", '%' . $wpdb->esc_like( $search ) . '%', '%' . $wpdb->esc_like( $search ) . '%' );
}
if ( ! empty( $status ) ) {
    $where[] = $wpdb->prepare( "status = %s", $status );
}
if ( ! empty( $category ) ) {
    $where[] = $wpdb->prepare( "category = %s", $category );
}
if ( ! empty( $language ) ) {
    $where[] = $wpdb->prepare( "language = %s", $language );
}

$where_sql = implode( ' AND ', $where );

$total_items = $wpdb->get_var( "SELECT COUNT(*) FROM $table_name WHERE $where_sql" );
$templates   = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table_name WHERE $where_sql ORDER BY $orderby $order LIMIT %d OFFSET %d", $per_page, $offset ), ARRAY_A );

// Get unique values for filters
$statuses   = $wpdb->get_col( "SELECT DISTINCT status FROM $table_name WHERE status IS NOT NULL AND status != ''" );
$categories = $wpdb->get_col( "SELECT DISTINCT category FROM $table_name WHERE category IS NOT NULL AND category != ''" );
$languages  = $wpdb->get_col( "SELECT DISTINCT language FROM $table_name WHERE language IS NOT NULL AND language != ''" );

// Handle Actions
if ( ! empty( $_REQUEST['action'] ) && ! empty( $_REQUEST['template_ids'] ) && is_array( $_REQUEST['template_ids'] ) ) {
    $ids      = array_map( 'intval', $_REQUEST['template_ids'] );
    $action   = sanitize_text_field( $_REQUEST['action'] );
    $api_base = rtrim( get_option( 'wa_template_api_url', 'https://whatatalk-api.azguardstech.com' ), '/' );
    $business_id = get_option( 'wa_business_id', '' );
    $user_id     = get_option( 'wa_user_id', '' );

    if ( ! function_exists( 'wa_get_valid_token' ) ) {
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/wc-templates.php';
    }
    $token = wa_get_valid_token();

    $deleted_count = 0;
    $errors = [];

    foreach ( $ids as $entity_id ) {
        $tpl = $wpdb->get_row( $wpdb->prepare( "SELECT template_id, template_name FROM $table_name WHERE entity_id = %d", $entity_id ) );

        if ( 'delete' === $action ) {
            $api_error = false;
            if ( $tpl && $tpl->template_id ) {
                $url = $api_base . '/meta-service/v1/template/' . urlencode($tpl->template_id);

                error_log( "[WA Delete] Deleting template: " . $tpl->template_name . " (ID: " . $tpl->template_id . ")" );

                $response = wp_remote_request( $url, [
                    'method'  => 'DELETE',
                    'headers' => [ 'Authorization' => 'Bearer ' . $token, 'businessId' => $business_id, 'userId' => $user_id ],
                    'timeout' => 15,
                ] );

                $code = wp_remote_retrieve_response_code( $response );
                if ( is_wp_error( $response ) || ( $code >= 400 && $code !== 404 ) ) {
                    $msg = is_wp_error( $response ) ? $response->get_error_message() : wp_remote_retrieve_body( $response );
                    error_log( "[WA Delete] API Error ($code): " . $msg );
                    $errors[] = sprintf( __( 'API error deleting %s: %s', 'whatsapp-connector' ), $tpl->template_name, $msg );
                    $api_error = true;
                }
            }

            // Delete locally if API deletion was successful or if the template was only local (or already gone from API - 404)
            if ( ! $api_error ) {
                $wpdb->query( $wpdb->prepare( "DELETE FROM $table_name WHERE entity_id = %d", $entity_id ) );
                $deleted_count++;
            }
        }
    }

    if ( $deleted_count > 0 ) {
        echo '<div class="updated notice is-dismissible"><p>' . sprintf( _n( 'Successfully deleted %d template.', 'Successfully deleted %d templates.', $deleted_count, 'whatsapp-connector' ), $deleted_count ) . '</p></div>';
    }
    if ( ! empty( $errors ) ) {
        foreach ( $errors as $err ) {
            echo '<div class="error notice is-dismissible"><p>' . esc_html( $err ) . '</p></div>';
        }
    }
}

$record_count = count( $templates );
?>
<div class="wrap">
    <h1 class="wp-heading-inline">WhatTack Templates</h1>
    <a href="?page=wa-template-builder" class="page-title-action">Create Template</a>
    <a href="#" id="wa-sync-templates-btn" data-nonce="<?php echo wp_create_nonce( 'wa_sync_templates' ); ?>" class="page-title-action">Sync Templates</a>
    <hr class="wp-header-end">
    
    <form method="get" id="posts-filter">
        <input type="hidden" name="page" value="wa-templates-grid" />

        <p class="search-box">
            <label class="screen-reader-text" for="post-search-input">Search Templates:</label>
            <input type="search" id="post-search-input" name="s" value="<?php echo esc_attr($search); ?>">
            <input type="submit" id="search-submit" class="button" value="Search Templates">
        </p>

        <div class="tablenav top">
            <div class="alignleft actions bulkactions">
                <select name="action" id="bulk-action-selector-top">
                    <option value="-1">Actions</option>
                    <option value="delete">Delete</option>
                </select>
                <input type="submit" id="doaction" class="button action wa-bulk-delete" value="Apply">
            </div>

            <div class="alignleft actions">
                <select name="status" id="filter-by-status">
                    <option value="">All Statuses</option>
                    <?php foreach ( $statuses as $st ) : ?>
                        <option value="<?php echo esc_attr( $st ); ?>" <?php selected( $status, $st ); ?>><?php echo esc_html( ucfirst( strtolower( $st ) ) ); ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="category" id="filter-by-category">
                    <option value="">All Categories</option>
                    <?php foreach ( $categories as $cat ) : ?>
                        <option value="<?php echo esc_attr( $cat ); ?>" <?php selected( $category, $cat ); ?>><?php echo esc_html( ucfirst( strtolower( $cat ) ) ); ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="language" id="filter-by-language">
                    <option value="">All Languages</option>
                    <?php foreach ( $languages as $lang ) : ?>
                        <option value="<?php echo esc_attr( $lang ); ?>" <?php selected( $language, $lang ); ?>><?php echo esc_html( $lang ); ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="submit" name="filter_action" id="post-query-submit" class="button" value="Filter">
            </div>

            <div class="tablenav-pages">
                <span class="displaying-num"><?php echo esc_html( $total_items ); ?> items</span>
                <?php
                $page_links = paginate_links( array(
                    'base'      => add_query_arg( 'paged', '%#%' ),
                    'format'    => '',
                    'prev_text' => __( '&laquo;', 'text-domain' ),
                    'next_text' => __( '&raquo;', 'text-domain' ),
                    'total'     => ceil( $total_items / $per_page ),
                    'current'   => $current_page,
                ) );

                if ( $page_links ) {
                    echo '<span class="pagination-links">' . $page_links . '</span>';
                }
                ?>
            </div>
        </div>

    <?php
    $order_next = ( $order === 'ASC' ) ? 'desc' : 'asc';
    $base_url   = admin_url( 'admin.php?page=wa-templates-grid' );

    $sort_links = array(
        'template_name' => add_query_arg( array( 'orderby' => 'template_name', 'order' => $order_next ), $base_url ),
        'template_type' => add_query_arg( array( 'orderby' => 'template_type', 'order' => $order_next ), $base_url ),
        'category'      => add_query_arg( array( 'orderby' => 'category', 'order' => $order_next ), $base_url ),
        'language'      => add_query_arg( array( 'orderby' => 'language', 'order' => $order_next ), $base_url ),
        'status'        => add_query_arg( array( 'orderby' => 'status', 'order' => $order_next ), $base_url ),
        'created_at'    => add_query_arg( array( 'orderby' => 'created_at', 'order' => $order_next ), $base_url ),
    );

    function get_sort_class( $column, $current_orderby, $current_order ) {
        if ( $column === $current_orderby ) {
            return 'sorted ' . strtolower( $current_order );
        }
        return 'sortable desc';
    }
    ?>

    <table class="wp-list-table widefat fixed striped table-view-list">
        <thead>
            <tr>
                <td id="cb" class="manage-column column-cb check-column"><input id="cb-select-all-1" type="checkbox"></td>
                <th scope="col" class="manage-column column-id <?php echo get_sort_class( 'entity_id', $orderby, $order ); ?>">
                    <a href="<?php echo esc_url( add_query_arg( array( 'orderby' => 'entity_id', 'order' => $order_next ), $base_url ) ); ?>"><span>ID</span><span class="sorting-indicator"></span></a>
                </th>
                <th scope="col" class="manage-column column-title column-primary <?php echo get_sort_class( 'template_name', $orderby, $order ); ?>">
                    <a href="<?php echo esc_url( $sort_links['template_name'] ); ?>"><span>Template Name</span><span class="sorting-indicator"></span></a>
                </th>
                <th scope="col" class="manage-column column-type <?php echo get_sort_class( 'template_type', $orderby, $order ); ?>">
                    <a href="<?php echo esc_url( $sort_links['template_type'] ); ?>"><span>Type</span><span class="sorting-indicator"></span></a>
                </th>
                <th scope="col" class="manage-column column-category <?php echo get_sort_class( 'category', $orderby, $order ); ?>">
                    <a href="<?php echo esc_url( $sort_links['category'] ); ?>"><span>Category</span><span class="sorting-indicator"></span></a>
                </th>
                <th scope="col" class="manage-column column-language <?php echo get_sort_class( 'language', $orderby, $order ); ?>">
                    <a href="<?php echo esc_url( $sort_links['language'] ); ?>"><span>Language Code</span><span class="sorting-indicator"></span></a>
                </th>
                <th scope="col" class="manage-column column-status <?php echo get_sort_class( 'status', $orderby, $order ); ?>">
                    <a href="<?php echo esc_url( $sort_links['status'] ); ?>"><span>Status</span><span class="sorting-indicator"></span></a>
                </th>
                <th scope="col" class="manage-column column-date <?php echo get_sort_class( 'created_at', $orderby, $order ); ?>">
                    <a href="<?php echo esc_url( $sort_links['created_at'] ); ?>"><span>Created At</span><span class="sorting-indicator"></span></a>
                </th>
                <th scope="col" class="manage-column column-last-synced <?php echo get_sort_class( 'last_synced_at', $orderby, $order ); ?>">
                    <a href="<?php echo esc_url( add_query_arg( array( 'orderby' => 'last_synced_at', 'order' => $order_next ), $base_url ) ); ?>"><span>Last Synced</span><span class="sorting-indicator"></span></a>
                </th>
            </tr>
        </thead>
        <tfoot>
            <tr>
                <td class="manage-column column-cb check-column"><input id="cb-select-all-2" type="checkbox"></td>
                <th scope="col" class="manage-column column-id <?php echo get_sort_class( 'entity_id', $orderby, $order ); ?>">
                    <a href="<?php echo esc_url( add_query_arg( array( 'orderby' => 'entity_id', 'order' => $order_next ), $base_url ) ); ?>"><span>ID</span><span class="sorting-indicator"></span></a>
                </th>
                <th scope="col" class="manage-column column-title column-primary <?php echo get_sort_class( 'template_name', $orderby, $order ); ?>">
                    <a href="<?php echo esc_url( $sort_links['template_name'] ); ?>"><span>Template Name</span><span class="sorting-indicator"></span></a>
                </th>
                <th scope="col" class="manage-column column-type <?php echo get_sort_class( 'template_type', $orderby, $order ); ?>">
                    <a href="<?php echo esc_url( $sort_links['template_type'] ); ?>"><span>Type</span><span class="sorting-indicator"></span></a>
                </th>
                <th scope="col" class="manage-column column-category <?php echo get_sort_class( 'category', $orderby, $order ); ?>">
                    <a href="<?php echo esc_url( $sort_links['category'] ); ?>"><span>Category</span><span class="sorting-indicator"></span></a>
                </th>
                <th scope="col" class="manage-column column-language <?php echo get_sort_class( 'language', $orderby, $order ); ?>">
                    <a href="<?php echo esc_url( $sort_links['language'] ); ?>"><span>Language Code</span><span class="sorting-indicator"></span></a>
                </th>
                <th scope="col" class="manage-column column-status <?php echo get_sort_class( 'status', $orderby, $order ); ?>">
                    <a href="<?php echo esc_url( $sort_links['status'] ); ?>"><span>Status</span><span class="sorting-indicator"></span></a>
                </th>
                <th scope="col" class="manage-column column-date <?php echo get_sort_class( 'created_at', $orderby, $order ); ?>">
                    <a href="<?php echo esc_url( $sort_links['created_at'] ); ?>"><span>Created At</span><span class="sorting-indicator"></span></a>
                </th>
                <th scope="col" class="manage-column column-last-synced <?php echo get_sort_class( 'last_synced_at', $orderby, $order ); ?>">
                    <a href="<?php echo esc_url( add_query_arg( array( 'orderby' => 'last_synced_at', 'order' => $order_next ), $base_url ) ); ?>"><span>Last Synced</span><span class="sorting-indicator"></span></a>
                </th>
            </tr>
        </tfoot>
        <tbody id="the-list">
            <?php if ( ! empty( $templates ) ) : ?>
                <?php foreach($templates as $tmp): ?>
                <tr id="template-<?php echo esc_attr($tmp['entity_id'] ?? ''); ?>">
                    <th scope="row" class="check-column"><input type="checkbox" name="template_ids[]" value="<?php echo esc_attr($tmp['entity_id'] ?? ''); ?>"></th>
                    <td class="column-id" data-colname="ID"><?php echo esc_html($tmp['entity_id']); ?></td>
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
                    <td class="column-last-synced" data-colname="Last Synced"><?php echo ! empty( $tmp['last_synced_at'] ) ? esc_html( date( 'M d, Y g:i a', strtotime( $tmp['last_synced_at'] ) ) ) : 'Never'; ?></td>
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
document.getElementById('cb-select-all-2').addEventListener('change', function() {
    document.querySelectorAll('input[name="template_ids[]"]').forEach(cb => cb.checked = this.checked);
});

jQuery(document).ready(function($) {
    $('.wa-bulk-delete').on('click', function(e) {
        if ($('#bulk-action-selector-top').val() === 'delete') {
            var selected = $('input[name="template_ids[]"]:checked').length;
            if (selected === 0) {
                alert('Please select at least one template to delete.');
                return false;
            }
            if (!confirm('Are you sure you want to delete ' + selected + ' template(s)? This will also attempt to delete them from Meta API.')) {
                return false;
            }
        }
    });
});
</script>
