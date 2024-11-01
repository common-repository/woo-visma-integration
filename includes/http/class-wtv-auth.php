<?php

namespace includes\http;

require(WTV_PATH . '/vendor/autoload.php');

use includes\WTV_Plugin;
use Exception;

class WTV_Auth
{

    const BASE_TEST_URI = 'https://identity-sandbox.test.vismaonline.com/connect/';
    const BASE_URI = 'https://identity.vismaonline.com/connect/';

    /**
     * Performs authenticate request
     * @param string $code
     * @return bool
     * @throws Exception
     */
    public static function authenticate( $code ){

        $api_endpoint = get_option('visma_test') ? self::BASE_TEST_URI : self::BASE_URI;
        $secret = get_option('visma_test') ? WTV_Plugin::CLIENT_TEST_SECRET : WTV_Plugin::CLIENT_SECRET;
        $args = [
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode( WTV_Plugin::CLIENT_ID . ':' . $secret )),
            'timeout' => 20,
            'body' => array(
                'code'          => $code,
                'grant_type'    => 'authorization_code',
                'redirect_uri'  => WTV_Auth::redirect_uri(),
            ),
            'method' => 'POST',
            'data_format' => 'body'
        ];

        $response = wp_remote_post( $api_endpoint . 'token', $args );

        $data = json_decode( $response['body'] );
        wtv_write_log( $data );

        if( intval( $response['response']['code'] ) >= 400 ){
            update_option( 'visma_needs_login', true );
            throw new Exception('Request failed' . $response['response']['code'], WTV_Plugin::INTERNAL_EXCEPTION );
        }

        WTV_Auth::set_auth_data( $data );
        WTV_Auth::set_appstore_status();
        return true;
    }

    /** Activates appstore status
     * @throws Exception
     */
    public static function set_appstore_status(){
        WTV_Request::put('/appstore/status', ['ActivationStatus'=> 1]);
    }

    /**
     * Returns Visma Authorize url
     * @return array
     */
    public static function get_authorize_url(){
        return [
            'error' => false,
            'message' => self::authorize_url()
        ];
    }
    /**
     * Returns Visma Authorize url
     * @return string
     */
    public static function authorize_url(){
        $uri = get_option('visma_test') ? self::BASE_TEST_URI : self::BASE_URI;
        return $uri . 'authorize?client_id=' . WTV_Plugin::CLIENT_ID . '&redirect_uri=' . self::redirect_uri() . '&scope=ea:accounting%20ea:purchase%20ea:sales%20ea:api%20offline_access&response_type=code&acr_values=service:44643eb1-3f76-4c1c-a672-402ae8085934+forceselectcompany:true';
    }



    /**
     * Returns Auth headers
     * @return array
     * @throws Exception
     */
    public static function get_auth_headers( ){

        if( WTV_Auth::is_access_token_valid() ){
            return [
                'Authorization' => 'Bearer ' . get_option( 'visma_access_token' ),
                'Accept'        => 'application/json',
                'Content-Type'  => 'application/json; charset=utf-8',
            ];
        }
        else{
            return [
                'Authorization' => 'Bearer ' . self::refresh_token(),
                'Accept'        => 'application/json',
                'Content-Type'  => 'application/json; charset=utf-8',
            ];
        }

    }

    /**
     * Returns if Visma Access Token is valid
     * @throws Exception
     */
	public static function is_access_token_valid(){
        return intval( get_option( 'visma_expiry_time' ) ) > time();
	}

    /**
     * Sets Access Token, Refresh Token and expiry time in wp_options table
     *
     * @param array $data
     */
    public static function set_auth_data( $data ){

        if ( property_exists( $data, 'access_token' ) ){
            update_option( 'visma_access_token', $data->access_token );
            delete_option( 'visma_needs_login' );
        }
        elseif ( property_exists( $data, 'id_token' ) ){
            update_option( 'visma_access_token', $data->id_token );
            delete_option( 'visma_needs_login' );
        }

        update_option( 'visma_refresh_token', $data->refresh_token );
        update_option( 'visma_expiry_time', time() + ( 57 * 60 ) );
    }

    /**
     * Returns the Redirect URI used for authentication
     *
     * @return string
     */
    public static function redirect_uri(){
        return rest_url() . 'visma/callback';
	}

    /**
     * Refreshes AccessToken
     * @return string
     * @throws Exception
     */
    public static function refresh_token( ){
        $api_endpoint = get_option('visma_test') ? self::BASE_TEST_URI : self::BASE_URI;
        $secret = get_option('visma_test') ? WTV_Plugin::CLIENT_TEST_SECRET : WTV_Plugin::CLIENT_SECRET;

        $args = [
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode( WTV_Plugin::CLIENT_ID . ':' . $secret )),
            'timeout' => 20,
            'body' => array(
                'refresh_token' => get_option( 'visma_refresh_token' ),
                'grant_type'    => 'refresh_token',
                'redirect_uri'  => WTV_Auth::redirect_uri(),
            ),
            'method' => 'POST',
            'data_format' => 'body'
        ];

        $response = wp_remote_post( $api_endpoint . 'token', $args );
        $data = json_decode( $response['body'] );
        wtv_write_log( $data );


        if( intval( $response['response']['code'] ) >= 400 ){
            update_option( 'visma_needs_login', true );
            throw new Exception('Request failed' . $response['response']['code'] , WTV_Plugin::INTERNAL_EXCEPTION );
        }

        self::set_auth_data( $data );
        return $data->access_token;
    }
}
