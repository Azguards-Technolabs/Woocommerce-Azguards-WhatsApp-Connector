<?php
/**
 * Custom WooCommerce settings field for WhatsApp Connector plugin.
 */

// Render custom table field.
add_action( 'woocommerce_admin_field_wa_custom_table', 'wa_render_custom_table_field' );

if ( ! function_exists( 'wa_render_custom_table_field' ) ) :
    /**
     * Render custom table field in WooCommerce settings.
     *
     * @param array $field Field settings.
     */
    function wa_render_custom_table_field( $field ) {
        $value = get_option( $field['id'], $field['default'] );

        if ( ! empty( $value ) ) {
            $value = json_decode( $value, true );
            if ( ! is_array( $value ) ) {
                $value = array();
            }
        } else {
            $value = array();
        }

        $options = WA_WoocommerceOptions::get_woocommerce_options( $field['field_name'] );

        ob_start();
        ?>
        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr( $field['id'] ); ?>">
                    <?php echo esc_html( $field['title'] ); ?>
                </label>
            </th>
            <td class="forminp">
                <div class="wa-variable-wrapper" id="wrapper-<?php echo esc_attr( $field['id'] ); ?>">
                    <table class="wa-variable-table-<?php echo esc_attr( $field['id'] ); ?> widefat">
                        <tbody>
                            <?php if ( ! empty( $value ) ) : ?>
                                <?php foreach ( $value as $i => $row ) : ?>
                                    <tr>
                                        <td><?php echo esc_html( $row['index'] ); ?></td>
                                        <td>
                                            <select name="<?php echo esc_attr( $field['id'] ); ?>[<?php echo esc_attr( $i ); ?>][max_results]">
                                                <?php foreach ( $options as $key => $label ) : ?>
                                                    <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $key, $row['max_results'] ); ?>>
                                                        <?php echo esc_html( $label ); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <tr>
                                    <td style="color: red;"><?php esc_html_e( 'Template variables not available', 'whatsapp-connector' ); ?></td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>

                    <input type="hidden"
                        class="wa-custom-table-json"
                        id="<?php echo esc_attr( $field['id'] ); ?>"
                        name="<?php echo esc_attr( $field['id'] ); ?>"
                        value="<?php echo esc_attr( wp_json_encode( $value ) ); ?>" />
                </div>
            </td>
        </tr>

        <script type="text/javascript">
            jQuery(function($) {
                const fieldId = '<?php echo esc_js( $field['id'] ); ?>';

                $('.wa-variable-table-' + fieldId + ' select').on('change', function() {
                    var table = $(this).closest('table');
                    var data = [];

                    table.find('tbody tr').each(function() {
                        var index = $(this).find('td:first').text().trim();
                        var max = $(this).find('select').val();
                        data.push({ index: index, max_results: max });
                    });

                    $('#' + fieldId).val(JSON.stringify(data));
                });
            });
        </script>
        <?php
        echo ob_get_clean();
    }
endif;

/**
 * AJAX: Get template variables for dropdowns.
 */
add_action( 'wp_ajax_wa_get_template_variables', 'wa_get_template_variables_callback' );

if ( ! function_exists( 'wa_get_template_variables_callback' ) ) :
    /**
     * Handle AJAX request to return template variables.
     */
    function wa_get_template_variables_callback() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( __( 'Unauthorized', 'whatsapp-connector' ) );
        }

        $template_id = isset( $_POST['template_id'] ) ? sanitize_text_field( wp_unslash( $_POST['template_id'] ) ) : '';
        $section     = isset( $_POST['section'] ) ? sanitize_text_field( wp_unslash( $_POST['section'] ) ) : '';
        $table_id    = isset( $_POST['table_id'] ) ? sanitize_text_field( wp_unslash( $_POST['table_id'] ) ) : '';

        $options   = WA_WoocommerceOptions::get_woocommerce_options( $table_id );
        $variables = WA_Variable::get_variables( $template_id );

        wp_send_json_success(
            array(
                'section'   => $section,
                'variables' => $variables,
                'options'   => $options,
            )
        );
    }
endif;
