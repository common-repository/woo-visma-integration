<?php

namespace includes\api;

if ( !defined( 'ABSPATH' ) ) die();

use Exception;
use includes\utils\WTV_Utils;
use includes\WTV_Plugin;

class WTV_Full_Refund_Invoices  extends WTV_Refund_Invoices{

    /** Controller function for handling a refund
     * @param \WC_Order $order
     * @param int $refunded_order_id
     * @return mixed
     * @throws \Exception
     */
    public static function process_full_credit_invoice( $order, $refunded_order_id ){
        $refunded_order = wc_get_order( $refunded_order_id );
        $credit_invoice_id = self::create_credit_invoice( $order, $refunded_order );
        
        if( ! $credit_invoice_id ){
            return;
        }
        try{
            WTV_Refunds::set_order_refund_as_synced( $order->get_id() );
            foreach (  $order->get_refunds() as $refund ){
                WTV_Refunds::set_order_refund_as_synced( $refund->get_id() );
            }

        } catch( \Exception $error ) {
            WTV_Orders::add_order_error_log( $order, $error );
            throw new \Exception( $error->getMessage(), $error->getCode() );
        }

        do_action( 'wetail_visma_order_after_full_refund', $order );

        return $credit_invoice_id;
    }

    /**
     * @param $order
     * @param $refunded_order
     * @return mixed
     * @throws Exception
     */
    public static function create_credit_invoice( $order, $refunded_order ){

        try {
            $customer_number = WTV_Customers::sync( $order );
            $invoice_rows = [];
            $order_number = apply_filters( 'woocommerce_order_number', $refunded_order->get_id(), $order );
            $visma_invoice =  WTV_Invoices::generate_invoice_header_payload( $order, $customer_number, $order_number, $refunded_order, true );
            $include_vat = WTV_Utils::include_vat( $order );
            $index = 0;
            foreach( $order->get_items() as $item ) {
                $invoice_rows[] = WTV_Invoices::generate_invoice_row_payload( $item, $order, $index, (-1) * $item->get_quantity(), $include_vat, true );
                $index++;
            }

            $visma_invoice = WTV_Orders::handle_fees( $order, $visma_invoice );

            if ( intval( $order->get_shipping_total() ) != 0 ) {
                $shipping_total = WTV_Utils::include_vat( $order ) ? floatval( $order->get_shipping_total() ) + floatval( $order->get_shipping_tax() ) : floatval( $order->get_shipping_total() );
                $invoice_rows[] = WTV_Refunds::get_custom_refund_shipping( $order, $index, $shipping_total, true );
            }
            $visma_invoice['Rows'] = $invoice_rows;

            $invoice = apply_filters( 'wetail_visma_invoice_before_visma_submit', $visma_invoice, $order );

            $visma_invoice = self::send_invoice_to_visma( $invoice );

            self::post_process_refund( $order, $refunded_order, $visma_invoice );

            return $visma_invoice['visma_invoice_id'];
        } catch( Exception $error ) {
            WTV_Orders::add_order_error_log( $order, $error );
            throw new Exception( $error->getMessage(), WTV_Plugin::INTERNAL_EXCEPTION );
        }
    }
}
