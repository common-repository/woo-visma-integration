<?php


namespace includes\woo_api;

use includes\http\WTV_Auth;
use includes\WTV_Plugin;
use WP_Error;
use WP_REST_Controller;
use WP_REST_Response;

class WTV_Callback_Controller extends WP_REST_Controller{

    /**
     * Inits callback route
     */
    public function register_routes(){

        register_rest_route( WTV_API_NAMESPACE, '/callback/', array(
            'methods' => 'GET',
            'callback' => array( $this, 'handle_request' ),
        ));

    }

    /**
     *  Inits callback request that Visma calls for auth data
     * @param \WP_REST_Request $request
     * @return WP_Error|WP_REST_Response
     */
    public function handle_request( $request ){
        $query_params = $request->get_query_params();

        if( array_key_exists( 'code', $query_params ) ){
            WTV_Auth::authenticate( $query_params['code'] );
            wp_redirect( admin_url() . 'options-general.php?page=visma' );
            exit;
        }
        else{
            echo __( 'Something went wrong', WTV_Plugin::TEXTDOMAIN );
        }
    }

}