<?php
/**
 * Customer Sync UI and Handlers for WP Users Table
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Add "Sync WhatsApp Contact" to the User Row Actions.
 */
add_filter( 'user_row_actions', 'wa_add_user_row_action', 10, 2 );
function wa_add_user_row_action( $actions, $user_object ) {
    $sync_url = wp_nonce_url(
        admin_url( "users.php?action=wa_sync_contact&user_id={$user_object->ID}" ),
        'wa_sync_contact_' . $user_object->ID
    );
    $actions['wa_sync'] = "<a href='" . esc_url( $sync_url ) . "'>" . __( 'Sync WhatsApp Contact', 'whatsapp-connector' ) . "</a>";
    return $actions;
}

/**
 * Add "Bulk Sync WhatsApp" to the Bulk Actions Dropdown.
 */
add_filter( 'bulk_actions-users', 'wa_register_user_bulk_actions' );
function wa_register_user_bulk_actions( $bulk_actions ) {
    $bulk_actions['wa_bulk_sync_contacts'] = __( 'Bulk Sync WhatsApp', 'whatsapp-connector' );
    return $bulk_actions;
}

/**
 * Handle Single Sync Action.
 */
add_action( 'admin_init', 'wa_handle_single_contact_sync' );
function wa_handle_single_contact_sync() {
    if ( isset( $_GET['action'] ) && $_GET['action'] === 'wa_sync_contact' && isset( $_GET['user_id'] ) ) {
        $user_id = intval( $_GET['user_id'] );
        check_admin_referer( 'wa_sync_contact_' . $user_id );

        if ( ! current_user_can( 'edit_user', $user_id ) ) {
            wp_die( __( 'You are not allowed to edit this user.', 'whatsapp-connector' ) );
        }

        $result = WA_Contact::sync_customer( $user_id );

        $redirect_url = admin_url( 'users.php' );
        if ( is_wp_error( $result ) ) {
            $err = urlencode( $result->get_error_message() );
            wp_redirect( add_query_arg( [ 'wa_sync_status' => 'error', 'wa_msg' => $err ], $redirect_url ) );
        } else {
            wp_redirect( add_query_arg( [ 'wa_sync_status' => 'success', 'wa_count' => 1 ], $redirect_url ) );
        }
        exit;
    }
}

/**
 * Handle Bulk Sync Action.
 */
add_filter( 'handle_bulk_actions-users', 'wa_handle_bulk_contact_sync', 10, 3 );
function wa_handle_bulk_contact_sync( $redirect_to, $doaction, $user_ids ) {
    if ( $doaction !== 'wa_bulk_sync_contacts' ) {
        return $redirect_to;
    }

    $success_count = 0;
    $error_msg     = '';

    foreach ( $user_ids as $user_id ) {
        if ( current_user_can( 'edit_user', $user_id ) ) {
            $result = WA_Contact::sync_customer( $user_id );
            if ( is_wp_error( $result ) && empty( $error_msg ) ) {
                $error_msg = $result->get_error_message(); 
            } else {
                $success_count++;
            }
        }
    }

    $redirect_args = [
        'wa_sync_status' => empty( $error_msg ) ? 'success' : 'error',
    ];

    if ( $success_count > 0 ) {
        $redirect_args['wa_count'] = $success_count;
    }
    if ( ! empty( $error_msg ) ) {
        $redirect_args['wa_msg'] = urlencode( $error_msg );
    }

    return add_query_arg( $redirect_args, $redirect_to );
}

/**
 * Display Admin Notices for Sync Results.
 */
add_action( 'admin_notices', 'wa_display_contact_sync_notices' );
function wa_display_contact_sync_notices() {
    global $pagenow;
    if ( $pagenow !== 'users.php' ) {
        return;
    }

    if ( isset( $_GET['wa_sync_status'] ) ) {
        $status = sanitize_text_field( $_GET['wa_sync_status'] );
        if ( $status === 'success' ) {
            $count = intval( $_GET['wa_count'] ?? 0 );
            $msg   = sprintf( _n( 'Successfully synced %s contact to WhatsApp.', 'Successfully synced %s contacts to WhatsApp.', $count, 'whatsapp-connector' ), $count );
            if ( isset( $_GET['wa_msg'] ) ) {
                $msg .= ' ' . esc_html( urldecode( $_GET['wa_msg'] ) ); // Show partial error if any
            }
            echo "<div class='notice notice-success is-dismissible'><p><strong>WhatsApp Connector:</strong> {$msg}</p></div>";
        } elseif ( $status === 'error' ) {
            $count = intval( $_GET['wa_count'] ?? 0 );
            $msg   = esc_html( urldecode( $_GET['wa_msg'] ?? 'Unknown error' ) );
            if ( $count > 0 ) {
               echo "<div class='notice notice-warning is-dismissible'><p><strong>WhatsApp Connector:</strong> Synced {$count} contacts, but encountered an error: {$msg}</p></div>";
            } else {
               echo "<div class='notice notice-error is-dismissible'><p><strong>WhatsApp Connector Error:</strong> {$msg}</p></div>";
            }
        }
    }
}
