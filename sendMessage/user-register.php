<?php
/**
 * User Registration WhatsApp Notification — REMOVED
 *
 * This file previously contained a `user_register` hook that sent a WhatsApp
 * message when a new WordPress user registered. It has been removed because:
 *
 *  1. The Magento WhatsApp Connector does NOT have an equivalent observer.
 *  2. It is out of scope for the parity implementation.
 *
 * The file is kept as a stub to avoid breaking any file-existence checks
 * during plugin load. It is no longer required by whatsapp-connector.php.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
