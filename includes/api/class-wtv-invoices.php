<?php

namespace includes\api;

use Exception;
use includes\http\WTV_Request;
use includes\utils\WTV_Currency_Converter;
use includes\utils\WTV_Utils;
use includes\WTV_Plugin;
use WC_Order;
use WC_Order_Refund;
use WC_Product;
use WC_Order_Item;

class WTV_Invoices {

    /**
     * Generate Visma invoice payload
     */
    public static function generate_invoice_header_payload( $wc_order, $customer_number, $wc_order_number, $refund_order=false, $is_credit_invoice=false ){
        $address = $wc_order->get_address();
        $invoice = apply_filters( 'wetail_visma_invoice', [
            'CustomerId'                            => $customer_number,
            'Number'                                => preg_replace('/\D/', '', $wc_order_number),
            'IsCreditInvoice'                       => $is_credit_invoice,
            'Status'                                => 1,
            'CustomerIsPrivatePerson'               => ( ! empty( $address['company'] ) ? false : true ),
            'InvoiceDate'                           => $refund_order ? substr( $refund_order->get_date_created(), 0, 10 ) : substr( $wc_order->get_date_created(), 0, 10 ), # To cut off order time
            'VATIncluded'                           => apply_filters( 'wetail_visma_sync_order_vat_included', false ),
            'CurrencyCode'                          => $wc_order->get_currency(),
            'DeliveryCustomerName'                  => ! empty( $wc_order->get_shipping_company() ) ? $wc_order->get_shipping_company() : $wc_order->get_shipping_first_name() . ' ' . $wc_order->get_shipping_last_name(),
            'DeliveryAddress1'                      => $wc_order->get_shipping_address_1(),
            'DeliveryAddress2'                      => $wc_order->get_shipping_address_2(),
            'DeliveryPostalCode'                    => $wc_order->get_shipping_postcode(),
            'DeliveryCity'                          => $wc_order->get_shipping_city(),
            'DeliveryCountryCode'                   => $wc_order->get_shipping_country() ? $wc_order->get_shipping_country() : $wc_order->get_billing_country(),
            'OurReference'                          => $wc_order->get_shipping_first_name().' '.$wc_order->get_shipping_last_name(),
            'InvoiceAddress1'                       => $wc_order->get_billing_address_1(),
            'InvoiceAddress2'                       => $wc_order->get_billing_address_2(),
            'InvoiceCity'                           => $wc_order->get_billing_city(),
            'InvoiceCountryCode'                    => $wc_order->get_billing_country(),
            'InvoiceCustomerName'                   => ! empty( $address['company'] ) ? $address['company'] : $address['first_name'] . ' ' . $address['last_name'],
            'InvoicePostalCode'                     => $wc_order->get_billing_postcode(),
            'EuThirdParty'                          => false,
            'RotReducedInvoicingType'               => false,
            'ReverseChargeOnConstructionServices'   => false,
            'ReversedConstructionServicesVatFree'   => false,
            'BuyersOrderReference'                  => apply_filters( 'woocommerce_order_number', $wc_order->get_id(), $wc_order ),
        ], $wc_order );
        return $invoice;
    }



    /**
     * Creates order row array
     *
     * @param WC_Order_Item $item
     * @param WC_Order $wc_order
     * @param $index
     * @return mixed
     * @throws Exception
     */
    public static function generate_invoice_row_payload( $item, $wc_order, $index, $quantity, $include_vat, $is_credit_invoice=false ){

        $product = ( WTV_Orders::item_is_variation( $item )) ? wc_get_product($item->get_variation_id()) : wc_get_product( $item->get_product_id() );
        $product_name = WTV_Orders::get_product_name( $item );

        if ( wc_get_product( $product->get_id() ) ) {
            WTV_Products::sync( $product->get_id(), true);
        }

        $subtotal = $wc_order->get_item_subtotal( $item, false, false );
        $total = $wc_order->get_item_total( $item, false, false );

        return apply_filters( 'wetail_visma_sync_modify_order_row', [
            'LineNumber'                            => $index,
            'ArticleId'                             => WTV_Products::get_visma_article_id( $product ),
            'ArticleNumber'                         => WTV_Products::sanitized_sku( $product->get_sku() ),
            'Text'                                  => WTV_Products::sanitize_description( $product_name ),
            'DeliveredQuantity'                     => $quantity,
            'Quantity'                              => $quantity,
            'IsTextRow'                             => false,
            'IsWorkCost'                            => false,
            'EligibleForReverseChargeOnVat'         => true,
            'ReversedConstructionServicesVatFree'   => false,
            'UnitPrice'                             => WTV_Utils::format_number( $wc_order->get_item_subtotal( $item, $include_vat, false ) ),
            'DiscountPercentage'                    => WTV_Orders::calculate_item_discount( $subtotal, $total, $quantity ),
        ], $product, $item, $is_credit_invoice );
    }

    /** Fetches invoice id from WC order
     * @param $wc_order
     * @return mixed
     * @throws Exception
     */
    public static function get_visma_invoice( $wc_order ) {
        $wc_order->read_meta_data(true);
        if ( $wc_order->meta_exists( 'visma_invoice_number' ) ) {
            return WTV_Request::get( "/customerinvoices/" . self::get_visma_invoice_id( $wc_order ));
        }
        return false;
    }


    /** Fetches invoice id from WC order
     * @param $wc_order
     * @return mixed
     */
    public static function get_visma_invoice_id( $wc_order ) {
        if ( $wc_order->meta_exists( 'visma_invoice_id' ) ) {
            return $wc_order->get_meta( 'visma_invoice_id' );
        }
    }

    /** Sets invoice id from Visma
     * @param $wc_order
     * @param $visma_invoice_id
     */
    public static function set_visma_invoice_id( $wc_order, $visma_invoice_id ) {
        WTV_Utils::update_order_meta_compat(  $wc_order->get_id(), 'visma_invoice_id', $visma_invoice_id );
    }

    /** Fetches invoice number from WC order
     * @param $wc_order
     * @return mixed
     */
    public static function get_visma_invoice_number( $wc_order ) {
        if ( $wc_order->meta_exists( 'visma_invoice_number' ) ) {
            return $wc_order->get_meta( 'visma_invoice_number' );
        }
    }

    /** Sets invoice number from Visma
     * @param $wc_order
     * @param $visma_invoice_number
     */
    public static function set_visma_invoice_number( $wc_order, $visma_invoice_number ) {
        WTV_Utils::update_order_meta_compat(  $wc_order->get_id(), 'visma_invoice_number', $visma_invoice_number );
    }

    /**
     * @param $wc_order_id
     * @throws Exception
     */
    public static function send_invoice_PDF( $wc_order_id ) {
        $wc_order = wc_get_order( $wc_order_id );

        if ( $wc_order->meta_exists( 'visma_invoice_number' ) ) {
            WTV_Request::get('/customerinvoices/' . $wc_order->get_meta('visma_invoice_number') . '/email');
        }
    }

	/**
	 * Fetches PDF for specific order if available
	 *
	 * @param $wc_order_id
	 *
	 * @return mixed
	 * @throws Exception
	 * @wrike https://www.wrike.com/open.htm?id=1257978584
	 * @since 2.3.0
	 */
	public static function get_invoice_PDF_url( $wc_order_id ) {
		$wc_order = wc_get_order( $wc_order_id );

		if ( $wc_order->meta_exists( 'visma_invoice_id' ) ) {
			$response = WTV_Request::get( '/customerinvoices/' . $wc_order->get_meta( 'visma_invoice_id' ) . '/pdf' );
			$response = json_decode(json_encode($response), ARRAY_A);
			if ( array_key_exists( 'Url', $response ) ) {
				return $response['Url'];
			}
		}
		throw new Exception( __( "Invalid Visma API response.", WTV_Plugin::TEXTDOMAIN ), WTV_Plugin::INTERNAL_EXCEPTION );
	}

}
