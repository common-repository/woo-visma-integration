<?php

namespace includes\api;

use includes\http\WTV_Request;

class WTV_Invoice_Payment{

    /**
     * Sets invoiced as paid
     * @param WC_Order $wc_order
     * @param $total
     * @param $date_created
     * @param $is_credit_invoice
     * @return void
     * @throws \Exception
     */
    public static function create_invoice_payment( $wc_order, $total, $date_created,  $is_credit_invoice=false  ){

        $invoice_payment = apply_filters( 'wetail_visma_invoice_payment_before_create_or_update', [
            'CompanyBankAccountId'  => get_option( 'visma_invoice_payment_bank_account'),
            'PaymentDate'           => substr( $date_created, 0, 10 ),
            'PaymentAmount'         => abs( $total ),
            'PaymentCurrency'       => $wc_order->get_currency(),
            'PaymentType'           => floatval( $wc_order->get_total() ) === abs( $total ) ? 'CompletePayment' : 'PartialPayment'
        ], $wc_order, $is_credit_invoice );
        $wc_order->read_meta_data( true );
        WTV_Request::post( '/customerinvoices/'. WTV_Invoices::get_visma_invoice_id( $wc_order ) . '/payments', $invoice_payment );
    }
}
