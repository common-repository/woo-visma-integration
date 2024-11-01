<?php

namespace includes\api;

use includes\utils\WTV_Utils;

if ( !defined( 'ABSPATH' ) ) die();

class WTV_Refunds{

    /** Controller function for handling a refund
     * @param int $wc_order_id
     * @param int $refunded_order_id
     * @return mixed
     * @throws \Exception
     */
    public static function handle_refund( $wc_order_id, $refunded_order_id ){
        $wc_order = wc_get_order( $wc_order_id );

        if( self::order_is_totally_refunded( $wc_order ) ){
            return self::process_full_refund( $wc_order, $refunded_order_id );
        }
        else{
            return self::process_partial_refund( $wc_order, $refunded_order_id );
        }
    }

    /**
     * Handle Custom Shipping
     *
     * @param $wc_order
     * @param $refunded_order
     * @return mixed
     * @throws \Exception
     */
    public static function get_custom_refund_shipping( $wc_order, $index, $price, $is_full_refund=False ){

        if( $is_full_refund ){
            $quantity = -1;
        }
        else{
            $quantity = $price < 0 ? -1 : 1;
        }

        $shipping = WTV_Orders::get_shipping( $wc_order, $index );
        $shipping['DeliveredQuantity'] = $quantity;
        $shipping['Quantity'] = $quantity;
        $shipping['UnitPrice'] = abs( $price );
        return $shipping;
    }

    /**
     * Check whether order refund is synced to Visma
     *
     * @param int $wc_order_id
     * @return mixed
     */
    public static function is_refund_synced( $refund_order_id ) {
        return WTV_Utils::get_order_meta_compat( $refund_order_id, '_visma_order_refund_synced' );
    }

    /**
     * Returns true if order refund total equals order total
     * @param WC_Order $wc_order
     * @return bool
     */
    private static function order_is_totally_refunded( $wc_order ){
        return $wc_order->get_total() == $wc_order->get_total_refunded();
    }

    /** Controller function for handling a full refund
     * @param \WC_Order $wc_order
     * @return mixed
     * @throws \Exception
     */
    public static function process_full_refund( $wc_order, $refund_id ){
        if( WTV_Invoices::get_visma_invoice_id( $wc_order ) ){
            WTV_Full_Refund_Invoices::process_full_credit_invoice( $wc_order, $refund_id );
            WTV_Full_Refund_Invoices::do_post_process_actions( $wc_order, wc_get_order( $refund_id ) );
        }
        else{
            WTV_Orders::delete_order( $wc_order );
        }
    }

    /** Controller function for handling a partial refund
     * @param \WC_Order $wc_order
     * @param int $refunded_order_id
     * @return mixed
     * @throws \Exception
     */
    public static function process_partial_refund( $wc_order, $refunded_order_id ){
        $refunded_order = wc_get_order( $refunded_order_id );
        if( WTV_Invoices::get_visma_invoice_id( $wc_order ) ){
            $refunded_invoice_number = WTV_Partial_Refund_Invoices::create_partial_credit_invoice( $wc_order, $refunded_order );
            WTV_Partial_Refund_Invoices::do_post_process_actions( $wc_order, $refunded_order, $refunded_invoice_number );
        }
        else{
            WTV_Partial_Refund_Orders::create_partial_order_refund( $wc_order, $refunded_order );
        }
    }

    /**
     * Sets meta '_visma_order_refund_synced' of shop_order to true
     * @param int $refund_order_id
     */
    public static function set_order_refund_as_synced( $refund_order_id ) {
        WTV_Utils::update_order_meta_compat ( $refund_order_id, '_visma_order_refund_synced', 1 );
    }
}
