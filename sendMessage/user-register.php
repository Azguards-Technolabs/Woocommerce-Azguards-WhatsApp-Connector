<?php
/**
 * Handle user registration and send a message upon registration.
 *
 * @param int $user_id The ID of the newly registered user.
 */
add_action( 'user_register', 'wa_handle_user_registration', 10, 1 );

function wa_handle_user_registration( $user_id ) {
    $user = get_userdata( $user_id );
    if ( ! $user ) {
        return;
    }

    $template_id  = get_option( 'wa_user_registration_template' );
    $variables    = wa_process_user_template_variables( 'wa_user_registration_table_data', $user );
    $user_detail  = wa_get_user_detail_data_from_user( $user );

    if ( $template_id ) {
        WA_Message::send_message( $variables, $template_id, 'User Registration', $user_detail );
    }
}

/**
 * Get user details data from the WP_User object.
 *
 * @param WP_User $user The WP_User object.
 *
 * @return array An array of user details like name, phone, country code, etc.
 */
function wa_get_user_detail_data_from_user( WP_User $user ) {
    $first_name  = get_user_meta( $user->ID, 'first_name', true );
    $last_name   = get_user_meta( $user->ID, 'last_name', true );
    $phone       = get_user_meta( $user->ID, 'billing_phone', true );
    $company     = get_user_meta( $user->ID, 'billing_company', true );
    $country     = get_user_meta( $user->ID, 'billing_country', true );

    return [
        'firstName'    => $first_name ?: $user->user_nicename,
        'lastName'     => $last_name ?: $user->display_name,
        'countryCode'  => wa_get_dial_code_by_country( $country ),
        'mobileNumber' => $phone ?: '0000000000',
        'imageURL'     => 'https://randomuser.me/api/portraits/men/' . rand( 1, 99 ) . '.jpg',
        'email'        => $user->user_email,
        'businessName' => $company ?: 'Verma Creations',
        'website'      => get_site_url(),
    ];
}

/**
 * Process the user template variables for a given template option key and WP_User object.
 *
 * @param string  $template_option_key The template option key.
 * @param WP_User $user The WP_User object to fetch data from.
 *
 * @return array Processed template variable data.
 */
function wa_process_user_template_variables( $template_option_key, WP_User $user ) {
    $template_variable_json = get_option( $template_option_key );
    $template_variables     = json_decode( $template_variable_json, true );
    $template_variable_data = [];

    if ( ! is_array( $template_variables ) ) {
        return $template_variable_data;
    }

    foreach ( $template_variables as $variable ) {
        $index    = $variable['index'];
        $property = $variable['max_results'];
        $value    = '';

        switch ( $property ) {
            case 'first_name':
                $value = get_user_meta( $user->ID, 'first_name', true ) ?: $user->data->user_nicename ?? '';
                break;

            case 'last_name':
                $value = get_user_meta( $user->ID, 'last_name', true ) ?: $user->data->display_name ?? '';
                break;

            case 'role':
                $value = implode( ', ', $user->roles );
                break;

            case 'country_dial_code':
                $country = get_user_meta( $user->ID, 'billing_country', true );
                $value   = wa_get_dial_code_by_country( $country );
                break;

            default:
                if ( isset( $user->data->$property ) ) {
                    $value = $user->data->$property;
                } elseif ( property_exists( $user, $property ) ) {
                    $value = $user->$property;
                } else {
                    $value = get_user_meta( $user->ID, $property, true );
                }
                break;
        }

        $template_variable_data[ $index ] = $value;
    }

    return $template_variable_data;
}
