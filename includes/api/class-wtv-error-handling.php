<?php

namespace includes\api;

use includes\WTV_Plugin;

class WTV_Error_Handling{

    private static function error_messages(){
        return [
	        '0' => [
		        'message' => __( 'Du är inte längre inloggad. Var god klicka på knappen Logga in först och slutför inloggningen.', WTV_Plugin::TEXTDOMAIN ),
		        'help_link' => ''
	        ],
            '2000' => [
                'message' => __( 'This error is thrown when any data model validations are broken in the request. See the rules for each POST method on each property.', WTV_Plugin::TEXTDOMAIN ),
                'help_link' => 'https://docs.wetail.io/woocommerce/visma-integration/felkoder/#2000'
            ],
            '3000' => [
                'message' => __( 'This error is thrown when you refer to a specific object that does not exist, eg. customers, suppliers or articles in a request.', WTV_Plugin::TEXTDOMAIN ),
                'help_link' => 'https://docs.wetail.io/woocommerce/visma-integration/felkoder/#3000'
            ],
            '3001' => [
                'message' => __( 'This error is thrown when you refer to a specific fiscalyear that does not exist.', WTV_Plugin::TEXTDOMAIN ),
                'help_link' => 'https://docs.wetail.io/woocommerce/visma-integration/felkoder/#3001'
            ],
            '3002' => [
                'message' => __( 'This error is thrown when you refer to a specific account that does not exist.', WTV_Plugin::TEXTDOMAIN ),
                'help_link' => 'https://docs.wetail.io/woocommerce/visma-integration/felkoder/#3002'
            ],
            '3003' => [
                'message' => __( 'This error is thrown when you have insufficient permissions to bookeep an invoice when using supplier invoice approval flow.', WTV_Plugin::TEXTDOMAIN ),
                'help_link' => 'https://docs.wetail.io/woocommerce/visma-integration/felkoder/#3003'
            ],
            '4000' => [
                'message' => __( 'This error is thrown when you try to create a object that cannot exist as a duplicate.', WTV_Plugin::TEXTDOMAIN ),
                'help_link' => 'https://docs.wetail.io/woocommerce/visma-integration/felkoder/#4000'
            ],
            '4001' => [
                'message' => __( 'This error is thrown when you try to delete a object that cannot be deleted. For example, when a object has dependencies, it cannot be deleted.', WTV_Plugin::TEXTDOMAIN ),
                'help_link' => 'https://docs.wetail.io/woocommerce/visma-integration/felkoder/#4001'
            ],
            '4002' => [
                'message' => __( 'This error is thrown when you try to create or update a object that contains one or more invalid properties that prevents it from being created or updated.', WTV_Plugin::TEXTDOMAIN ),
                'help_link' => 'https://docs.wetail.io/woocommerce/visma-integration/felkoder/#4002'
            ],
            '4003' => [
                'message' => __( 'This error is thrown when you try to make requests towards a company that hasnt completed the startup guide in Visma eAccounting.', WTV_Plugin::TEXTDOMAIN ),
                'help_link' => 'https://docs.wetail.io/woocommerce/visma-integration/felkoder/#4003'
            ],
            '4004' => [
                'message' => __( 'This error is thrown when you try to make requests towards an inactive company.', WTV_Plugin::TEXTDOMAIN ),
                'help_link' => 'https://docs.wetail.io/woocommerce/visma-integration/felkoder/#4004'
            ],
            '4005' => [
                'message' => __( 'This error is thrown when you try to make unauthorized requests towards the API.', WTV_Plugin::TEXTDOMAIN ),
                'help_link' => 'https://docs.wetail.io/woocommerce/visma-integration/felkoder/#4005'
            ],
            '4006' => [
                'message' => __( 'This error is thrown when you try to make requests towards an endpoint which require scopes that you are not authorized with.', WTV_Plugin::TEXTDOMAIN ),
                'help_link' => 'https://docs.wetail.io/woocommerce/visma-integration/felkoder/#4006'
            ],
            '4007' => [
                'message' => __( 'This error is thrown when you try to make requests with an authorized user that does not have sufficient permissions for that specific endpoint.', WTV_Plugin::TEXTDOMAIN ),
                'help_link' => 'https://docs.wetail.io/woocommerce/visma-integration/felkoder/#4007'
            ],
            '4008' => [
                'message' => __( 'This error is thrown when you try to make a request towards an endpoint with ControlDigit validation and the value is invalid.', WTV_Plugin::TEXTDOMAIN ),
                'help_link' => 'https://docs.wetail.io/woocommerce/visma-integration/felkoder/#4008'
            ],
            '4009' => [
                'message' => __( 'This error is thrown when you try to make requests towards and endpoint that is not included in the authorized companys product variant.', WTV_Plugin::TEXTDOMAIN ),
                'help_link' => 'https://docs.wetail.io/woocommerce/visma-integration/felkoder/#4009'
            ],
            '4010' => [
                'message' => __( 'This error is thrown when you try to more requests than allowed. Read more about this here.', WTV_Plugin::TEXTDOMAIN ),
                'help_link' => 'https://docs.wetail.io/woocommerce/visma-integration/felkoder/#4010'
            ],
            '4011' => [
                'message' => __( 'This error is thrown when the authenticated user does not have access to eAccounting for this company.Give the user access to eAccounting or reauthenticate with a user that have access to eAccounting to solve the issue.', WTV_Plugin::TEXTDOMAIN ),
                'help_link' => 'https://docs.wetail.io/woocommerce/visma-integration/felkoder/#4011'
            ],
            '4012' => [
                'message' => __( 'This error is thrown when the authenticated user does not have accepted the licence agreement for eAccounting for this company.The licence agreement can be accepted by logging in to eAccounting with the same user and company.', WTV_Plugin::TEXTDOMAIN ),
                'help_link' => 'https://docs.wetail.io/woocommerce/visma-integration/felkoder/#4012'
            ],
            '4013' => [
                'message' => __( 'This error is thrown when the process of bookkeeping the invoice is interrupted by validations like locked fiscal year or accounting period.', WTV_Plugin::TEXTDOMAIN ),
                'help_link' => 'https://docs.wetail.io/woocommerce/visma-integration/felkoder/#4013'
            ],
            '4014' => [
                'message' => __( 'This error is thrown when Norwegian companies lack YourReference when sending customer invoices with AutoInvoice.', WTV_Plugin::TEXTDOMAIN ),
                'help_link' => 'https://docs.wetail.io/woocommerce/visma-integration/felkoder/#4014'
            ],
            '4015' => [
                'message' => __( 'This error is thrown when sending customer invoices with AutoInvoice and the customer requires a reference code, which is missing.', WTV_Plugin::TEXTDOMAIN ),
                'help_link' => 'https://docs.wetail.io/woocommerce/visma-integration/felkoder/#4015'
            ],
            '4016' => [
                'message' => __( 'This error is thrown when a invalid invoice date is applied to a supplier invoice.', WTV_Plugin::TEXTDOMAIN ),
                'help_link' => 'https://docs.wetail.io/woocommerce/visma-integration/felkoder/#4016'
            ],
            '4017' => [
                'message' => __( 'This error is thrown when sending customer invoices with AutoInvoice and electronic address is missing on the customer.', WTV_Plugin::TEXTDOMAIN ),
                'help_link' => 'https://docs.wetail.io/woocommerce/visma-integration/felkoder/#4017'
            ],
            '4018' => [
                'message' => __( 'This error is thrown when you try to create a supplier invoices with a future date without having the "pay manually" setting active.', WTV_Plugin::TEXTDOMAIN ),
                'help_link' => 'https://docs.wetail.io/woocommerce/visma-integration/felkoder/#4018'
            ],
            '4019' => [
                'message' => __( 'This error is thrown when you are authenticated with a readonly user and trying to create/edit/delete an entity.', WTV_Plugin::TEXTDOMAIN ),
                'help_link' => 'https://docs.wetail.io/woocommerce/visma-integration/felkoder/#4019'
            ],
            '4020' => [
                'message' => __( 'This error is thrown when you are trying to create a fiscal year which overlaps another one.', WTV_Plugin::TEXTDOMAIN ),
                'help_link' => 'https://docs.wetail.io/woocommerce/visma-integration/felkoder/#4020'
            ],
            '4021' => [
                'message' => __( 'This error is thrown when you are trying to create a fiscal year with a number of months which exceeds the maximum allowed for the specific country.', WTV_Plugin::TEXTDOMAIN ),
                'help_link' => 'https://docs.wetail.io/woocommerce/visma-integration/felkoder/#4021'
            ],
            '4022' => [
                'message' => __( 'This error is thrown when you are trying to create a fiscal year which doesnt end in the last day of a month.', WTV_Plugin::TEXTDOMAIN ),
                'help_link' => 'https://docs.wetail.io/woocommerce/visma-integration/felkoder/#4022'
            ],
            '4100' => [
                'message' => __( 'This error is thrown when a validation from an external service is thrown', WTV_Plugin::TEXTDOMAIN ),
                'help_link' => 'https://docs.wetail.io/woocommerce/visma-integration/felkoder/#4100'
            ],
            '5000' => [
                'message' => __( 'This error is thrown when you encounter a error that we have not handled on our side. This can occur if there is a bug in eAccounting. These errors should be reported to us at eaccountingapi@visma,com.', WTV_Plugin::TEXTDOMAIN ),
                'help_link' => 'https://docs.wetail.io/woocommerce/visma-integration/felkoder/#5000'
            ],
            '5001' => [
                'message' => __( 'This error is thrown when you make a request towards and endpoint with external service dependencies, and that dependency is not answering in time.This can occur if a service is down.', WTV_Plugin::TEXTDOMAIN ),
                'help_link' => 'https://docs.wetail.io/woocommerce/visma-integration/felkoder/#5001'
            ],
            '5002' => [
                'message' => __( 'This error is thrown when you encounter a error that we have not handled on our side. These errors should be reported to us at eaccountingapi@visma.com.', WTV_Plugin::TEXTDOMAIN ),
                'help_link' => 'https://docs.wetail.io/woocommerce/visma-integration/felkoder/#5002'
            ],
            '5003' => [
                'message' => __( 'This error is thrown when an article cannot be changed to non stock article because it has non zero stock balance.', WTV_Plugin::TEXTDOMAIN ),
                'help_link' => 'https://docs.wetail.io/woocommerce/visma-integration/felkoder/#5003'
            ],
            '5004' => [
                'message' => __( 'This error is thrown when a stock article cannot be changed to service type article because it has non zero stock balance.', WTV_Plugin::TEXTDOMAIN ),
                'help_link' => 'https://docs.wetail.io/woocommerce/visma-integration/felkoder/#5004'
            ],
            '6000' => [
                'message' => __( 'This error is thrown when you make a request with $filter parameters and the query is invalid.', WTV_Plugin::TEXTDOMAIN ),
                'help_link' => 'https://docs.wetail.io/woocommerce/visma-integration/felkoder/#6000'
            ],
            '6001' => [
                'message' => __( 'This error is thrown when you make a request with DateTime properties or filtering and the provided format is wrong.', WTV_Plugin::TEXTDOMAIN ),
                'help_link' => 'https://docs.wetail.io/woocommerce/visma-integration/felkoder/#6001'
            ],
            '10001' => [
                'message' => __( 'This error is thrown when the API module is not activated in Visma.', WTV_Plugin::TEXTDOMAIN ),
                'help_link' => 'https://docs.wetail.io/woocommerce/visma-integration/felkoder/#10001'
            ],
            '10002' => [
                'message' => '',
                'help_link' => ''
            ]
        ];
    }

    public static function get_error_message( $developer_message, $code ){
        wtv_write_log($developer_message);
        $error_messages = self::error_messages();

        if ( intval($code) == 4002 and strpos( $developer_message, 'No access to module' ) != 0 ){
	            if ( strpos( $developer_message, 'api_standard' ) != 0 ){
                $code = '10001';
            }
        }

        $error_message = $error_messages[$code];
        $message = $error_message['message'] . ' - ' .$developer_message;

        if( $code == 9999 ){
            return $developer_message;
        }

        if( $error_message['help_link'] != '' ){
            $message .= '<a href="' . $error_message['help_link'] .'">LÄS MER</a>';
        }
        if( empty( $message ) ){
            return $developer_message;
        }
        return $message;

    }
}
