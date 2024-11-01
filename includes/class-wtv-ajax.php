<?php

namespace includes;

use Exception;
use includes\http\WTV_Auth;
use includes\wetail\WTV_Credentials;
use includes\api\WTV_Error_Handling;

class WTV_Ajax {

    const WTV_ACTION_AJAX_GET_AUTH_URL = "visma_get_auth_url";
    const WTV_ACTION_AJAX_SYNC_PRODUCT = "sync_product";
    const WTV_ACTION_AJAX_SYNC_ORDER = "sync_order";
    const WTV_ACTION_AJAX_SYNC_MULTIPLE_ORDERS = "visma_sync_orders_date_range";
    const WTV_ACTION_AJAX_SYNC_MULTIPLE_PRODUCTS = "visma_sync_products";
    const WTV_ACTION_AJAX_UPDATE_SETTINGS = "visma_update_settings";

	/**
	 * Send AJAX response
	 *
	 * @param array $data
	 */
	public static function respond( $data = [] )
	{
		$defaults = [
			'error' => false
		];
		$data = array_merge( $defaults, $data );
		wtv_write_log($data);
		die( json_encode( $data ) );
	}

	/**
	 * Send AJAX error
	 *
	 * @param string $message
	 */
	public static function error( $message )
	{
		self::respond(
			[
				'message' => $message,
				'error' => true
			]
		);
	}

	/**
	 * Update settings through AJAX
	 */
	public static function update_setting()
	{
		if( ! empty( $_REQUEST['settings'] ) )
			foreach( $_REQUEST['settings'] as $option => $value )
				if( 0 === strpos( $option, 'visma_' ) )
					update_option( $option, $value );

		self::respond();
	}

    /**
     * Process AJAX request
     */
    public static function admin_action(){

        try{
            switch ( $_REQUEST['visma_action'] ) {
                case self::WTV_ACTION_AJAX_UPDATE_SETTINGS:
                    $response = WTV_Admin_Actions::update_settings();
                    break;
                case self::WTV_ACTION_AJAX_GET_AUTH_URL:
                    $response =  WTV_Auth::get_authorize_url();
                    break;
                case self::WTV_ACTION_AJAX_SYNC_MULTIPLE_ORDERS:
                    $response =  WTV_Admin_Actions::bulk_sync_orders();
                    break;
                case self::WTV_ACTION_AJAX_SYNC_MULTIPLE_PRODUCTS:
                    $response =  WTV_Admin_Actions::bulk_sync_products();
                    break;
                case self::WTV_ACTION_AJAX_SYNC_ORDER:
                    if ( empty( $_REQUEST['order_id'] ) ) {
                        return [
                            'error' => true,
                            'message' => __( "Order ID saknas.", WTV_Plugin::TEXTDOMAIN )
                        ];
                    }
                    $response = WTV_Admin_Actions::sync_order( $_REQUEST['order_id'] );
                    break;
                case self::WTV_ACTION_AJAX_SYNC_PRODUCT:
                    $response = WTV_Admin_Actions::sync_product();
                    break;
                default:
                    $response = [
                        'error' => true,
                        'message' => __( "Ogiltig action.", WTV_Plugin::TEXTDOMAIN )
                    ];
                    break;
            }
        }
        catch ( Exception $error ){
            $response = [
                'error' => true,
                'message' => WTV_Error_Handling::get_error_message( $error->getMessage(), $error->getCode() )
            ];
        }

        self::respond( $response );
    }

	# Check API key
	public static function check_wetail_visma_license_key()
	{
		update_option('wetail_visma_license_key' , $_REQUEST[ 'key' ] );

		if( ! get_option( 'wetail_visma_license_key' ) )
			self::error( __( "Licensnyckel saknas", WTV_Plugin::TEXTDOMAIN ) );

		if ( WTV_Credentials::Check() ) {
			self::respond( [ 'message' => __( "Licensnyckeln är giltig.", WTV_Plugin::TEXTDOMAIN ) ] );
		} else{
			self::error( __( "Wetail licensnyckel är ogiltig", WTV_Plugin::TEXTDOMAIN ) );
		}
	}
}
