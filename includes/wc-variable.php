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
        // $api_url = get_option( 'wa_template_api_url' );
        $token      = get_option( 'wa_access_token' );
        $templates  = self::get_static_templates();
        $variables  = array();

        foreach ( $templates as $item ) {
            if ( $item['id'] === $template_id ) {
                if ( ! empty( $item['templateBodyTextSampleValues'] ) ) {
                    foreach ( $item['templateBodyTextSampleValues'] as $param ) {
                        $variables[] = array(
                            'index'       => isset( $param['parameterName'] ) ? $param['parameterName'] : '',
                            'max_results' => isset( $param['parameterValue'] ) ? $param['parameterValue'] : '',
                        );
                    }
                }
            }
        }

        return $variables;
    }

    /**
     * Get predefined static templates.
     *
     * @return array
     */
    public static function get_static_templates() {
        return [
        [
            "templateName" => "local_kenit_55",
            "templateCategory" => "MARKETING",
            "templateHeaderType" => "TEXT",
            "id" => "e939fc3e-abbf-4d99-9063-21b59588b73a",
            "templateHeaderText" => "hello",
            "templateBodyText" => "How are you , are you fine",
            "templateBodyTextSampleValues" => [
                [
                    "parameterName" => "lastname",
                    "parameterValue" => "kenit"
                ],
                [
                    "parameterName" => "firstname",
                    "parameterValue" => "kenit test"
                ]
            ],
            "templateFooterText" => "thank you",
            "templateButtons" => []
        ],[
            "templateName" => "local_juber_55",
            "templateCategory" => "MARKETING_JUBER",
            "templateHeaderType" => "TEXT_JUBER",
            "id" => "c9c56177-2079-451c-b982-3dfe40ce9964",
            "templateHeaderText" => "hello",
            "templateBodyText" => "How are you , are you fine",
            "templateBodyTextSampleValues" => [
                [
                    "parameterName" => "order_id",
                    "parameterValue" => "Order Id"
                ],
                [
                    "parameterName" => "order_total",
                    "parameterValue" => "Order Total"
                ],[
                    "parameterName" => "order_number",
                    "parameterValue" => "Order Number"
                ]
            ],
            "templateFooterText" => "thank you",
            "templateButtons" => []
        ],[
            "templateName" => "local_juber_55",
            "templateCategory" => "MARKETING_JUBER",
            "templateHeaderType" => "TEXT_JUBER",
            "id" => "ba9407d9-1ca8-4161-be18-930ae9682a33",
            "templateHeaderText" => "hello",
            "templateBodyText" => "How are you , are you fine",
            "templateBodyTextSampleValues" => [
                [
                    "parameterName" => "invoice_id",
                    "parameterValue" => "Invoice Id"
                ],
                [
                    "parameterName" => "invoice_total",
                    "parameterValue" => "Invoice Total"
                ],[
                    "parameterName" => "invoice_number",
                    "parameterValue" => "Invoice Number"
                ]
            ],
            "templateFooterText" => "thank you",
            "templateButtons" => []
        ],[
            "templateName" => "local_juber_55",
            "templateCategory" => "MARKETING_JUBER",
            "templateHeaderType" => "TEXT_JUBER",
            "id" => "48fe321d-a1d4-43e5-b16e-7fe2422c80b1",
            "templateHeaderText" => "hello",
            "templateBodyText" => "How are you , are you fine",
            "templateBodyTextSampleValues" => [
                [
                    "parameterName" => "shipment_id",
                    "parameterValue" => "Shipment Id"
                ],
                [
                    "parameterName" => "shipment_total",
                    "parameterValue" => "Shipment Total"
                ],[
                    "parameterName" => "shipment_number",
                    "parameterValue" => "Shipment Number"
                ]
            ],
            "templateFooterText" => "thank you",
            "templateButtons" => []
        ],[
            "templateName" => "local_juber_55",
            "templateCategory" => "MARKETING_JUBER",
            "templateHeaderType" => "TEXT_JUBER",
            "id" => "35c6a045-0219-460a-bc7e-67e07ebd7ff6",
            "templateHeaderText" => "hello",
            "templateBodyText" => "How are you , are you fine",
            "templateBodyTextSampleValues" => [
                [
                    "parameterName" => "cancel_id",
                    "parameterValue" => "Cancel Id"
                ],
                [
                    "parameterName" => "cancel_total",
                    "parameterValue" => "Cancel Total"
                ],[
                    "parameterName" => "cancel_number",
                    "parameterValue" => "Cancel Number"
                ],[
                    "parameterName" => "order_status",
                    "parameterValue" => "Order Status"
                ]
            ],
            "templateFooterText" => "thank you",
            "templateButtons" => []
        ],[
            "templateName" => "local_juber_55",
            "templateCategory" => "MARKETING_JUBER",
            "templateHeaderType" => "TEXT_JUBER",
            "id" => "6eb7eb58-0495-4b86-ba8f-cd425362cabe",
            "templateHeaderText" => "hello",
            "templateBodyText" => "How are you , are you fine",
            "templateBodyTextSampleValues" => [
                [
                    "parameterName" => "refund_id",
                    "parameterValue" => "Refund Id"
                ],
                [
                    "parameterName" => "refund_total",
                    "parameterValue" => "Refund Total"
                ],[
                    "parameterName" => "refund_number",
                    "parameterValue" => "Refund Number"
                ],[
                    "parameterName" => "order_status",
                    "parameterValue" => "Order Status"
                ]
            ],
            "templateFooterText" => "thank you",
            "templateButtons" => []
        ],
        // You can add more templates here
    ];
    }
}
