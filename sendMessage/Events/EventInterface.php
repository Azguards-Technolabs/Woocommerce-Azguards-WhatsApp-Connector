<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

interface WA_Event_Interface {
    /**
     * Executes the specific logic for the WooCommerce hook event.
     *
     * @param int $order_id
     */
    public function execute( $order_id );
}
