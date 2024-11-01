<?php

namespace includes\api;

if ( !defined( 'ABSPATH' ) ) die();

use Exception;
use includes\http\WTV_Request;
use includes\WTV_Plugin;
use WC_Order;

class WTV_Refund_Invoices {

    /**
     * Sends invoice to Visma
     * @param mixed $visma_invoice
     * @return mixed
     * @throws \Exception
     */
    public static function send_invoice_to_visma( $visma_invoice ){
        $response =  WTV_Request::post( "/customerinvoices/", $visma_invoice );
        return [
            'visma_invoice_id' => $response->Id,
            'visma_invoice_number' => $response->InvoiceNumber
        ];
    }

    /**
     * @param $order
     * @return mixed
     */
    public static function get_visma_refund_invoice_id( $order ){
        $order->read_meta_data(true);
        return $order->get_meta( '_visma_refund_invoice_id'  );

    }
    /** Processes actions after refund creation
    * @param $order
    * @param $refunded_order
    * @throws Exception
    */
    public static function do_post_process_actions( $wc_order, $refunded_order ){
        if ( get_option( 'visma_auto_set_refund_invoice_as_paid' ) ) {
            if ( floatval( $refunded_order->get_total() ) != 0.0) {
                WTV_Invoice_Payment::create_invoice_payment( $wc_order, $refunded_order->get_total(), $refunded_order->get_date_created(), );
            }
        }
    }

    /** Sets visma invoice Id(refund) to order meta
     * @param $order
     * @param $invoice_number
     */
    public static function set_visma_refund_invoice_id( $order, $refunded_visma_invoice_id ){
        $order->add_meta_data( '_visma_refund_invoice_id', $refunded_visma_invoice_id );
        $order->save_meta_data();
    }
    /**
     * Sets invoiced as paid
     * @param WC_Order $order
     * @return void
     * @throws \Exception
     */
    public static function post_process_refund( $order, $refunded_order, $refunded_visma_invoice ){
        WTV_Refunds::set_order_refund_as_synced( $refunded_order->get_id() );
        self::set_visma_refund_invoice_id( $order, $refunded_visma_invoice['visma_invoice_id'] );
        $order->add_order_note(__( 'Retur hanterad i Visma, ID: ', WTV_Plugin::TEXTDOMAIN) . $refunded_visma_invoice['visma_invoice_id'] . " Fakturanummer: " .  $refunded_visma_invoice['visma_invoice_number']);
    }
}
