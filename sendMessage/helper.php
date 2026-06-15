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
                $value = method_exists( $source_object, 'get_billing_first_name' )
                    ? $source_object->get_billing_first_name()
                    : '';
                break;

            case 'last_name':
                $value = method_exists( $source_object, 'get_billing_last_name' )
                    ? $source_object->get_billing_last_name()
                    : '';
                break;

            default:
                if ( method_exists( $source_object, $method ) ) {
                    $value = $source_object->$method();
                } else {
                    $data  = is_callable( [ $source_object, 'get_data' ] ) ? $source_object->get_data() : [];
                    $value = $data[ $property ] ?? '';
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
