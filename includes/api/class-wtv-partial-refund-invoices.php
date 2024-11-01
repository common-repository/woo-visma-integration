<?php

namespace includes\api;

if ( !defined( 'ABSPATH' ) ) die();

use Exception;
use includes\http\WTV_Request;
use includes\utils\WTV_Utils;
use includes\WTV_Plugin;
use WC_Order;

class WTV_Partial_Refund_Invoices extends WTV_Refund_Invoices
{

    /**
     * @param $wc_order
     * @param $refunded_order
     * @return mixed
     * @throws Exception
     */
    public static function create_partial_credit_invoice( $wc_order, $refunded_order ){

        try{
            $customer_number = WTV_Customers::sync( $wc_order );
            $invoice_rows = [];
            $wc_order_number = apply_filters( 'woocommerce_order_number', $refunded_order->get_id(), $wc_order );
            $visma_invoice =  WTV_Invoices::generate_invoice_header_payload( $wc_order, $customer_number, $wc_order_number, $refunded_order, true );
            $include_vat = WTV_Utils::include_vat( $wc_order );
            $index = 0;

            foreach( $refunded_order->get_items() as $item ) {
                if( $item->get_quantity() != 0 ){
                    $invoice_rows[] = WTV_Invoices::generate_invoice_row_payload( $item, $refunded_order, $index, $item->get_quantity(), $include_vat, true  );
                }
                else{
                    $invoice_rows[] = WTV_Invoices::generate_invoice_row_payload( $item, $refunded_order, $index, -1, $include_vat, true  );
                }
                $index++;
            }

            if ( intval( $refunded_order->get_shipping_total() ) != 0 ) {
                $shipping_total = $include_vat ? floatval( $refunded_order->get_shipping_total() ) + floatval( $refunded_order->get_shipping_tax() ) : floatval( $refunded_order->get_shipping_total() );
                $invoice_rows[] = WTV_Refunds::get_custom_refund_shipping( $wc_order, $index, $shipping_total );
            }

            $visma_invoice['Rows'] = $invoice_rows;
            $visma_invoice = WTV_Orders::handle_fees( $refunded_order, $visma_invoice );

            $invoice = apply_filters( 'wetail_visma_invoice_before_visma_submit', $visma_invoice, $wc_order );

            $visma_invoice = self::send_invoice_to_visma( $invoice );
            self::post_process_refund( $wc_order, $refunded_order, $visma_invoice );


        } catch (\Exception $error ) {
            WTV_Orders::add_order_error_log( $wc_order, $error );
            throw new \Exception( $error->getMessage(), $error->getCode() );
        }

        do_action( 'wf_order_after_partial_refund', $wc_order, $refunded_order );

        return $visma_invoice['visma_invoice_id'];
    }
}
