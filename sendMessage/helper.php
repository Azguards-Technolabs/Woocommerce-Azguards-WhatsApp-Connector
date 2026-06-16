<?php
/**
 * Process the template variables for a given template option key and source object.
 *
 * @param string $template_option_key The template option key.
 * @param object $source_object The source object to fetch values from.
 *
 * @return array Processed template variable data.
 */
function wa_process_template_variables( $template_option_key, $source_object ) {
    $template_variable_json = get_option( $template_option_key );
    $template_variables = json_decode( $template_variable_json, true );
    $template_variable_data = [];

    if ( ! is_array( $template_variables ) ) {
        return $template_variable_data;
    }

    foreach ( $template_variables as $variable ) {
        $index    = $variable['index'];
        $property = $variable['max_results'];
        $method   = 'get_' . $property;

        switch ( $property ) {
            case 'first_name':
            case 'billing_first_name':
                $value = method_exists( $source_object, 'get_billing_first_name' )
                    ? $source_object->get_billing_first_name()
                    : '';
                break;

            case 'last_name':
            case 'billing_last_name':
                $value = method_exists( $source_object, 'get_billing_last_name' )
                    ? $source_object->get_billing_last_name()
                    : '';
                break;

            case 'items_summary':
                if ( method_exists( $source_object, 'get_items' ) ) {
                    $items_text = [];
                    foreach ( $source_object->get_items() as $item ) {
                        $qty  = $item->get_quantity();
                        $name = $item->get_name();
                        // We use a default structure if the exact inner template isn't available at runtime.
                        $items_text[] = "{$qty} x {$name}";
                    }
                    $value = implode( "\n", $items_text );
                }
                break;

            default:
                if ( method_exists( $source_object, $method ) ) {
                    $value = $source_object->$method();
                } else {
                    // Dot Notation Parsing 
                    // Support order.grand_total to grand_total
                    $clean_prop = $property;
                    if ( strpos( $clean_prop, 'order.' ) === 0 ) {
                        $clean_prop = str_replace( 'order.', '', $clean_prop );
                    }
                    
                    if ( $clean_prop === 'grand_total' || $clean_prop === 'total' ) {
                        $value = method_exists($source_object, 'get_total') ? $source_object->get_total() : '';
                    } else if ( $clean_prop === 'status' ) {
                        $value = method_exists($source_object, 'get_status') ? $source_object->get_status() : '';
                    } else if ( $clean_prop === 'order_number' || $clean_prop === 'id' ) {
                        $value = method_exists($source_object, 'get_order_number') ? $source_object->get_order_number() : ($source_object->get_id() ?? '');
                    } else if ( method_exists( $source_object, 'get_' . $clean_prop ) ) {
                        $dynamic_method = 'get_' . $clean_prop;
                        $value = $source_object->$dynamic_method();
                    } else {
                        $data  = is_callable( [ $source_object, 'get_data' ] ) ? $source_object->get_data() : [];
                        $value = $data[ $property ] ?? ( $data[ $clean_prop ] ?? '' );
                    }
                }
                break;
        }

        $template_variable_data[ $index ] = $value;
    }

    return $template_variable_data;
}

/**
 * Get the dial code for a given country code.
 *
 * @param string $country_code The country code (e.g. 'IN', 'US', etc.).
 *
 * @return string The corresponding dial code, or an empty string if not found.
 */
if ( ! function_exists( 'wa_get_dial_code_by_country' ) ) {
    function wa_get_dial_code_by_country( $country_code ) {
        $dial_codes = [
            'IN' => '91',
            'US' => '1',
            'GB' => '44',
            'AE' => '971',
            'CA' => '1',
            'AU' => '61',
        ];

        return $dial_codes[ strtoupper( $country_code ) ] ?? '';
    }
}
