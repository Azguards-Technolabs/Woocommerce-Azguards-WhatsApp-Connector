<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WA_Database {

    public static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

        // 1. Templates Table
        $table_templates = $wpdb->prefix . 'azguards_whatsapp_templates';
        $sql_templates = "CREATE TABLE $table_templates (
            entity_id bigint(20) NOT NULL AUTO_INCREMENT,
            template_id varchar(100) NOT NULL,
            template_name varchar(255) NOT NULL,
            template_type varchar(50) NOT NULL,
            category varchar(100) DEFAULT NULL,
            language varchar(50) DEFAULT NULL,
            body text NOT NULL,
            header_format varchar(50) DEFAULT NULL,
            header_text text DEFAULT NULL,
            footer text DEFAULT NULL,
            buttons text DEFAULT NULL,
            carousel_cards text DEFAULT NULL,
            media_handle text DEFAULT NULL,
            status varchar(50) NOT NULL DEFAULT 'PENDING',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            last_synced_at datetime DEFAULT NULL,
            PRIMARY KEY  (entity_id),
            UNIQUE KEY template_id (template_id),
            KEY status (status),
            KEY category (category),
            KEY language (language)
        ) $charset_collate;";

        // 2. Campaigns Table
        $table_campaigns = $wpdb->prefix . 'azguards_whatsapp_campaigns';
        $sql_campaigns = "CREATE TABLE $table_campaigns (
            campaign_id bigint(20) NOT NULL AUTO_INCREMENT,
            campaign_name varchar(255) NOT NULL,
            template_entity_id bigint(20) NOT NULL,
            target_type varchar(255) NOT NULL,
            customer_groups text DEFAULT NULL,
            contact_ids text DEFAULT NULL,
            scheduler_id varchar(100) DEFAULT NULL,
            schedule_time datetime DEFAULT NULL,
            cron_expression varchar(100) DEFAULT NULL,
            variable_mapping text DEFAULT NULL,
            trigger_type varchar(50) DEFAULT NULL,
            timezone varchar(100) DEFAULT NULL,
            status varchar(50) NOT NULL DEFAULT 'SCHEDULED',
            sent_count int(11) DEFAULT 0,
            failed_count int(11) DEFAULT 0,
            media_url varchar(1024) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (campaign_id)
        ) $charset_collate;";

        // 3. Campaign Queue Table
        $table_queue = $wpdb->prefix . 'azguards_whatsapp_campaign_queue';
        $sql_queue = "CREATE TABLE $table_queue (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            campaign_id bigint(20) DEFAULT NULL,
            recipient_phone varchar(50) NOT NULL,
            variable_mapping text,
            status varchar(50) NOT NULL DEFAULT 'PENDING',
            error_message text,
            processed_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        // 4. Abandoned Cart Tracking Table
        $table_abandoned = $wpdb->prefix . 'azguards_whatsapp_abandoned_cart';
        $sql_abandoned = "CREATE TABLE $table_abandoned (
            session_id varchar(100) NOT NULL,
            customer_email varchar(255),
            status varchar(50) NOT NULL DEFAULT 'SENT',
            notified_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (session_id)
        ) $charset_collate;";

        dbDelta( $sql_templates );
        dbDelta( $sql_campaigns );
        dbDelta( $sql_queue );
        dbDelta( $sql_abandoned );

        self::maybe_upgrade();
    }

    /**
     * Add missing columns on existing installs (dbDelta does not always alter tables).
     */
    public static function maybe_upgrade() {
        global $wpdb;

        $table_templates  = $wpdb->prefix . 'azguards_whatsapp_templates';
        $table_campaigns  = $wpdb->prefix . 'azguards_whatsapp_campaigns';

        if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_templates ) ) !== $table_templates ) {
            return;
        }

        $column = $wpdb->get_var( "SHOW COLUMNS FROM `$table_templates` LIKE 'last_synced_at'" );
        if ( null === $column ) {
            $wpdb->query( "ALTER TABLE `$table_templates` ADD COLUMN last_synced_at datetime DEFAULT NULL AFTER updated_at" );
        }

        if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_campaigns ) ) === $table_campaigns ) {
            $column = $wpdb->get_var( "SHOW COLUMNS FROM `$table_campaigns` LIKE 'contact_ids'" );
            if ( null === $column ) {
                $wpdb->query( "ALTER TABLE `$table_campaigns` ADD COLUMN contact_ids text DEFAULT NULL AFTER customer_groups" );
            }
        }
    }
}
