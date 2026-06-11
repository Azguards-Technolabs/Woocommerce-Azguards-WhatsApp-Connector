<?php
/**
 * Add custom WooCommerce admin field for validate credentials button.
 */
add_action( 'woocommerce_admin_field_wa_validate_button', function ( $value ) {
    $nonce = wp_create_nonce( 'wa_validate_credentials_nonce' );
    ?>
    <tr valign="top">
        <th scope="row" class="titledesc">
            <label><?php echo esc_html( $value['title'] ); ?></label>
        </th>
        <td class="forminp forminp-button">
            <button class="button" id="wa-validate-credentials">
                <?php esc_html_e( 'Generate Token', 'whatsapp-connector' ); ?>
            </button>
            <span id="wa-validate-result" style="margin-left:10px;"></span>
            <script type="text/javascript">
                jQuery(function($) {
                    $('#wa-validate-credentials').on('click', function(e) {
                        e.preventDefault();

                        const wa_grant_type = $('#wa_grant_type').val();
                        const wa_client_id = $('#wa_client_id').val();
                        const wa_client_secret = $('#wa_client_secret').val();
                        const wa_auth_api_url = $('#wa_auth_api_url').val();
                        const $result = $('#wa-validate-result');

                        $result.text('<?php echo esc_js( 'Validating...' ); ?>').css({
                            'color': '',
                            'font-weight': ''
                        });

                        $.post(ajaxurl, {
                            action: 'wa_get_validate_credentials',
                            wa_grant_type: wa_grant_type,
                            wa_client_id: wa_client_id,
                            wa_client_secret: wa_client_secret,
                            wa_auth_api_url: wa_auth_api_url
                        }, function(response) {
                            if (response.success) {
                                $result.text('<?php echo esc_js( 'Valid Authentication!' ); ?>').css({
                                    color: 'green',
                                    fontWeight: 'bold'
                                });
                                console.log('Token:', response.data.token);
                            } else {
                                $result.text(response.data?.message || '<?php echo esc_js( 'Validation failed' ); ?>').css({
                                    color: 'red',
                                    fontWeight: 'bold'
                                });
                                console.warn('Error:', response);
                            }
                        }).fail(function(xhr) {
                            $result.text('<?php echo esc_js( 'Server Error. Please try again.' ); ?>').css({
                                color: 'red',
                                fontWeight: 'bold'
                            });
                            console.error(xhr.responseText);
                        });
                    });
                });
            </script>
        </td>
    </tr>
    <?php
} );

/**
 * Handle AJAX request to validate WhatsApp API credentials.
 */
add_action( 'wp_ajax_wa_get_validate_credentials', 'wa_get_validate_credentials_callback' );

function wa_get_validate_credentials_callback() {
    if ( ! current_user_can( 'manage_woocommerce' ) ) {
        wp_send_json_error( 'Unauthorized' );
    }

    $grant_type    = sanitize_text_field( $_POST['wa_grant_type'] ?? '' );
    $client_id     = sanitize_text_field( $_POST['wa_client_id'] ?? '' );
    $client_secret = sanitize_text_field( $_POST['wa_client_secret'] ?? '' );
    $auth_url      = esc_url_raw( $_POST['wa_auth_api_url'] ?? '' );

    $response = WA_Auth::get_token( $auth_url, $client_id, $client_secret, $grant_type );

    if ( is_wp_error( $response ) ) {
        wp_send_json_error( array(
            'message' => 'Authentication failed: ' . $response->get_error_message(),
        ) );
    }

    if ( empty( $response['access_token'] ) ) {
        wp_send_json_error( array(
            'message' => 'Access token missing in response.',
        ) );
    }

    $token     = $response['access_token'];
    $expiresIn = $response['expires_in'] ?? 3600; // fallback to 1 hour if not provided.

    set_transient( 'wa_access_token', $token, $expiresIn );

    wp_send_json_success( array(
        'token' => $token,
    ) );
}
