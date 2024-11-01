<?php

namespace includes\http;

require( WTV_PATH . '/vendor/autoload.php');

use Exception;
use includes\wetail\WTV_Credentials;
use includes\WTV_Plugin;

class WTV_Request
{

    const API_TEST_ENDPOINT = 'https://eaccountingapi-sandbox.test.vismaonline.com/';
    const API_ENDPOINT = 'https://eaccountingapi.vismaonline.com/';
    const ERROR_CODE_UNAUTHORIZED = 1;
    const ERROR_CODE_DUPLICATE = 2;
    const ERROR_CODE_BAD_REQUEST = 3;

    /**
     * Clean Data
     *
     * @param $data
     * @return mixed
     */
    public static function clean_data( $data )
    {
        if ( is_array($data)) {
            foreach ($data as $k => $v) {
                $data[$k] = self::clean_data($v);
            }
        } else if (is_string ($data)) {
            return preg_replace('/[\/]/', '_', $data );
        }
        return $data;
    }

    /**
     * Extract error code from error response
     * @param $response
     * @return mixed
     */
	public static function get_error_code( $response )
	{
		if( ! empty( $response->ErrorInformation->Code ) )
			return $response->ErrorInformation->Code;

		if( ! empty( $response->ErrorInformation->code ) )
			return $response->ErrorInformation->code;

	}

    /**
     * Performs GET request
     * @param string $endpoint
     * @param array $params
     * @param string $version
     * @return mixed
     * @throws Exception
     */
    public static function get( $endpoint, $params=[], $version='v2' ){

        if ( ! WTV_Credentials::check() ){
            throw new Exception( __( "Licensnyckel saknas eller är ogiltig.", WTV_Plugin::TEXTDOMAIN ), WTV_Plugin::INTERNAL_EXCEPTION );
        }
        wtv_write_log($endpoint);
        $api_endpoint = get_option('visma_test') ? self::API_TEST_ENDPOINT : self::API_ENDPOINT;
        $response = wp_remote_get( $api_endpoint . $version . $endpoint, array( 'headers' => WTV_Auth::get_auth_headers(), 'timeout' => 20 ) );
        return self::handle_response( $response, $endpoint, 'GET');
    }


    /**
     * Handle response
     *
     * @param $response
     * @param $endpoint
     * @param $method
     * @param array $params
     * @return mixed
     * @throws Exception
     */
    public static function handle_response( $response, $endpoint, $method, $params=[]  )
    {
        if( is_a( $response, 'WP_Error' ) ){
            throw new \Exception( $response->get_error_message(), WTV_Plugin::INTERNAL_EXCEPTION );
        }

        $data = json_decode( $response['body'] );

        if( intval( $response['response']['code'] ) >= 400 ){
            wtv_write_log('Request failed ' . $method . ' ' . $endpoint . ' ' . $response['response']['code'] . print_r( $response , true) . ' ' . print_r( $params , true));
            $error = $data;
            if( 409 === intval( $response['response']['code'] ) ){
                throw new Exception('Request failed ' . $response['response']['code'] . ' ' . print_r( $response , true), self::ERROR_CODE_DUPLICATE );
            }
            throw new Exception( $error->DeveloperErrorMessage, $error->ErrorCode );
        }
        wtv_write_log($data);
        return $data;
    }

    /**
     * Performs POST request
     * @param string $endpoint
     * @param array $params
     * @param string $version
     * @return mixed
     * @throws Exception
     */
    public static function post( $endpoint, $params, $version='v2' ){

        if ( ! WTV_Credentials::check() ){
            throw new Exception( __( "Licensnyckel saknas eller är ogiltig.", WTV_Plugin::TEXTDOMAIN ), WTV_Plugin::INTERNAL_EXCEPTION );
        }

        $api_endpoint = get_option('visma_test') ? self::API_TEST_ENDPOINT : self::API_ENDPOINT;

        $args = [
            'headers' => WTV_Auth::get_auth_headers(),
            'timeout' => 20,
            'body' => json_encode( self::clean_data( $params ) ),
            'method' => 'POST',
            'data_format' => 'body'
        ];

        $response = wp_remote_post( $api_endpoint . $version . $endpoint, $args );

        wtv_write_log( $endpoint );
        wtv_write_log( $params );

        return self::handle_response( $response, $endpoint, 'POST',  $params );
    }

    /**
     * Perform PUT request
     * @param string $endpoint
     * @param array $params
     * @param string $version
     * @return mixed
     * @throws Exception
     */
    public static function put( $endpoint, $params, $version='v2' ){
        if ( ! WTV_Credentials::check() ){
            throw new Exception( __( "Licensnyckel saknas eller är ogiltig.", WTV_Plugin::TEXTDOMAIN ), WTV_Plugin::INTERNAL_EXCEPTION );
        }

        $api_endpoint = get_option('visma_test') ? self::API_TEST_ENDPOINT : self::API_ENDPOINT;

        $args = [
            'headers' => WTV_Auth::get_auth_headers(),
            'body' =>  json_encode( self::clean_data( $params ) ),
            'method' => 'PUT',
            'data_format' => 'body',
            'timeout' => 20
        ];

        $response = wp_remote_post( $api_endpoint . $version . $endpoint, $args );

        return self::handle_response( $response, $endpoint, 'PUT', $params );
    }

    /**
     * Perform DELETE request
     * @param string $endpoint
     * @param array $params
     * @param string $version
     * @return mixed
     * @throws Exception
     */
    public static function delete( $endpoint, $version='v2' ){
        if ( ! WTV_Credentials::check() ){
            throw new Exception( __( "Licensnyckel saknas eller är ogiltig.", WTV_Plugin::TEXTDOMAIN ), WTV_Plugin::INTERNAL_EXCEPTION );
        }

        $api_endpoint = get_option('visma_test') ? self::API_TEST_ENDPOINT : self::API_ENDPOINT;

        $args = [
            'headers' => WTV_Auth::get_auth_headers(),
            'method' => 'DELETE',
            'timeout' => 20
        ];

        $response = wp_remote_request( $api_endpoint . $version . $endpoint, $args );

        return self::handle_response( $response, $endpoint, 'PUT', [] );
    }
}
