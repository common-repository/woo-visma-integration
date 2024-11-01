<?php

namespace includes\api;

use Exception;
use includes\utils\WTV_Currency_Converter;
use includes\utils\WTV_Utils;
use includes\views\WTV_Sync_Settings_View;
use includes\WTV_Plugin;
use WC_Order_Refund;
use includes\http\WTV_Request;

class WTV_Vouchers {

    public function __construct( $wc_order ){
        $this->wc_order = $wc_order;
        $this->converter = new WTV_Currency_Converter( $this->wc_order->get_currency(), substr( $this->wc_order->get_date_created(), 0, 10 ) );
    }

    /**
     * Static method to create an instance of WTV_Vouchers
     *
     * @param $wc_order_id
     * @param $refund_id
     * @return WTV_Vouchers
     */
    public static function trigger_credit_voucher( $wc_order_id, $refund_id ) {
        $class = new self( wc_get_order( $wc_order_id ) );
        $class->create_credit_voucher( $refund_id );
    }

    /**
     * Creates a voucher for an order in Visma
     *
     * 
     * @throws Exception
     */
    public function create_order_voucher(){
        $wc_order_number = apply_filters( 'woocommerce_order_number', $this->wc_order->get_id(), $this->wc_order );
        $visma_voucher = apply_filters( 'wetail_visma_voucher', [
            'VoucherText'   => 'Order #' . preg_replace('/\D/', '', $wc_order_number),
            'VoucherDate'   => substr( $this->wc_order->get_date_created(), 0, 10 ), # To cut off order time
            'NumberSeries'  => WTV_Sync_Settings_View::VOUCHER_SERIES_CHOICES[ get_option( 'visma_order_sync_settings_' . $this->wc_order->get_payment_method() )['voucher_series'] ]['name'],
            'Rows'          => self::format_voucher_rows()
        ], $this->wc_order );

        try{
            wtv_write_log( $visma_voucher );
            $response = WTV_Request::post( '/vouchers?useDefaultVoucherSeries=false', $visma_voucher );
            self::set_visma_voucher_id( $response->Id );
        }
        catch ( Exception $error ){
            wtv_write_log( $error->getMessage() );
            wtv_write_log( $visma_voucher );
            throw new Exception('Voucher Creation failed ' . $error->getMessage(), WTV_Plugin::INTERNAL_EXCEPTION );
        }

    }

    /**
     * Creates a credit voucher for an order in Visma
     *
     * 
     * @param int $refund_id
     * @throws Exception
     */
    public function create_credit_voucher( $refund_id ){

        $refund = new WC_Order_Refund( $refund_id );
        $wc_order_number = apply_filters( 'woocommerce_order_number', $this->wc_order->get_id(), $this->wc_order );

        $visma_voucher = apply_filters( 'wetail_visma_credit_voucher', [
            'VoucherText'   => 'Kreditering av order #' . preg_replace('/\D/', '', $wc_order_number),
            'VoucherDate'   => empty ( ! $refund_id ) ? substr( $refund->get_date_created(), 0, 10 ) : substr( $this->wc_order->get_date_created(), 0, 10 ), # To cut off order time
            'NumberSeries'  => WTV_Sync_Settings_View::VOUCHER_SERIES_CHOICES[ get_option( 'visma_order_sync_settings_' . $this->wc_order->get_payment_method() )['voucher_series'] ]['name'],
            'Rows'          => self::format_credit_voucher_rows()
        ], $this->wc_order );

        try{
            wtv_write_log( $visma_voucher );
            $response = WTV_Request::post( '/vouchers', $visma_voucher );
            self::set_visma_voucher_id( $response->Id, $credit=true );
        }
        catch ( Exception $error ){
            wtv_write_log( $error->getMessage() );
            wtv_write_log( $visma_voucher );
            throw new Exception('Voucher Creation failed ' . $error->getMessage(), WTV_Plugin::INTERNAL_EXCEPTION );
        }
    }

    /**
     * Returns the freight account for given country code
     *
     * @param string $country_code
     * @return mixed|void
     * @throws Exception
     */
    public static function get_freight_account( $country_code ){

        if( 'SE' == $country_code ){
            return get_option( 'visma_shipping_account_se');
        }
        elseif( ! WTV_Utils::is_outside_eu( $country_code ) ){
            return get_option( 'visma_shipping_account_eu');
        }
        else{
            return get_option( 'visma_shipping_account_world');
        }
    }

    /**
     * Returns the Vat percentage for item
     *
     * @param array $items
     * @param string $country_code
     * @return int
     * @throws Exception
     */
    public static function get_shipping_vat_percentage( $items, $country_code ){
        $row_totals = [];
        foreach ( $items as $item ){
            if( is_a( $item ,'WC_Order_Item_Product') ){
                $row_totals[] = floatval( $item->get_total() );
            }
        }

        foreach ( $items as $item ){
            if( is_a( $item ,'WC_Order_Item_Product') ){
                if( $item->get_total() == max( $row_totals ) ){
                    return WTV_Utils::get_wc_tax_rate( $item->get_product(), $country_code );
                }
            }
        }
        return 0;
    }

    /**
     * Returns visma Voucher Id
     *
     * @return int
     * @throws Exception
     */
    public function get_visma_voucher_id(){
        return $this->wc_order->get_meta('visma_voucher_id' );
    }

    /**
     * Returns visma Voucher Id
     *
     * @param int $wc_order_id
     * @return int
     * @throws Exception
     */
    public function get_visma_credit_voucher_id( $wc_order_id ){
        return $this->wc_order->get_meta('visma_credit_voucher_id' );
    }

    /**
     * Returns voucher from Visma
     *
     * @param int $wc_order_id
     * @param bool $credit
     * @return mixed
     * @throws Exception
     */
    public static function get_voucher( $wc_order_id, $credit=false ){
        $wc_order = wc_get_order( $wc_order_id );
        $visma_voucher_id = $wc_order->get_meta($credit ? 'visma_credit_voucher_id' : 'visma_voucher_id');

        if( ! $visma_voucher_id ){
            throw new Exception( __( 'Verifikation finns ej i Visma', WTV_Plugin::TEXTDOMAIN ), WTV_Plugin::INTERNAL_EXCEPTION );
        }
        $fiscal_years = WTV_Request::get( "/fiscalyears");

        foreach ($fiscal_years->Data as $fiscal_year ){
            if( strtotime( substr( $wc_order->get_date_created(), 0, 10 ) ) >= strtotime( $fiscal_year->StartDate ) && strtotime( substr( $wc_order->get_date_created(), 0, 10 ) ) <= strtotime( $fiscal_year->EndDate ) ){
                return WTV_Request::get( "/vouchers/{$fiscal_year->Id}/{$visma_voucher_id}");
            }
        }

        throw new Exception( __( 'Räkenskapsår för orderns datum kunde ej hittas', WTV_Plugin::TEXTDOMAIN ), WTV_Plugin::INTERNAL_EXCEPTION );
    }

    /**
     * Formats voucher rows
     *
     * @return array
     * @throws Exception
     */
    public function format_voucher_rows(){

        $country_code = WTV_Utils::get_country_code( $this->wc_order );
        foreach ( $this->wc_order->get_items( array( 'line_item', 'fee' ) ) as $item ){

            if( is_a( $item ,'WC_Order_Item_Product') ){
                if( 0 < $item->get_total() ) {
                    $vat_percentage = WTV_Utils::get_wc_tax_rate( $item->get_product(), 'SE');
                    $rows[] = array(
                        'AccountNumber'     => WTV_Visma_Settings::get_account_number( $vat_percentage, $this->wc_order , $country_code ),
                        'CreditAmount'      => $this->converter->convert( $item->get_total() )
                    );
                    if ( 'SE' == $country_code) {
                        if( $item->get_total_tax() ){
                            $rows[] = array(
                                'AccountNumber'     => get_option('visma_domestic_vat_' . intval($vat_percentage) . '_account'),
                                'CreditAmount'      => $this->converter->convert( $item->get_total_tax() )
                            );
                        }

                    } elseif ( ! WTV_Utils::is_outside_eu($country_code)) {
                        if( $item->get_total_tax() ) {
                            $rows[] = array(
                                'AccountNumber'     => get_option('visma_eu_vat_' . intval($vat_percentage) . '_account'),
                                'CreditAmount'      => $this->converter->convert( $item->get_total_tax() )
                            );
                        }
                    }
                }
            }
            elseif( is_a( $item ,'WC_Order_Item_Fee') ){
                if( 0 < $item->get_total() ) {
                    $rows[] = array(
                        'AccountNumber'     => '3540',
                        'CreditAmount'      => $this->converter->convert( $item->get_total() )
                    );
                    if ( 'SE' == $country_code) {
                        if( $item->get_total_tax() ){
                            $rows[] = array(
                                'AccountNumber'     => get_option('visma_domestic_vat_25_account'),
                                'CreditAmount'      => $this->converter->convert( $item->get_total_tax() )
                            );
                        }

                    } elseif ( ! WTV_Utils::is_outside_eu($country_code)) {
                        if( $item->get_total_tax() ) {
                            $rows[] = array(
                                'AccountNumber'     => get_option('visma_eu_vat_25_account'),
                                'CreditAmount'      => $this->converter->convert( $item->get_total_tax() )
                            );
                        }
                    }
                }
            }
        }

        if( ! empty( $this->wc_order->get_shipping_total() ) && intval( $this->wc_order->get_shipping_total() ) != 0 ){
            foreach ( self::format_shipping_rows( $country_code ) as $row ){
                $rows[] = $row;
            }
        }

        $rows[] = array(
            'AccountNumber'     => get_option( 'visma_order_sync_settings_' . $this->wc_order->get_payment_method() )['payment_account'],
            'DebitAmount'       => $this->get_total( $rows, 'CreditAmount' )
        );

        return $rows;
    }

    /**
     * Formats credit voucher rows
     *
     * @return array
     * @throws Exception
     */
    public function format_credit_voucher_rows(){
        $country_code = WTV_Utils::get_country_code( $this->wc_order );
        foreach ( $this->wc_order->get_items( array( 'line_item', 'fee' ) ) as $item ){

            if( is_a( $item ,'WC_Order_Item_Product') ){
                if( 0 < $item->get_total() ) {
                    $vat_percentage = WTV_Utils::get_wc_tax_rate( $item->get_product(), 'SE' );
                    $rows[] = array(
                        'AccountNumber' => WTV_Visma_Settings::get_account_number( $vat_percentage, $this->wc_order, $country_code ),
                        'DebitAmount'   => $this->converter->convert( $item->get_total() )
                    );
                    if ('SE' == $country_code) {
                        if( $item->get_total_tax() ){
                            $rows[] = array(
                                'AccountNumber' => get_option('visma_domestic_vat_' . intval($vat_percentage) . '_account'),
                                'DebitAmount'   => $this->converter->convert( $item->get_total_tax() )
                            );
                        }

                    } elseif (!WTV_Utils::is_outside_eu($country_code)) {
                        if( $item->get_total_tax() ){
                            $rows[] = array(
                                'AccountNumber' => get_option('visma_eu_vat_' . intval( $vat_percentage ) . '_account'),
                                'DebitAmount'   => $this->converter->convert( $item->get_total_tax() )
                            );
                        }
                    }
                }
            }
            elseif ( is_a( $item ,'WC_Order_Item_Fee') ){
                if( 0 < $item->get_total() ) {
                    $vat_percentage = WTV_Utils::get_wc_tax_rate( $item->get_product(), 'SE' );
                    $rows[] = array(
                        'AccountNumber' => '3540',
                        'DebitAmount'   => $this->converter->convert( $item->get_total() )
                    );
                    if ('SE' == $country_code) {
                        if( $item->get_total_tax() ){
                            $rows[] = array(
                                'AccountNumber' => get_option('visma_domestic_vat_' . intval($vat_percentage) . '_account'),
                                'DebitAmount'   => $this->converter->convert( $item->get_total_tax() )
                            );
                        }

                    } elseif (!WTV_Utils::is_outside_eu($country_code)) {
                        if( $item->get_total_tax() ){
                            $rows[] = array(
                                'AccountNumber' => get_option('visma_eu_vat_' . intval( $vat_percentage ) . '_account'),
                                'DebitAmount'   => $this->converter->convert( $item->get_total_tax() )
                            );
                        }
                    }
                }
            }
        }

        if( ! empty( $this->wc_order->get_shipping_total() ) ){
            foreach ( self::format_credit_shipping_rows( $country_code ) as $row ){
                $rows[] = $row;
            }
        }

        $rows[] = array(
            'AccountNumber' => get_option( 'visma_order_sync_settings_' . $this->wc_order->get_payment_method() )['payment_account'],
            'CreditAmount'   => $this->get_total( $rows, 'DebitAmount' )
        );

        return $rows;
    }

    /**
     * Formats credit voucher row for shipping
     *
     * @param $country_code
     * @return array
     * @throws Exception
     */
    public function format_credit_shipping_rows( $country_code ){
        $rows = [];
        $vat_percentage = self::get_shipping_vat_percentage( $this->wc_order->get_items(), $country_code );
        $rows[] = array(
            'AccountNumber' => self::get_freight_account( $country_code ),
            'DebitAmount'   => $this->converter->convert( $this->wc_order->get_shipping_total() )
        );

        if( 0 < $this->wc_order->get_shipping_tax() ){
            if( 'SE' == $country_code ){
                $rows[] = array(
                    'AccountNumber' => get_option( 'visma_domestic_vat_' . intval( $vat_percentage ) . '_account'),
                    'DebitAmount'   => $this->converter->convert( $this->wc_order->get_shipping_tax() )
                );
            }
            elseif( ! WTV_Utils::is_outside_eu( $country_code ) ){
                $rows[] = array(
                    'AccountNumber' => get_option( 'visma_eu_vat_' . intval( $vat_percentage ) . '_account'),
                    'DebitAmount'   => $this->converter->convert( $this->wc_order->get_shipping_tax() )
                );
            }
        }

        return $rows;
    }

    /**
     * Formats voucher row for shipping
     *
     * @param $country_code
     * @return array
     * @throws Exception
     */
    public function format_shipping_rows( $country_code ){
        $rows = [];
        $vat_percentage = self::get_shipping_vat_percentage( $this->wc_order->get_items(), $country_code );
        $rows[] = array(
            'AccountNumber' => self::get_freight_account( $country_code ),
            'CreditAmount'   => $this->converter->convert( $this->wc_order->get_shipping_total() )
        );

        if( 0 < $this->wc_order->get_shipping_tax() ){
            if( 'SE' == $country_code ){
                $rows[] = array(
                    'AccountNumber' => get_option( 'visma_domestic_vat_' . intval( $vat_percentage ) . '_account'),
                    'CreditAmount'   => $this->converter->convert( $this->wc_order->get_shipping_tax() )
                );
            }
            elseif( ! WTV_Utils::is_outside_eu( $country_code ) ){
                $rows[] = array(
                    'AccountNumber' => get_option( 'visma_eu_vat_' . intval( $vat_percentage ) . '_account'),
                    'CreditAmount'   => $this->converter->convert( $this->wc_order->get_shipping_tax() )
                );
            }
        }

        return $rows;
    }

    /**
     * Calculate total
     *
     * @param $rows
     * @param string $key
     * @return float
     */
    public static function get_total( $rows, $key='DebitAmount' ){
        $total = 0;
        foreach ( $rows as $row ) {
            $total += $row[$key];
        }
        return WTV_Utils::format_number( $total );
    }

    /**
     * Sets postmeta 'visma_voucher_id' of shop_order
     * @param int $visma_voucher_id
     * @param bool $credit
     */
    public function set_visma_voucher_id( $visma_voucher_id, $credit=false ) {
        if( ! $credit ){
            WTV_Utils::update_order_meta_compat( $this->wc_order->get_id(),  'visma_voucher_id', $visma_voucher_id );
        }
        else{
            WTV_Utils::update_order_meta_compat( $this->wc_order->get_id(), 'visma_credit_voucher_id', $visma_voucher_id );
        }
    }


    /*
    * @param WC_Order $wc_order
     */
    public static function is_voucher_created( $wc_order ){
        return ! empty( $wc_order->get_meta( 'visma_voucher_id' ) );
    }
}
