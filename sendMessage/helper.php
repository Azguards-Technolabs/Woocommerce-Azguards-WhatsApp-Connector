<?php
/**
 * Process the template variables for a given template body and source object.
 *
 * @param string   $body_template The template string containing placeholders.
 * @param WC_Order $order         The WooCommerce order object.
 *
 * @return string Processed body with replaced variables.
 */
function wa_process_magento_variables( $body_template, $order ) {
    if ( ! $order ) {
        return $body_template;
    }

    // Replace basic variables
    $replacements = [
        '{{var order.customer_firstname}}' => $order->get_billing_first_name(),
        '{{var order.increment_id}}'        => $order->get_order_number(),
        '{{var order.grand_total}}'         => $order->get_total(),
        '{{var order.status}}'              => $order->get_status(),
        '{{var order.entity_id}}'           => $order->get_id(),
        '{{var store.base_url}}'            => get_site_url() . '/',
    ];

    // Handle Invoice variables (if applicable)
    $replacements['{{var invoice.increment_id}}'] = $order->get_order_number(); // Simplified for WP
    $replacements['{{var invoice.grand_total}}']  = $order->get_total();

    // Handle Shipment variables
    $replacements['{{var shipment.tracking_number}}'] = 'N/A'; // Need actual tracking if available
    $replacements['{{var shipment.carrier_name}}']    = 'Standard';

    // Handle Creditmemo variables
    $replacements['{{var creditmemo.increment_id}}'] = $order->get_order_number();
    $replacements['{{var creditmemo.grand_total}}']  = $order->get_total();

    // Handle Quote (Abandoned Cart) variables
    $replacements['{{var quote.customer_firstname}}'] = $order->get_billing_first_name();
    $replacements['{{var quote.grand_total}}']        = $order->get_total();

    $processed_body = str_replace( array_keys( $replacements ), array_values( $replacements ), $body_template );

    // Handle Items Loop: {{#items}}{{var items.name}} x {{var items.qty_ordered}} = {{var items.row_total}}{{/items}}
    if ( preg_match( '/{{#items}}(.*){{\/items}}/s', $processed_body, $matches ) ) {
        $item_template = $matches[1];
        $items_string  = '';

        foreach ( $order->get_items() as $item_id => $item ) {
            $product = $item->get_product();
            $item_replacements = [
                '{{var items.name}}'        => $item->get_name(),
                '{{var items.qty_ordered}}' => $item->get_quantity(),
                '{{var items.qty}}'         => $item->get_quantity(),
                '{{var items.row_total}}'   => $item->get_total(),
            ];
            $items_string .= str_replace( array_keys( $item_replacements ), array_values( $item_replacements ), $item_template );
        }

        $processed_body = preg_replace( '/{{#items}}.*{{\/items}}/s', $items_string, $processed_body );
    }

    return $processed_body;
}

/**
 * Get the dial code for a given country code.
 *
 * @param string $country_code The country code (e.g. 'IN', 'US', etc.).
 *
 * @return string The corresponding dial code, or an empty string if not found.
 */
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

/**
 * Get user detail data from the order.
 *
 * @param WC_Order $order The WooCommerce order object.
 *
 * @return array The array of user details.
 */
function wa_get_user_detail_data( $order ) {
    $billing_first_name = $order->get_billing_first_name();
    $billing_last_name  = $order->get_billing_last_name();
    $billing_country    = $order->get_billing_country();
    $billing_phone      = $order->get_billing_phone();
    $billing_email      = $order->get_billing_email();
    $billing_company    = $order->get_billing_company();

    return [
        'firstName'    => $billing_first_name,
        'lastName'     => $billing_last_name,
        'countryCode'  => wa_get_dial_code_by_country( $billing_country ),
        'mobileNumber' => $billing_phone,
        'imageURL'     => 'https://randomuser.me/api/portraits/men/45.jpg',
        'email'        => $billing_email,
        'businessName' => $billing_company ?: 'Verma Creations',
        'website'      => get_site_url(),
    ];
}
