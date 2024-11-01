<?php

namespace includes\utils;

require( WTV_PATH . '/vendor/autoload.php');

class WTV_Currency_Converter
{
    const CURRENCY_API_URL = 'https://api.currencyapi.com/v3/';
    const CURRENCY_API_ACCESS_KEY = 'KBGLqS6bP4FVl7QGQeM6UCs66HijplXwHqatZHxQ';

    private $rates;

    public function __construct( $from_currency, $date ){
        $this->from_currency = $from_currency;
        $this->date = date('Y-m-d H:i:s', strtotime( $date ) );

        if ( 'SEK' != $this->from_currency ) {
            $cached_rates = get_transient( $this->from_currency . '_' . $this->date );
            if ( ! $cached_rates ) {
                $this->get_rates();
            }
            else{
                $this->rates = $cached_rates;
            }
        }
    }

    private function get_rates(){

        $options = array(
            'http_errors' => false,
            'headers' => [
                'apikey' => self::CURRENCY_API_ACCESS_KEY
            ],
        );

        if( substr(strval( $this->date ),0,10 ) == strval( date('Y-m-d' ) ) ){
            $response = wp_remote_get( self::CURRENCY_API_URL . 'latest?currencies=EUR,NOK,USD,DKK,GBP,CHF,SEK,AUD,CAD&base_currency=' . $this->from_currency, $options );
        }
        else{
            $response = wp_remote_get( self::CURRENCY_API_URL . 'historical?date=' .  strval($this->date) . '&currencies=EUR,NOK,USD,DKK,GBP,CHF,SEK,AUD,CAD&base_currency=' . $this->from_currency, $options );
        }

        $data = json_decode( $response['body'], true );
        $this->rates = $data['data'];
        set_transient( $this->from_currency . '_' . $this->date, $this->rates, 60*60*24 );
    }

    public function convert( $amount ){

        if ( 'SEK' == $this->from_currency ){
            return WTV_Utils::format_number( $amount );
        }
        $amount_base_currency = $amount / $this->rates[$this->from_currency]['value'];
        $amount_in_sek = $amount_base_currency * $this->rates['SEK']['value'];
        return WTV_Utils::format_number( $amount_in_sek );
    }
}
