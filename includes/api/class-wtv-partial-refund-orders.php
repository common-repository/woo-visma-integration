<?php

namespace includes\api;

if ( !defined( 'ABSPATH' ) ) die();

use Exception;
use includes\http\WTV_Request;
use includes\utils\WTV_Utils;
use includes\WTV_Plugin;
use WC_Order;

class WTV_Partial_Refund_Orders
{

    /**
     * @param $order
     * @param $refunded_order
     * @return mixed
     * @throws Exception
     */
    public static function create_partial_order_refund( $wc_order, $refunded_order ){

        try {
            $customer_id = WTV_Customers::sync( $wc_order );
            $wc_order_number = WTV_Orders::get_order_number( $wc_order->get_id(), $wc_order );
            $visma_order = WTV_Orders::generate_order_header_payload( $wc_order, $customer_id, $wc_order_number, $refunded_order);
            $order_rows = self::format_partial_order_refund_rows( $wc_order, [ 'DeliveryCountryCode' => $wc_order->get_shipping_country() ] );

            if ( intval( $wc_order->get_shipping_total() ) != 0 ) {
                $include_vat = WTV_Utils::include_vat( $wc_order );
                $shipping = WTV_Orders::get_shipping( $wc_order, count( $order_rows ) + 1 );
                $refunded_shipping_total = $include_vat ? floatval( $refunded_order->get_shipping_total() ) + floatval( $refunded_order->get_shipping_tax() ) : floatval( $refunded_order->get_shipping_total() );
                $shipping['UnitPrice'] = $shipping['UnitPrice'] - abs( $refunded_shipping_total );
                $order_rows[] = $shipping;
            }

            $visma_order['Rows'] = $order_rows;
            $visma_order = WTV_Orders::handle_fees( $refunded_order, $visma_order );

            if( 3 == $visma_order['Status']){
                $visma_order['ShippedDateTime'] = substr( $wc_order->get_date_created(), 0, 10 );
            }
            $visma_order_id = WTV_Orders::get_visma_order_id( $wc_order );
            WTV_Request::put("/orders/{$visma_order_id}/", $visma_order );

            WTV_Refunds::set_order_refund_as_synced( $wc_order->get_id() );
            $wc_order->add_order_note( __( 'Retur hanterad i Visma', WTV_Plugin::TEXTDOMAIN ) );

        } catch ( \Exception $error ) {
            WTV_Orders::add_order_error_log( $wc_order, $error );
            throw new \Exception( $error->getMessage(), $error->getCode() );
        }

        do_action( 'wf_order_after_partial_refund', $wc_order, $refunded_order );
    }

    /**
     * @param \WC_Order $wc_order
     * @param $customer
     * @return array
     * @throws \Exception
     */
    public static function format_partial_order_refund_rows( $wc_order, $customer ){

        $order_rows         = [];
        $row_index          = 1;
        $total_refunds_rows = [];
        $refund_ids = WTV_Utils::get_refunds( $wc_order->get_id() );
        foreach ( $refund_ids as $refunded_order_id ){
            $wc_refunded_order = wc_get_order( $refunded_order_id );
            foreach( $wc_refunded_order->get_items() as $refund_order_item ) {
                $refund_product_ids[] = $refund_order_item->get_product_id();
                if ( in_array( $refund_order_item->get_product_id(), array_keys( $total_refunds_rows ) ) ){
                    $total_refunds_rows[$refund_order_item->get_product_id()] += $refund_order_item->get_quantity();
                }
                else{
                    $total_refunds_rows[$refund_order_item->get_product_id()] = $refund_order_item->get_quantity();
                }
            }
        }

        foreach( $wc_order->get_items() as $order_item ) {
            if ( ! in_array( $order_item->get_product_id(), array_keys( $total_refunds_rows ) ) ){
                $order_rows[] = WTV_Orders::generate_order_row_payload( $order_item, $order_item->get_quantity(), $wc_order, $row_index );
            }
            else{
                $order_rows[] = WTV_Orders::generate_order_row_payload( $order_item, $order_item->get_quantity() + $total_refunds_rows[$order_item->get_product_id()], $wc_order, $row_index );
            }
            $row_index++;
        }
        return $order_rows;
    }

    private static function get_order_item_by( $wc_order, $product_id ){
        foreach( $wc_order->get_items() as $item ) {
            if( $item->get_product_id() === $product_id ){
                return $item;
            }
        }
    }
}

