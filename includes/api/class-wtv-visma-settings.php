<?php

namespace includes\api;

use Exception;
use includes\utils\WTV_Utils;
use includes\WTV_Plugin;
use includes\http\WTV_Request;

class WTV_Visma_Settings {

    /**
     * Get settings from visma
     *
     * 
     * @return array
     * @throws Exception
     */
    static private function get_settings() {
        try{

            $payment_accounts_url = get_option('woocommerce_default_country') == 'SE' ? '/accounts?$pagesize=1000&$filter=Number%20gt%201000%20and%20Number%20lt%202000%20and%20IsActive%20eq%20true' : '/accounts?$pagesize=1000&$filter=Number%20gt%201000%20and%20Number%20lt%208000%20and%20IsActive%20eq%20true';

            return array(
                'terms_of_payments'     => WTV_Request::get( '/termsofpayments' ),
                'account_coding'        => WTV_Request::get( '/articleaccountcodings', $params=[], $version='v2' ),
                'units'                 => WTV_Request::get( '/units?$pagesize=1000', $params=[], $version='v2'  ),
                'payment_accounts'      => WTV_Request::get( $payment_accounts_url ),
                'revenue_accounts'      => WTV_Request::get( '/accounts?$pagesize=1000&$filter=Number%20gt%203000%20and%20Number%20lt%204000%20and%20IsActive%20eq%20true' ),
                'bank_accounts'         => WTV_Request::get( '/bankaccounts' ),
            );
        }
        catch ( Exception $error){
            wtv_write_log( $error->getMessage() );
            throw new Exception( __( 'Hämtning av inställningar från Visma misslyckades' , WTV_Plugin::TEXTDOMAIN ), WTV_Plugin::INTERNAL_EXCEPTION );
        }

    }

    /**
     * Get settings from visma and saves locally
     *
     * 
     * @param $accounts
     * @return array
     */
    static private function clean_accounts( $accounts ) {
        $ret = [];
        foreach ( $accounts as $account ) {
            $ret[] = $account->Number;
        }

        return $ret;
    }

    /**
     * Get settings from visma and saves locally
     *
     * 
     * @param $accounts
     * @return array
     */
    static private function clean_bank_accounts( $accounts ) {
        $ret = [];
        foreach ( $accounts as $account ) {
            $ret[] = [
                'value' => $account->Id,
                'label' => $account->Name
            ];
        }

        return $ret;
    }

    /**
     * Get settings from visma and saves locally
     *
     * 
     * @param $terms_of_payments
     * @return array
     */
    static private function clean_terms_of_payments( $terms_of_payments ) {
        $ret = [];
        foreach ( $terms_of_payments as $terms_of_payment ) {
            $ret[] = array(
                'id' => $terms_of_payment->Id,
                'name' => $terms_of_payment->Name

            );
        }

        return $ret;
    }

    /**
     * Get settings from visma and saves locally
     *
     * 
     */
    static public function get_and_save_settings() {
        $settings = self::get_settings();

        update_option( 'visma_terms_of_payments', self::clean_terms_of_payments( $settings['terms_of_payments']->Data ) );
        update_option( 'visma_account_coding', $settings['account_coding']->Data );
        update_option( 'visma_units', $settings['units']->Data );
        update_option( 'visma_payment_accounts', self::clean_accounts( $settings['payment_accounts']->Data ) );
        update_option( 'visma_revenue_accounts',  self::clean_accounts( $settings['revenue_accounts']->Data ) );
        update_option( 'visma_bank_accounts',  self::clean_bank_accounts( $settings['bank_accounts']->Data ) );
    }

    /**
     * Get Id for 'st'
     *
     * 
     * @return array
     * @throws Exception
     */
    static public function get_standard_unit_id() {
        $standard_unit = get_option( 'visma_default_unit', false );

        if ( empty( $standard_unit) ) {
            $units = get_option( 'visma_units' );
            if( ! empty( $units ) ) {
                foreach ( $units as $unit ) {
                    if ( 'st' == $unit->Abbreviation ) {
                        return $unit->Id;
                    }
                }
            }
            throw new Exception( __( 'Standardenhet saknas. Hämta inställningar från Visma', WTV_Plugin::TEXTDOMAIN ), WTV_Plugin::INTERNAL_EXCEPTION );
        }
        return $standard_unit;
    }

    /**
     * Get account coding for given VAT percentage from visma
     *
     * @param int $vat_percentage
     * @param string $country_code
     * @return int
     * @throws Exception
     */
    static public function get_account_coding( $vat_percentage, $country_code='SE' ) {
        $account_codings = get_option( 'visma_account_coding' );
        if( ! empty( $account_codings ) ){
            foreach  ( $account_codings as $account_coding ){
                if( self::is_account_coding_matching( $account_coding, $vat_percentage ) ){
                    return $account_coding->Id;
                }
            }
        }
        throw new Exception( __( 'Artikelkontering saknas. Hämta inställningar från Visma', WTV_Plugin::TEXTDOMAIN ), WTV_Plugin::INTERNAL_EXCEPTION );
    }

    /**
     * Get account coding for given VAT percentage from visma
     *
     * @param int $vat_percentage
     * @param string $country_code
     * @return int
     * @throws Exception
     */
    static public function get_account_number( $vat_percentage, $order, $country_code='SE' ) {

        $account_codings = get_option( 'visma_account_coding' );
        $coding = null;
        if( ! empty( $account_codings ) ){
            foreach  ( $account_codings as $account_coding ){

                if( self::is_account_coding_matching( $account_coding, $vat_percentage ) ){
                    $coding = $account_coding;
                }
            }

            if( $country_code == 'SE' ){
                if( 0 == (int)$vat_percentage ){
                    return $coding->DomesticSalesVatExemptAccountNumber;
                }
                return $coding->DomesticSalesSubjectToVatAccountNumber;
            }
            elseif ( ! WTV_Utils::is_outside_eu( $country_code ) ){

                if( WTV_Orders::has_eu_vat_number( $order ) ) {
                    return $coding->ForeignSalesVatExemptWithinEuAccountNumber;
                }
                return $coding->ForeignSalesSubjectToVatWithinEuAccountNumber;
            }
            else{
                return $coding->ForeignSalesVatExemptOutsideEuAccountNumber;
            }
        }
        throw new Exception( __( 'Konto saknas. Hämta inställningar från Visma', WTV_Plugin::TEXTDOMAIN ), WTV_Plugin::INTERNAL_EXCEPTION );
    }

    /**
     * Match account coding
     *
     * @param object $account_coding
     * @param string $vat_percentage
     * @return bool
     * @throws Exception
     */
    private static function is_account_coding_matching( $account_coding, $vat_percentage ){
        if( round($vat_percentage) . '%' == $account_coding->VatRate && 'Goods' == $account_coding->Type && boolval( $account_coding->IsActive ) && ! preg_match('/Expeditionsavgift/', $account_coding->Name ) ){
            return true;
        }
        if( strval(intval($vat_percentage)) . '%' == $account_coding->VatRate && 'Goods' == $account_coding->Type && boolval( $account_coding->IsActive ) && ! preg_match('/Expeditionsavgift/', $account_coding->Name ) ){
            return true;
        }
        return false;
    }
}
