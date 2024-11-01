<?php

namespace includes\utils;
use Automattic\WooCommerce\Utilities\OrderUtil;

use WC_Countries;

class WTV_Utils
{

    /**
     * Returns true if country_code is EU
     *
     * @param string $country_code
     * @return bool
     */
    public static function is_outside_eu( $country_code ) {
        $countries = new WC_Countries();
        if ( $country_code == 'SE' || in_array( $country_code, $countries->get_european_union_countries() ) ) {
            return false;
        }
        return true;
    }

    /**
     * @param $order_id
     * @return \stdClass|\WC_Order[]
     */
    public static function get_refunds( $order_id ){
        return wc_get_orders(
            array(
                'type'   => 'shop_order_refund',
                'parent' => intval( $order_id ),
                'return' => 'ids',
                'limit'  => -1,
            )
        );
    }

    /**
     * Returns tax rates
     * @param \WC_Product $product
     * @param $country_code
     * @return int
     */
    public static function get_wc_tax_rate( $product, $country_code ){

        foreach( \WC_Tax::get_rates_for_tax_class( $product->get_tax_class() ) as $tax_rate){
            if ( $country_code == $tax_rate->tax_rate_country ){
                return $tax_rate->tax_rate;
            }
        }

        foreach( \WC_Tax::get_rates_for_tax_class( $product->get_tax_class() ) as $tax_rate){

            if( '' == $tax_rate->tax_rate_country ){
                return $tax_rate->tax_rate;
            }
        }
        return 0;
    }

    /**
     * @param $option
     * @param $needed
     * @return string
     */
    public static function setting_needed( $option, $needed ){

        if( 'create_orders' === get_option( 'visma_sync_order_method' ) ){
            return true;
        }
        if( $needed ){
            $setting = get_option( $option );
            if ( empty( $setting ) ) {
                return false;
            }
        }
        return true;

    }

    /**
     * Numberformatting function
     * @param $amount
     * @param $decimals
     * @return string
     */
    public static function format_number( $amount, $decimals=2 ){
        return (string)number_format( floatval( $amount ), $decimals ,  "." , $thousands_sep = "" );
    }

    /** Returns true if customer is a private person, if so VAT should be included according to Visma
     * @param $wc_order
     * @return bool
     */
    public static function include_vat( $wc_order ){
        $address = $wc_order->get_address();
        return empty( $address['company'] ) ? true : false;
    }

    public static function get_enabled_payment_gateways() {
        $_enabled_gateways = array();

        foreach ( WC()->payment_gateways()->payment_gateways as $gateway ) {
            if ( 'yes' === $gateway->enabled ) {
                $_enabled_gateways[ $gateway->id ] = $gateway;
            }
        }

        return $_enabled_gateways;
    }

    public static function get_country_code( $wc_order ) {
        $country_code = ! empty( $wc_order->get_shipping_country() ) ? $wc_order->get_shipping_country() : $wc_order->get_billing_country();
        return empty( $country_code ) ? get_option('woocommerce_default_country') : $country_code;
    }

    /** Fetches meta data from order. If HPOS is not available then reads from postmeta table
     * @param $wc_order_id
     * @param $meta_key
     * @return array|mixed|string
     */
    public static function get_order_meta_compat( $wc_order_id, $meta_key ){

        if( 0 === $wc_order_id ){
            return;
        }

        if ( ! defined( 'UNITTESTS' ) && OrderUtil::custom_orders_table_usage_is_enabled() ) {
            $wc_order = wc_get_order( $wc_order_id );
            if( ! $wc_order ){
                return;
            }
            return $wc_order->get_meta( $meta_key );
        } else {
            return get_post_meta( $wc_order_id, $meta_key, true );
        }
    }

    /** Fetches meta data from order. If HPOS is not available then reads from postmeta table
     * @param $wc_order_id
     * @param $meta_key
     * @param $value
     * @param $save
     * @return array|mixed|string
     */
    public static function update_order_meta_compat( $wc_order_id, $meta_key, $value, $save=true ){

        if( 0 === $wc_order_id ){
            return;
        }
        $wc_order = wc_get_order( $wc_order_id );

        if( ! $wc_order ){
            return;
        }

        $wc_order->update_meta_data( $meta_key, $value );

        if ( ! defined( 'UNITTESTS' ) && OrderUtil::custom_orders_table_usage_is_enabled() ) {
            if( ! $save ){
                return;
            }
            $wc_order->save();
        } else {
            $wc_order->save_meta_data();
        }
    }
}
