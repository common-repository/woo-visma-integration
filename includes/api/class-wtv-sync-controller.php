<?php

namespace includes\api;

use includes\WTV_Plugin;
use includes\WTV_Settings_Validator;
use WC_Order;

class WTV_Sync_Controller{

    /**
     * Handles all syncing for an order
     * @param int $wc_order_id
     * @return bool
     * @throws \Exception
     */
    public static function sync( $wc_order_id ){

        $wc_order = wc_get_order( $wc_order_id );
        WTV_Settings_Validator::validate_settings_payment_method( $wc_order );

        if( self::should_create_order() ){
            WTV_Orders::sync( $wc_order );

            if ( self::should_convert_order_to_invoice( $wc_order->get_payment_method() ) ){
                WTV_Orders::convert_order_to_invoice( $wc_order );
                if ( self::should_add_payment_to_invoice( $wc_order->get_payment_method() ) ){
                    WTV_Invoice_Payment::create_invoice_payment( $wc_order, $wc_order->get_total(), $wc_order->get_date_created() );
                }
            }
        }
        elseif( self::should_create_voucher() && ! WTV_Vouchers::is_voucher_created( $wc_order ) ){
            $voucher = new WTV_Vouchers( $wc_order );
            $voucher->create_order_voucher();
            WTV_Orders::set_order_as_synced( $wc_order, WTV_Plugin::SYNC_STATUS_VOUCHER_SYNCED );
        }
        return true;
    }

    public static function should_add_payment_to_invoice( $payment_method ){
        $settings = get_option( 'visma_order_sync_settings_' . $payment_method );
        if( isset( $settings['order_add_invoice_payment'] ) ){
            return intval( $settings['order_add_invoice_payment'] ) == 1;
        }

    }

    public static function should_convert_order_to_invoice( $payment_method ){
        $settings = get_option( 'visma_order_sync_settings_' . $payment_method );
        if( isset( $settings['order_convert_to_invoice'] ) ){
            return intval( $settings['order_convert_to_invoice'] ) == 1;
        }

    }

    public static function should_create_order(){
        return get_option( 'visma_sync_order_method' ) && get_option( 'visma_sync_order_method' ) === 'create_orders';
    }

    public static function should_create_voucher(){
        return get_option( 'visma_sync_order_method' ) && get_option( 'visma_sync_order_method' ) === 'create_vouchers';
    }
}
