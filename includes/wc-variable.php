<?php
/**
 * WA_Variable class.
 *
 * @package WhatsApp_Connector
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class WA_Variable
 */
class WA_Variable {

    /**
     * Get variables from a template by ID.
     *
     * @param string $template_id Template ID.
     * @return array
     */
    public static function get_variables( $template_id ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'azguards_whatsapp_templates';
        
        $template = $wpdb->get_row( $wpdb->prepare( "SELECT body FROM $table_name WHERE template_id = %s", $template_id ) );
        
        if ( ! $template ) {
            return array();
        }

        $body_text = $template->body;
        $variables = array();

        // Extract placeholders like {{1}}, {{2}}, etc. or named ones like {{firstname}}
        preg_match_all( '/\{\{\s*([^}]+?)\s*\}\}/', $body_text, $matches );

        if ( ! empty( $matches[1] ) ) {
            $unique_vars = array_unique( $matches[1] );
            foreach ( $unique_vars as $var ) {
                $variables[] = array(
                    'index'       => trim( $var ),
                    'max_results' => '', // Selected option from dropdown will be stored here
                );
            }
        }

        return $variables;
    }
}

