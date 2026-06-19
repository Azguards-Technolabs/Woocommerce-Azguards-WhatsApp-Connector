<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class WA_WoocommerceOptions
 *
 * Handles WooCommerce options for various order statuses.
 */
class WA_WoocommerceOptions {

    /**
     * Get WooCommerce options based on field name.
     *
     * @param string $option_key The field name for the option data.
     * 
     * @return array
     */
    public static function get_woocommerce_options( $option_key ) {
        $options = [
            'wa_order_creation_table_data' => [
                'id' => 'Order ID',
                'order_number' => 'Order Number',
                'status' => 'Order Status',
                'currency' => 'Currency',
                'payment_method' => 'Payment Method Slug',
                'payment_method_title' => 'Payment Method Title',
                'total' => 'Order Total',
                'subtotal' => 'Order Subtotal',
                'discount_total' => 'Discount Total',
                'shipping_total' => 'Shipping Total',
                'shipping_method' => 'Shipping Method',
                'date_created' => 'Order Date',

                // Billing
                'billing_first_name' => 'Billing First Name',
                'billing_last_name' => 'Billing Last Name',
                'billing_email' => 'Billing Email',
                'billing_phone' => 'Billing Phone',

                // Shipping
                'shipping_first_name' => 'Shipping First Name',
                'shipping_last_name' => 'Shipping Last Name',
                'shipping_address_1' => 'Shipping Address 1',
                'shipping_city' => 'Shipping City',

                // Customer
                'customer_id' => 'Customer ID',
                'customer_ip_address' => 'Customer IP Address',
            ],

            'wa_order_pending_payment_table_data' => [
                'id' => 'Order ID',
                'order_number' => 'Order Number',
                'status' => 'Order Status',
                'currency' => 'Currency',
                'payment_method' => 'Payment Method Slug',
                'payment_method_title' => 'Payment Method Title',
                'total' => 'Order Total',
                'subtotal' => 'Order Subtotal',
                'discount_total' => 'Discount Total',
                'shipping_total' => 'Shipping Total',
                'shipping_method' => 'Shipping Method',
                'date_created' => 'Order Date',

                // Billing
                'billing_first_name' => 'Billing First Name',
                'billing_last_name' => 'Billing Last Name',
                'billing_email' => 'Billing Email',
                'billing_phone' => 'Billing Phone',

                // Shipping
                'shipping_first_name' => 'Shipping First Name',
                'shipping_last_name' => 'Shipping Last Name',
                'shipping_address_1' => 'Shipping Address 1',
                'shipping_city' => 'Shipping City',

                // Customer
                'customer_id' => 'Customer ID',
                'customer_ip_address' => 'Customer IP Address',
            ],

            'wa_order_processing_table_data' => [
                'id' => 'Order ID',
                'order_number' => 'Order Number',
                'status' => 'Order Status',
                'currency' => 'Currency',
                'payment_method' => 'Payment Method Slug',
                'payment_method_title' => 'Payment Method Title',
                'total' => 'Order Total',
                'subtotal' => 'Order Subtotal',
                'discount_total' => 'Discount Total',
                'shipping_total' => 'Shipping Total',
                'shipping_method' => 'Shipping Method',
                'date_created' => 'Order Date',

                // Billing
                'billing_first_name' => 'Billing First Name',
                'billing_last_name' => 'Billing Last Name',
                'billing_email' => 'Billing Email',
                'billing_phone' => 'Billing Phone',

                // Shipping
                'shipping_first_name' => 'Shipping First Name',
                'shipping_last_name' => 'Shipping Last Name',
                'shipping_address_1' => 'Shipping Address 1',
                'shipping_city' => 'Shipping City',

                // Customer
                'customer_id' => 'Customer ID',
                'customer_ip_address' => 'Customer IP Address',
            ],

            'wa_order_on_hold_table_data' => [
                'id' => 'Order ID',
                'order_number' => 'Order Number',
                'status' => 'Order Status',
                'currency' => 'Currency',
                'payment_method' => 'Payment Method Slug',
                'payment_method_title' => 'Payment Method Title',
                'total' => 'Order Total',
                'subtotal' => 'Order Subtotal',
                'discount_total' => 'Discount Total',
                'shipping_total' => 'Shipping Total',
                'shipping_method' => 'Shipping Method',
                'date_created' => 'Order Date',
                'date_modified' => 'Last Modified Date',

                // Billing
                'billing_first_name' => 'Billing First Name',
                'billing_last_name' => 'Billing Last Name',
                'billing_email' => 'Billing Email',
                'billing_phone' => 'Billing Phone',

                // Shipping
                'shipping_first_name' => 'Shipping First Name',
                'shipping_last_name' => 'Shipping Last Name',
                'shipping_address_1' => 'Shipping Address 1',
                'shipping_city' => 'Shipping City',

                // Customer
                'customer_id' => 'Customer ID',
                'customer_ip_address' => 'Customer IP Address',
            ],

            'wa_order_failed_table_data' => [
                'id' => 'Order ID',
                'order_number' => 'Order Number',
                'status' => 'Order Status',
                'currency' => 'Currency',
                'payment_method' => 'Payment Method Slug',
                'payment_method_title' => 'Payment Method Title',
                'total' => 'Order Total',
                'subtotal' => 'Order Subtotal',
                'discount_total' => 'Discount Total',
                'shipping_total' => 'Shipping Total',
                'shipping_method' => 'Shipping Method',
                'date_created' => 'Order Date',
                'date_modified' => 'Last Modified Date',

                // Billing
                'billing_first_name' => 'Billing First Name',
                'billing_last_name' => 'Billing Last Name',
                'billing_email' => 'Billing Email',
                'billing_phone' => 'Billing Phone',

                // Shipping
                'shipping_first_name' => 'Shipping First Name',
                'shipping_last_name' => 'Shipping Last Name',
                'shipping_address_1' => 'Shipping Address 1',
                'shipping_city' => 'Shipping City',

                // Customer
                'customer_id' => 'Customer ID',
                'customer_ip_address' => 'Customer IP Address',
            ],

            'wa_order_completed_table_data' => [
                'id' => 'Order ID',
                'order_number' => 'Order Number',
                'status' => 'Order Status',
                'currency' => 'Currency',
                'payment_method' => 'Payment Method Slug',
                'payment_method_title' => 'Payment Method Title',
                'total' => 'Order Total',
                'subtotal' => 'Order Subtotal',
                'discount_total' => 'Discount Total',
                'shipping_total' => 'Shipping Total',
                'shipping_method' => 'Shipping Method',
                'date_created' => 'Order Date',
                'date_completed' => 'Completed Date',
                'date_modified' => 'Last Modified Date',

                // Billing
                'billing_first_name' => 'Billing First Name',
                'billing_last_name' => 'Billing Last Name',
                'billing_email' => 'Billing Email',
                'billing_phone' => 'Billing Phone',

                // Shipping
                'shipping_first_name' => 'Shipping First Name',
                'shipping_last_name' => 'Shipping Last Name',
                'shipping_address_1' => 'Shipping Address 1',
                'shipping_city' => 'Shipping City',

                // Customer
                'customer_id' => 'Customer ID',
                'customer_ip_address' => 'Customer IP Address',
            ],

            'wa_order_draft_table_data' => [
                'id' => 'Order ID',
                'order_number' => 'Order Number',
                'status' => 'Order Status',
                'currency' => 'Currency',
                'payment_method' => 'Payment Method Slug',
                'payment_method_title' => 'Payment Method Title',
                'total' => 'Order Total',
                'subtotal' => 'Order Subtotal',
                'discount_total' => 'Discount Total',
                'shipping_total' => 'Shipping Total',
                'shipping_method' => 'Shipping Method',
                'date_created' => 'Order Date',
                'date_modified' => 'Last Modified Date',

                // Billing
                'billing_first_name' => 'Billing First Name',
                'billing_last_name' => 'Billing Last Name',
                'billing_email' => 'Billing Email',
                'billing_phone' => 'Billing Phone',

                // Shipping
                'shipping_first_name' => 'Shipping First Name',
                'shipping_last_name' => 'Shipping Last Name',
                'shipping_address_1' => 'Shipping Address 1',
                'shipping_city' => 'Shipping City',

                // Customer
                'customer_id' => 'Customer ID',
                'customer_ip_address' => 'Customer IP Address',
            ],


            'wa_order_invoice_table_data' => [
                'id' => 'Order ID',
                'order_number' => 'Order Number',
                'status' => 'Order Status',
                'total' => 'Invoice Amount',
                'date_created' => 'Invoice Date',
                'billing_email' => 'Billing Email',
                'payment_method_title' => 'Payment Method Title',

                // Billing
                'billing_first_name' => 'Billing First Name',
                'billing_last_name' => 'Billing Last Name',
                'billing_email' => 'Billing Email',
                'billing_phone' => 'Billing Phone',

                // Shipping
                'shipping_first_name' => 'Shipping First Name',
                'shipping_last_name' => 'Shipping Last Name',
                'shipping_address_1' => 'Shipping Address 1',
                'shipping_city' => 'Shipping City',

                // Customer
                'customer_id' => 'Customer ID',
                'customer_ip_address' => 'Customer IP Address',
            ],

            'wa_order_shipment_table_data' => [
                'id' => 'Order ID',
                'order_number' => 'Order Number',
                'status' => 'Order Status',
                'shipping_method' => 'Shipping Method',
                'shipping_total' => 'Shipping Total',
                'shipping_first_name' => 'Shipping First Name',
                'shipping_last_name' => 'Shipping Last Name',
                'shipping_address_1' => 'Shipping Address 1',
                'shipping_city' => 'Shipping City',

                // Billing
                'billing_first_name' => 'Billing First Name',
                'billing_last_name' => 'Billing Last Name',
                'billing_email' => 'Billing Email',
                'billing_phone' => 'Billing Phone',
                
                // Customer
                'customer_id' => 'Customer ID',
                'customer_ip_address' => 'Customer IP Address',
            ],

            'wa_order_cancellation_table_data' => [
                'id' => 'Order ID',
                'cancel_reason'  => 'Cancellation Reason',
                'status' => 'Order Status',
                'cancel_date'    => 'Cancellation Date',
                'order_number' => 'Order Number',
                'total' => 'Cancellation Amount',
                'date_created' => 'Cancellation Date',
                'billing_email' => 'Billing Email',
                'payment_method_title' => 'Payment Method Title',

                // Billing
                'billing_first_name' => 'Billing First Name',
                'billing_last_name' => 'Billing Last Name',
                'billing_email' => 'Billing Email',
                'billing_phone' => 'Billing Phone',

                // Shipping
                'shipping_first_name' => 'Shipping First Name',
                'shipping_last_name' => 'Shipping Last Name',
                'shipping_address_1' => 'Shipping Address 1',
                'shipping_city' => 'Shipping City',

                // Customer
                'customer_id' => 'Customer ID',
                'customer_ip_address' => 'Customer IP Address',
            ],

            'wa_order_credit_memo_table_data' => [
                'id' => 'Order ID',
                'order_number' => 'Order Number',
                'status' => 'Order Status',
                'total' => 'Refund Amount',
                'date_created' => 'Refund Date',
                'billing_email' => 'Billing Email',
                'payment_method_title' => 'Payment Method Title',

                // Billing
                'billing_first_name' => 'Billing First Name',
                'billing_last_name' => 'Billing Last Name',
                'billing_email' => 'Billing Email',
                'billing_phone' => 'Billing Phone',

                // Shipping
                'shipping_first_name' => 'Shipping First Name',
                'shipping_last_name' => 'Shipping Last Name',
                'shipping_address_1' => 'Shipping Address 1',
                'shipping_city' => 'Shipping City',

                // Customer
                'customer_id' => 'Customer ID',
                'customer_ip_address' => 'Customer IP Address',
            ],

            'wa_product_notification_table_data' => [
                'product_id'     => 'Product ID',
                'product_name'   => 'Product Name',
                'stock_status'   => 'Stock Status',
                'price'          => 'Price',
                'sku'            => 'SKU',
            ],

            'wa_abandon_cart_table_data' => [
                'user_email'     => 'User Email',
                'cart_total'     => 'Cart Total',
                'abandon_time'   => 'Abandon Time',
                'cart_items'     => 'Cart Items',
            ],

            'wa_marketing_campaign_table_data' => [
                'firstname'    => 'Customer First Name',
                'lastname'     => 'Customer Last Name',
                'name'         => 'Customer Full Name',
                'email'        => 'Customer Email',
                'phone'        => 'Customer Phone',
                'customer_id'  => 'Customer ID',
                'mobileNumber' => 'Mobile Number',
                'countryCode'  => 'Country Code',
                'businessName' => 'Business Name',
                'website'      => 'Website',
            ],
        ];

        $order_option_keys = [
            'wa_order_creation_table_data',
            'wa_order_pending_payment_table_data',
            'wa_order_processing_table_data',
            'wa_order_on_hold_table_data',
            'wa_order_failed_table_data',
            'wa_order_completed_table_data',
            'wa_order_draft_table_data',
            'wa_order_invoice_table_data',
            'wa_order_shipment_table_data',
            'wa_order_cancellation_table_data',
            'wa_order_credit_memo_table_data',
        ];

        foreach ( $order_option_keys as $order_option_key ) {
            if ( isset( $options[ $order_option_key ] ) ) {
                $options[ $order_option_key ] = self::get_magento_order_options() + $options[ $order_option_key ];
            }
        }

        if ( isset( $options['wa_order_shipment_table_data'] ) ) {
            $options['wa_order_shipment_table_data'] = self::get_magento_shipment_options() + $options['wa_order_shipment_table_data'];
        }

        if ( isset( $options['wa_order_invoice_table_data'] ) ) {
            $options['wa_order_invoice_table_data'] = self::get_magento_invoice_options() + $options['wa_order_invoice_table_data'];
        }

        if ( isset( $options['wa_order_credit_memo_table_data'] ) ) {
            $options['wa_order_credit_memo_table_data'] = self::get_magento_creditmemo_options() + $options['wa_order_credit_memo_table_data'];
        }

        if ( isset( $options['wa_abandon_cart_table_data'] ) ) {
            $options['wa_abandon_cart_table_data'] = self::get_magento_quote_options() + $options['wa_abandon_cart_table_data'];
        }

        return isset( $options[ $option_key ] ) ? $options[ $option_key ] : [];
    }

    /**
     * Basic WooCommerce variable names for template mapping.
     *
     * @return array
     */
    private static function get_magento_order_options() {
        return [
            'entity_id'          => 'Order ID',
            'increment_id'       => 'Order Number',
            'status'             => 'Order Status',
            'customer_email'     => 'Customer Email',
            'customer_firstname' => 'Customer First Name',
            'customer_lastname'  => 'Customer Last Name',
            'grand_total'        => 'Grand Total',
            'subtotal'           => 'Subtotal',
            'shipping_amount'    => 'Shipping Amount',
            'payment_method'     => 'Payment Method',
            'shipping_method'    => 'Shipping Method',
            'created_at'         => 'Order Date',
            'updated_at'         => 'Last Updated',
            'billing_address'    => 'Billing Address',
            'shipping_address'   => 'Shipping Address',
            'store_base_url'     => 'Store Base URL',
            'items_summary'      => 'Items Summary',
        ];
    }

    private static function get_magento_shipment_options() {
        return [
            'shipment.tracking_number' => 'Shipment Tracking Number',
            'shipment.carrier_name'    => 'Shipment Carrier Name',
            'tracking_number'          => 'Tracking Number',
            'carrier_name'             => 'Carrier Name',
        ];
    }

    private static function get_magento_invoice_options() {
        return [
            'invoice.increment_id' => 'Invoice Number',
            'invoice.grand_total'  => 'Invoice Grand Total',
        ];
    }

    private static function get_magento_creditmemo_options() {
        return [
            'creditmemo.increment_id' => 'Credit Memo Number',
            'creditmemo.grand_total'  => 'Credit Memo Grand Total',
        ];
    }

    private static function get_magento_quote_options() {
        return [
            'cart_id'             => 'Cart ID',
            'cart_total'          => 'Cart Total',
            'cart_items'          => 'Cart Items',
            'items_count'         => 'Cart Items Count',
            'items_qty'           => 'Cart Items Quantity',
            'customer_email'      => 'Cart Customer Email',
            'customer_first_name' => 'Cart Customer First Name',
            'customer_last_name'  => 'Cart Customer Last Name',
            'store_base_url'      => 'Store Base URL',
            'cart_url'            => 'Cart URL',
        ];
    }
}
