<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Handle bulk or single sync
if ( isset( $_POST['wa_action'] ) && $_POST['wa_action'] === 'sync' && check_admin_referer( 'wa_sync_customers' ) ) {
    $ids = isset( $_POST['customer_ids'] ) ? array_map( 'intval', (array) $_POST['customer_ids'] ) : [];
    
    if ( ! empty( $ids ) ) {
        $success = 0;
        $errors  = 0;
        foreach ( $ids as $id ) {
            $res = WA_Contact::sync_customer( $id );
            if ( is_wp_error( $res ) ) {
                $last_error = $res->get_error_message();
                $errors++;
            } else {
                $success++;
            }
        }
        
        if ( $success > 0 ) {
            echo '<div class="notice notice-success is-dismissible"><p>Successfully synced ' . $success . ' customer(s).</p></div>';
        }
        if ( $errors > 0 ) {
            $err_str = isset($last_error) ? ' Error: ' . esc_html($last_error) : ' Check API settings.';
            echo '<div class="notice notice-error is-dismissible"><p>Failed to sync ' . $errors . ' customer(s).' . $err_str . '</p></div>';
        }
    }
}

// Fetch all "customer" role users
$args = array(
    'role'    => 'customer',
    'orderby' => 'ID',
    'order'   => 'DESC'
);

$customers = get_users( $args );
?>

<div class="wrap">
    <h1 class="wp-heading-inline">WhatTack Customers</h1>
    <a href="#" onclick="document.getElementById('wa_action').value='sync'; document.getElementById('wa-customers-form').submit(); return false;" class="page-title-action">Sync Selected to WhatTack</a>
    <hr class="wp-header-end">

    <form method="post" id="wa-customers-form">
        <?php wp_nonce_field( 'wa_sync_customers' ); ?>
        <input type="hidden" name="wa_action" id="wa_action" value="">
        
        <table class="wp-list-table widefat fixed striped table-view-list">
            <thead>
                <tr>
                    <td id="cb" class="manage-column column-cb check-column"><input id="wa_select_all" type="checkbox"></td>
                    <th scope="col" class="manage-column column-id">ID</th>
                    <th scope="col" class="manage-column column-name column-primary">Name</th>
                    <th scope="col" class="manage-column column-email">Email</th>
                    <th scope="col" class="manage-column column-phone">Phone</th>
                    <th scope="col" class="manage-column column-status">Sync Status</th>
                </tr>
            </thead>
            <tbody id="the-list">
                <?php if ( empty( $customers ) ): ?>
                <tr class="no-items"><td class="colspanchange" colspan="6">No customers found.</td></tr>
                <?php else: ?>
                <?php foreach ( $customers as $user ): 
                    $first = get_user_meta( $user->ID, 'billing_first_name', true );
                    $last  = get_user_meta( $user->ID, 'billing_last_name', true );
                    $name  = trim( "$first $last" ) ?: $user->display_name;
                    $phone = get_user_meta( $user->ID, 'billing_phone', true );
                    $synced = get_user_meta( $user->ID, 'wa_whatsapp_synced', true );
                ?>
                <tr id="customer-<?php echo esc_attr( $user->ID ); ?>">
                    <th scope="row" class="check-column">
                        <input type="checkbox" name="customer_ids[]" value="<?php echo esc_attr( $user->ID ); ?>">
                    </th>
                    <td class="column-id">#<?php echo esc_html( $user->ID ); ?></td>
                    <td class="column-name column-primary" data-colname="Name"><strong><?php echo esc_html( $name ); ?></strong></td>
                    <td class="column-email" data-colname="Email"><?php echo esc_html( $user->user_email ); ?></td>
                    <td class="column-phone" data-colname="Phone"><?php echo esc_html( $phone ?: '—' ); ?></td>
                    <td class="column-status" data-colname="Sync Status">
                        <?php if ( $synced == '1' ): ?>
                            <span style="color:#1a7f64; font-weight:bold;">✅ Synced</span>
                        <?php else: ?>
                            <span style="color:#888;">Not Synced</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </form>
</div>

<script>
document.getElementById('wa_select_all').addEventListener('change', function() {
    document.querySelectorAll('input[name="customer_ids[]"]').forEach(cb => cb.checked = this.checked);
});
</script>
