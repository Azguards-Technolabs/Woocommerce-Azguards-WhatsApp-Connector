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

            'wa_user_registration_table_data' => [
                'user_login'    => 'Username',
                'user_email'    => 'Email Address',
                'first_name'    => 'First Name',
                'last_name'     => 'Last Name',
                'display_name'  => 'Display Name',
                'user_id'       => 'User ID',
                'role'          => 'User Role',
                'registered'    => 'Registration Date',
                'billing_phone' => 'Billing Phone',
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
        ];

        return isset( $options[ $option_key ] ) ? $options[ $option_key ] : [];
    }
}
