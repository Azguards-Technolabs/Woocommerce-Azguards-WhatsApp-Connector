<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

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
        $value    = wa_resolve_template_variable_value( $property, $source_object );

        $template_variable_data[ $index ] = $value;
    }

    return $template_variable_data;
}

/**
 * Resolve Magento-style template variable paths against WooCommerce objects.
 *
 * @param string $property      Variable path from the template mapping.
 * @param object $source_object Usually a WC_Order.
 *
 * @return mixed
 */
function wa_resolve_template_variable_value( $property, $source_object ) {
    $property = trim( (string) $property );
    $property = preg_replace( '/^\s*var\s+/', '', $property );
    $property = str_replace( '()', '', $property );

    if ( $property === '' ) {
        return '';
    }

    $prefixless = $property;
    if ( strpos( $prefixless, '.' ) !== false ) {
        $parts      = explode( '.', $prefixless );
        $prefixless = end( $parts );
    }

    $aliases = [
        'base_url'             => 'base_url',
        'store_base_url'       => 'base_url',
        'entity_id'            => 'id',
        'increment_id'         => 'order_number',
        'grand_total'          => 'total',
        'shipping_amount'      => 'shipping_total',
        'created_at'           => 'date_created',
        'updated_at'           => 'date_modified',
        'customer_email'       => 'billing_email',
        'customer_firstname'   => 'billing_first_name',
        'customer_lastname'    => 'billing_last_name',
        'firstname'            => 'billing_first_name',
        'lastname'             => 'billing_last_name',
        'payment_method'       => 'payment_method',
        'billing_address'      => 'billing_address',
        'shipping_address'     => 'shipping_address',
        'tracking_number'      => 'tracking_number',
        'carrier_name'         => 'carrier_name',
        'items_summary'        => 'items_summary',
        'qty_ordered'          => 'qty_ordered',
        'row_total'            => 'row_total',
    ];

    $clean_prop = $aliases[ $prefixless ] ?? $prefixless;

    switch ( $clean_prop ) {
        case 'base_url':
            return trailingslashit( get_site_url() );

        case 'id':
            return is_callable( [ $source_object, 'get_id' ] ) ? $source_object->get_id() : '';

        case 'order_number':
            return is_callable( [ $source_object, 'get_order_number' ] ) ? $source_object->get_order_number() : '';

        case 'billing_first_name':
            return is_callable( [ $source_object, 'get_billing_first_name' ] ) ? $source_object->get_billing_first_name() : '';

        case 'billing_last_name':
            return is_callable( [ $source_object, 'get_billing_last_name' ] ) ? $source_object->get_billing_last_name() : '';

        case 'total':
            return is_callable( [ $source_object, 'get_total' ] ) ? $source_object->get_total() : '';

        case 'subtotal':
            return is_callable( [ $source_object, 'get_subtotal' ] ) ? $source_object->get_subtotal() : '';

        case 'billing_address':
            return wa_format_order_address( $source_object, 'billing' );

        case 'shipping_address':
            return wa_format_order_address( $source_object, 'shipping' );

        case 'tracking_number':
        case 'carrier_name':
            return wa_get_order_tracking_value( $source_object, $clean_prop );

        case 'items_summary':
            return wa_get_order_items_summary( $source_object );
    }

    $method = 'get_' . $clean_prop;
    if ( is_callable( [ $source_object, $method ] ) ) {
        $value = $source_object->$method();
        return wa_normalize_template_variable_value( $value );
    }

    $data = is_callable( [ $source_object, 'get_data' ] ) ? $source_object->get_data() : [];
    return wa_normalize_template_variable_value( $data[ $property ] ?? ( $data[ $clean_prop ] ?? '' ) );
}

function wa_format_order_address( $order, $type ) {
    $method = 'get_formatted_' . $type . '_address';
    if ( is_callable( [ $order, $method ] ) ) {
        return wp_strip_all_tags( str_replace( '<br/>', ', ', $order->$method() ) );
    }

    return '';
}

function wa_get_order_items_summary( $order ) {
    if ( ! is_callable( [ $order, 'get_items' ] ) ) {
        return '';
    }

    $items_text = [];
    foreach ( $order->get_items() as $item ) {
        $qty   = is_callable( [ $item, 'get_quantity' ] ) ? $item->get_quantity() : '';
        $name  = is_callable( [ $item, 'get_name' ] ) ? $item->get_name() : '';
        $total = is_callable( [ $item, 'get_total' ] ) ? $item->get_total() : '';

        $items_text[] = "{$name} x {$qty} = {$total}";
    }

    return implode( "\n", $items_text );
}

function wa_get_order_tracking_value( $order, $field ) {
    if ( ! is_callable( [ $order, 'get_meta' ] ) ) {
        return '';
    }

    $tracking_items = $order->get_meta( '_wc_shipment_tracking_items' );
    if ( empty( $tracking_items ) || ! is_array( $tracking_items ) ) {
        return '';
    }

    $tracking_item = reset( $tracking_items );
    if ( ! is_array( $tracking_item ) ) {
        return '';
    }

    if ( $field === 'tracking_number' ) {
        return $tracking_item['tracking_number'] ?? '';
    }

    return $tracking_item['tracking_provider'] ?? ( $tracking_item['custom_tracking_provider'] ?? '' );
}

function wa_normalize_template_variable_value( $value ) {
    if ( $value instanceof DateTimeInterface ) {
        return $value->format( 'Y-m-d H:i:s' );
    }

    if ( is_object( $value ) && is_callable( [ $value, '__toString' ] ) ) {
        return (string) $value;
    }

    if ( is_scalar( $value ) || $value === null ) {
        return $value;
    }

    return wp_json_encode( $value );
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
