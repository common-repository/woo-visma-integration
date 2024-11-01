<?php

namespace includes;

use Exception;
use includes\api\WTV_Sync_Controller;
use includes\utils\WTV_Utils;
use WC_Countries;

class WTV_Settings_Validator{


    const ACCOUNTING_OPTION_NAMES = [
            'visma_shipping_account_eu',
            'visma_shipping_account_world',
            'visma_domestic_vat_25_account',
            'visma_domestic_vat_12_account',
            'visma_domestic_vat_6_account',
            'visma_eu_vat_25_account',
            'visma_eu_vat_12_account',
            'visma_eu_vat_6_account'
        ];

    /**
     * Check if all required settings are there
     * TODO REMOVE FUNCTION
     */
    public static function all_settings_are_valid(){
        $valid = false;
        if( 'create_vouchers' === get_option('visma_sync_order_method') ){
            if ( get_option( 'visma_accounting_settings_are_valid' ) ){
                $valid = true;
            }
        }
        elseif( 'create_orders' === get_option('visma_sync_order_method') ){
            if ( get_option( 'visma_order_sync_settings_are_valid' ) ){
                $valid = true;
            }
        }
        if ( get_option( 'visma_general_settings_are_valid' ) && $valid ){
            return true;
        }

        return true;
    }

    /**
     * Check all required order settings are there
     */
    public static function visma_settings_are_present(){
        if ( empty( get_option( 'visma_terms_of_payments', [] ) ) ){
            delete_option( 'visma_general_settings_are_valid' );
            return false;
        }
        update_option( 'visma_general_settings_are_valid', true );
        return true;
    }

    /**
     * Check all required order settings are there
     */
    public static function order_sync_settings_are_valid(){
        $valid = false;
        foreach ( WTV_Utils::get_enabled_payment_gateways() as $gateway ) {
            $settings = get_option( 'visma_order_sync_settings_' . $gateway->id );
            if( ! empty( $settings ) ){
                $valid = true;
            }
        }
        update_option( 'visma_order_sync_settings_are_valid', $valid );
        return $valid;
    }

    /**
     * Check if all required accounting settings are there
     */
    public static function accounting_settings_are_valid(){
        $setting = get_option( 'visma_shipping_account_se' );
        if ( ! $setting ) {
            delete_option( 'visma_accounting_settings_are_valid' );
            return false;
        }

        $country_settings = self::get_country_settings();

        if ( $country_settings['sells_to_eu'] ){
            $setting = get_option( 'visma_shipping_account_eu' );
            if ( ! $setting ) {
                delete_option( 'visma_accounting_settings_are_valid' );
                return false;
            }
        }

        if ( $country_settings['sells_to_world'] ){
            $setting = get_option( 'visma_shipping_account_world' );
            if ( ! $setting ) {
                delete_option( 'visma_accounting_settings_are_valid' );
                return false;
            }
        }

        $domestic_vat_array = [];

        $tax_settings = self::get_tax_settings();

        if( $tax_settings['25'] ){
            $domestic_vat_array[] = 'visma_domestic_vat_25_account';
        }

        if( $tax_settings['12'] ){
            $domestic_vat_array[] = 'visma_domestic_vat_12_account';
        }

        if( $tax_settings['6'] ){
            $domestic_vat_array[] = 'visma_domestic_vat_6_account';
        }

        foreach ( $domestic_vat_array as $domestic_vat ) {
            $setting = get_option( $domestic_vat );
            if ( ! $setting ) {
                delete_option( 'visma_accounting_settings_are_valid' );
                return false;
            }
        }

        $country_settings = self::get_country_settings();

        if ( $country_settings['sells_to_eu'] ){
            $eu_vat_array = [];
            if( $tax_settings['25'] ){
                $eu_vat_array[] = 'visma_eu_vat_25_account';
            }

            if( $tax_settings['12'] ){
                $eu_vat_array[] = 'visma_eu_vat_12_account';
            }

            if( $tax_settings['6'] ){
                $eu_vat_array[] = 'visma_eu_vat_6_account';
            }

            foreach ( $eu_vat_array as $eu_vat ) {
                $setting = get_option( $eu_vat );
                if ( ! $setting ) {
                    delete_option( 'visma_accounting_settings_are_valid' );
                    return false;
                }
            }
        }

        update_option( 'visma_accounting_settings_are_valid', true );
        return true;

    }


    /**
     * Returns which regions the shop is selling to
     * @return array
     */
    public static function get_country_settings( ){

        $settings = array(
            'sells_to_se'       => false,
            'sells_to_eu'       => false,
            'sells_to_world'    => false,
        );
        $countries = new WC_Countries();
        foreach( $countries->get_allowed_countries() as $country_code => $value){

            if ( $country_code == 'SE' ) {
                $settings['sells_to_se'] = true;
            }
            else if ( in_array( $country_code, $countries->get_european_union_countries() ) ){
                $settings['sells_to_eu'] = true;
            }
            else{
                $settings['sells_to_world'] = true;
            }

            if ( $settings['sells_to_eu'] && $settings['sells_to_world'] && $settings['sells_to_se']){
                return $settings;
            }
        }

        return $settings;
    }

    /**
     * Returns which tax rates the shop is selling to
     * @return array
     */
    public static function get_tax_settings( ){

        $settings = array(
            '25'    => false,
            '12'    => false,
            '6'     => false,
        );
        foreach( \WC_Tax::get_tax_classes() as $tax_class_name){
            foreach( \WC_Tax::get_rates_for_tax_class($tax_class_name) as $entry){
                if( 25 == intval( $entry->tax_rate ) ){
                    $settings['25'] = true;
                }
                elseif ( 12 == intval( $entry->tax_rate ) ){
                    $settings['12'] = true;
                }
                elseif ( 6 == intval( $entry->tax_rate ) ){
                    $settings['6'] = true;
                }
                if ( $settings['25'] && $settings['12'] && $settings['6'] ){
                    return $settings;
                }
            }
        }
        return $settings;
    }

    /**
     * Check if all required settings are there
     */
    public static function validate_settings(){
        if ( empty( get_option( 'visma_terms_of_payments', [] ) ) ){
            return false;
        }
        return true;
    }

    /**
     * Validates settings for payment method
     * @param \WC_Order $payment_method
     * @throws Exception
     */
    public static function validate_settings_payment_method( $order ){
        $payment_method = $order->get_payment_method();
        $payment_method_title = $order->get_payment_method_title();
        $settings = get_option( 'visma_order_sync_settings_' . $payment_method );

        if( empty( $settings ) ){
            throw new Exception( __( 'Visma: Inställningar saknas för ' . $payment_method_title, WTV_Plugin::TEXTDOMAIN ), WTV_Plugin::INTERNAL_EXCEPTION );
        }

        if( WTV_Sync_Controller::should_create_voucher() ){
            if( !isset( $settings['payment_account'] ) || empty( $settings['payment_account'] ) ){
                throw new Exception( __( 'Visma: Bokföringskonto saknas för betalsätt ' . $payment_method_title, WTV_Plugin::TEXTDOMAIN ), WTV_Plugin::INTERNAL_EXCEPTION );
            }

            if( !isset( $settings['voucher_series'] )  || empty( $settings['voucher_series'] ) ){
                throw new Exception( __( 'Visma: Verifikationsserie saknas för betalsätt ' . $payment_method_title, WTV_Plugin::TEXTDOMAIN ), WTV_Plugin::INTERNAL_EXCEPTION );
            }
        }
        elseif ( WTV_Sync_Controller::should_create_order() ){
            if( !isset( $settings['order_status'] ) || empty( $settings['order_status'] ) ){
                throw new Exception( __( 'Visma: Order status saknas för betalsätt ' . $payment_method_title, WTV_Plugin::TEXTDOMAIN ),WTV_Plugin::INTERNAL_EXCEPTION );
            }

            if( !isset( $settings['terms_of_payment_id'] ) || empty( $settings['terms_of_payment_id'] ) ){
                throw new Exception( __( 'Visma: Betalningsvillkor saknas för betalsätt' . $payment_method_title, WTV_Plugin::TEXTDOMAIN ),WTV_Plugin::INTERNAL_EXCEPTION );
            }

            if( isset( $settings['order_convert_to_invoice'] ) && ! empty( $settings['order_convert_to_invoice'] ) && $settings['order_convert_to_invoice'] == 1){
                if( $settings['order_status'] != 3 ){ //TODO KALLE, det går inte att göra en faktura om order inte har status skickad
                    throw new Exception( __( 'Visma: När en order ska konverteras till faktura måste order vara satt på status SKICKAD. Byt order status i Visma inställnignarna för betalmetod ', WTV_Plugin::TEXTDOMAIN ),WTV_Plugin::INTERNAL_EXCEPTION );
                }
            }

            if( isset( $settings['order_add_invoice_payment'] ) && ! empty( $settings['order_add_invoice_payment'] ) && $settings['order_add_invoice_payment'] == 1){
                if( !isset( $settings['order_convert_to_invoice'] ) && empty( $settings['order_convert_to_invoice']  && $settings['order_convert_to_invoice'] == 1) ){
                    throw new Exception( __( 'Visma: Det går ej att skapa en betalning om ordern ej fakturerats ' , WTV_Plugin::TEXTDOMAIN ),WTV_Plugin::INTERNAL_EXCEPTION );
                }
            }
        }
        else{
            throw new Exception( __( 'Visma: Det saknas inställningar för att synkronisera ordrar ' , WTV_Plugin::TEXTDOMAIN ),WTV_Plugin::INTERNAL_EXCEPTION );

        }

    }
}
