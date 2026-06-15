<?php
require_once dirname( __FILE__ ) . '/../../../wp-load.php';

global $wpdb;

echo "Updating DB tables using dbDelta...\n";
if ( class_exists( 'WA_Database' ) ) {
    WA_Database::create_tables();
    echo "Done.\n";
} else {
    echo "WA_Database class not found.\n";
}
