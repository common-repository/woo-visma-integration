<?php
namespace includes\api;

use Exception;
use includes\utils\WTV_Utils;
use includes\WTV_Plugin;
use includes\WTV_Settings_Validator;
use WC_Customer;
use WC_Order;
use WC_Shipping_Zones;
use includes\http\WTV_Request;

class WTV_Orders {
    const VISMA_ERROR_CODE_ORDER_ALREADY_INVOICED = 2000;

    /**
     * Generate Visma invoice payload
     */
    public static function generate_order_header_payload( $wc_order, $visma_customer_id, $wc_order_number, $refund_order=false){

        $vat_number = self::has_eu_vat_number( $wc_order );
        $include_vat    = ! empty( $address['company'] ) ? false : true;
        $address    = $wc_order->get_address();
        $data = apply_filters( 'wetail_visma_order', [
            'CustomerId'                            => $visma_customer_id,
            'Number'                                => intval( preg_replace('/\D/', '', $wc_order_number ) ),
            'RoundingsAmount'                       => 0,
            'Status'                                => self::format_status( $wc_order->get_payment_method() ),
            'CustomerIsPrivatePerson'               => ( ! empty( $address['company'] ) ? false : true ),
            'OrderDate'                             => substr( $wc_order->get_date_created(), 0, 10 ), # To cut off order time
            'VATIncluded'                           => apply_filters( 'wetail_visma_sync_order_vat_included', $include_vat ),
            'CurrencyCode'                          => $wc_order->get_currency(),
            'DeliveryCustomerName'                  => ! empty( $wc_order->get_shipping_company() ) ? $wc_order->get_shipping_company() : $wc_order->get_shipping_first_name() . ' ' . $wc_order->get_shipping_last_name(),
            'DeliveryAddress1'                      => $wc_order->get_shipping_address_1(),
            'DeliveryAddress2'                      => $wc_order->get_shipping_address_2(),
            'DeliveryPostalCode'                    => $wc_order->get_shipping_postcode(),
            'DeliveryCity'                          => $wc_order->get_shipping_city(),
            'DeliveryCountryCode'                   => $wc_order->get_shipping_country() ? $wc_order->get_shipping_country() : $wc_order->get_billing_country(),
            'InvoiceAddress1'                       => $wc_order->get_billing_address_1(),
            'InvoiceAddress2'                       => $wc_order->get_billing_address_2(),
            'InvoiceCity'                           => $wc_order->get_billing_city(),
            'InvoiceCountryCode'                    => $wc_order->get_billing_country(),
            'InvoiceCustomerName'                   => ! empty( $address['company'] ) ? $address['company'] : $address['first_name'] . ' ' . $address['last_name'],
            'InvoicePostalCode'                     => $wc_order->get_billing_postcode(),
            'EuThirdParty'                          => $vat_number ? true : false ,
            'BuyersOrderReference'                  => intval( preg_replace('/\D/', '', $wc_order_number) ),
            'RotReducedInvoicingType'               => '0',
            'ReverseChargeOnConstructionServices'   => false,
            'YourReference'                         => $wc_order->get_billing_first_name() . ' ' . $wc_order->get_billing_last_name()
        ], $wc_order );
        return $data;
    }


    /**
     * Sync order to Visma
     *
     * @param $wc_order
     * @return mixed
     * @internal param int $wc_order_id
     * @throws Exception
     */
    public static function sync( $wc_order ) {

        self::validate_order_items( $wc_order );

        wtv_write_log("ORDER TOTAL:" . $wc_order->get_total() );
        wtv_write_log("SHIPPING TOTAL:" . $wc_order->get_shipping_total() );
        wtv_write_log("ORDER TOTAL TAX:" . $wc_order->get_total_tax() );
        wtv_write_log("SHIPPING TOTAL TAX:" . $wc_order->get_shipping_tax() );

        do_action('visma_before_order_sync', array( $wc_order ));

        $visma_customer_id  = WTV_Customers::sync( $wc_order );
        $visma_order_rows   = [];
        $wc_order_number    = apply_filters( 'woocommerce_order_number', $wc_order->get_id(), $wc_order );
        $visma_order        = self::generate_order_header_payload( $wc_order, $visma_customer_id, $wc_order_number, );

        if( 3 == $visma_order['Status']){
            $visma_order['ShippedDateTime'] = substr( $wc_order->get_date_created(), 0, 10 );
        }

        $index = 1;
        foreach( $wc_order->get_items() as $item ) {
            $visma_order_rows[] = self::generate_order_row_payload( $item, $item->get_quantity(), $wc_order, $index );
            $index++;
        }

        if( ! empty( $wc_order->get_shipping_method() && 0 != intval( $wc_order->get_shipping_total() ) ) ){
            $shipping = self::get_shipping( $wc_order, $index );
            if( is_array( $shipping ) ){
                $visma_order_rows[] = $shipping;
            }
        }

        $visma_order['Rows'] = $visma_order_rows;

        try{
            $response = self::send_order_to_visma( $visma_order, $wc_order->get_id() );
            self::set_order_as_synced( $wc_order, WTV_Plugin::SYNC_STATUS_ORDER_SYNCED, $response->Id );
            do_action('visma_after_order_sync', $wc_order , $visma_order );
        }
        catch ( Exception $error ){
            self::add_order_error_log( $wc_order, $error );
            throw $error;
        }
        return true;
    }

    /** Calculates order total
     * @param $wc_order
     * @param $refund_order
     */
    public static function get_order_total( $wc_order, $refund_order ){
        $total = $wc_order->get_total();
        $total_tax = $wc_order->get_total_tax();
        if ($refund_order){
            $refund_total = $refund_order->get_total();
            $refund_total_tax = $refund_order->get_total_tax();
        }

        if ( WTV_Utils::include_vat( $wc_order ) ){
            if ( $refund_order ) {
                return $wc_order->get_total() - abs( $refund_order->get_total() );
            }
            else{
                return $wc_order->get_total();
            }
        }
        else{
            if ( $refund_order ) {
                return $wc_order->get_total() - $wc_order->get_total_tax()  - ( abs( $refund_order->get_total() )  );
            }
            else{
                return $wc_order->get_total() - $wc_order->get_total_tax();
            }
        }
    }

    /**
     * Calculate item discount
     *
     * @param float $subtotal
     * @param float $total
     * @param int $quantity
     * @return mixed
     */
    public static function calculate_item_discount( $subtotal, $total, $quantity ){

        if ( $subtotal != $total ) {
            $item_discount = $subtotal - $total;
            return  WTV_Utils::format_number( floatval( $item_discount/$subtotal ), 4 );
        }
        return 0.0;
    }

    public static function get_order_number( $wc_order_id, $wc_order ){
        return preg_replace( '/\D/', '', apply_filters( 'wetail_visma_order_number', apply_filters( 'woocommerce_order_number', $wc_order_id, $wc_order ) ) );
    }

    /**
     * Creates order row array
     *
     * @param \WC_Order_Item $item
     * @param WC_Order $wc_order
     * @param $index
     * @return mixed
     * @throws Exception
     */
    public static function generate_order_row_payload( $item, $quantity, $wc_order, $index, $wc_order_refund=false ){

        $product        = ( self::item_is_variation( $item )) ? wc_get_product( $item->get_variation_id()) : wc_get_product( $item->get_product_id() );
        $product_name   = self::get_product_name( $item );
        $address        = $wc_order->get_address();
        $include_vat    = ! empty( $address['company'] ) ? false : true;
        $include_vat    = apply_filters( 'wetail_visma_sync_order_vat_included', $include_vat );

        if ( ! $product ){
            return self::get_non_product_order_row($item, $quantity, $wc_order, $index, $include_vat);
        }

        if ( wc_get_product( $product->get_id() ) ) {
            if( get_option( 'visma_sync_existing_product') ){
                WTV_Products::sync( $product->get_id(), true );
            }
            else if ( ! WTV_Products::exists_in_visma( $product ) ){
                 WTV_Products::sync( $product->get_id(), true );
            }
            $product = wc_get_product( $product->get_id() );
        }

        $order_row = apply_filters( 'wetail_visma_sync_modify_order_row', [
            'LineNumber'                    => $index,
            'ArticleId'                     => WTV_Products::get_visma_article_id( $product ),
            'ArticleNumber'                 => WTV_Products::sanitized_sku( $product->get_sku() ),
            'Text'                          => WTV_Products::sanitize_description( $product_name ),
            'DeliveredQuantity'             => floatval( $quantity ),
            'Quantity'                      => floatval( $quantity ),
            'IsTextRow'                     => false,
            'IsWorkCost'                    => false,
            'EligibleForReverseChargeOnVat' => false,
            'UnitPrice'                     => WTV_Utils::format_number( $wc_order->get_item_subtotal( $item, $include_vat, false ) ),
            'DiscountPercentage'            => self::calculate_item_discount(
                $wc_order->get_item_subtotal( $item, $include_vat, false ),
                $wc_order->get_item_total( $item, $include_vat, false ),
                $quantity
            ),
        ], $product, $item );

        return $order_row;
    }


    /**
     * Returns deleted product row
     *
     * @param $item
     * @param $quantity
     * @param $wc_order
     * @param $index
     * @param $include_vat
     * @return mixed
     */
    public static function get_non_product_order_row( $item, $quantity, $wc_order, $index, $include_vat ){
        return [
            'LineNumber'                    => $index,
            'Text'                          => 'Raderad produkt',
            'DeliveredQuantity'             => floatval( $quantity ),
            'Quantity'                      => floatval( $quantity ),
            'IsTextRow'                     => false,
            'IsWorkCost'                    => false,
            'EligibleForReverseChargeOnVat' => false,
            'UnitPrice'                     => WTV_Utils::format_number( $wc_order->get_item_subtotal( $item, $include_vat, false ) ),
            'DiscountPercentage'            => self::calculate_item_discount(
                $wc_order->get_item_subtotal( $item, $include_vat, false ),
                $wc_order->get_item_total( $item, $include_vat, false ),
                $quantity
            ),
        ];
    }


    /**
     * Returns order from Visma
     *
     * @param int $wc_order_id
     * @return mixed
     * @throws Exception
     */
    public static function get_order( $wc_order_id ){
        $visma_order_id = self::get_visma_order_id( wc_get_order( $wc_order_id ) );

        if( ! $visma_order_id ){
            throw new Exception( __( 'Order finns ej i Visma eEkonomi', WTV_Plugin::TEXTDOMAIN ), WTV_Plugin::INTERNAL_EXCEPTION );
        }
        return WTV_Request::get( "/orders/{$visma_order_id}");
    }

    /**
     * @param $item
     * @return string
     */
    public static function get_product_name( $item ){
        return $item->get_name();
    }

    /**
     * @param WC_Order $wc_order
     * @return bool
     */
    public static function has_eu_vat_number( $wc_order ){

        if( $wc_order->get_shipping_country() == 'SE' ){
            return false;
        }

        $vat_number = WTV_Utils::get_order_meta_compat( $wc_order->get_id(), '_vat_number' );
        if ( ! empty( $vat_number ) ) {
            if ( wc_string_to_bool( WTV_Utils::get_order_meta_compat( $wc_order->get_id(), '_vat_number_is_valid' ) ) ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get Shipping
     *
     * @param \WC_Shipping $shipping_method
     * @param string $country_code
     * @return mixed
     * @throws Exception
     */
    public static function create_shipping_article( $shipping_method, $country_code, $shipping_method_name, $vat_percentage ){
        $shipping_article_sku = $vat_percentage ? $shipping_method->get_method_id() . '_' . $vat_percentage : $shipping_method->get_method_id();
        $article = [
            'IsActive'      => true,
            'Name'          => substr( $shipping_method_name, 0, 50),
            'Number'        => $shipping_article_sku,
            'CodingId'      => WTV_Visma_Settings::get_account_coding( $vat_percentage, $country_code ),
            'UnitId'        => WTV_Visma_Settings::get_standard_unit_id(),
            'NetPrice'      => 0,
        ];

        wtv_write_log('PAYLOAD' );
        wtv_write_log( $article );

        try{
            $response = WTV_Request::post( '/articles',  $article );
            $shipping_id = $response->Id;
        }
        catch ( Exception $error ){

            wtv_write_log('ERROR' );
            wtv_write_log( $error );

            if( $error->getCode() == WTV_Request::ERROR_CODE_DUPLICATE ){
                $filter = '$filter';
                $response = WTV_Request::get( "/articles?$filter=contains(Number,'" . $shipping_article_sku ."')",  $article );
                $shipping_id = $response->Data[0]->Id;
            }
        }

        wtv_write_log('RESPONSE' );
        wtv_write_log( $response );

        update_option( 'visma_shipping_' . $shipping_method->get_method_id() . '_' . $vat_percentage, $shipping_id );
        return $shipping_id;
    }

    /**
     * Get Shipping
     *
     * @param WC_Order $wc_order
     * @param $index
     * @return mixed
     * @throws Exception
     */
    public static function get_shipping( $wc_order, $index ){

        $shipping_methods           = $wc_order->get_shipping_methods();
        $shipping_method            = reset( $shipping_methods );
        $vat_percentage             = self::get_vat_percentage( $wc_order->get_shipping_tax(), $wc_order->get_shipping_total() );
        $shipping_option_key        = 'visma_shipping_' . $shipping_method->get_method_id() . '_' . $vat_percentage;
        $address                    = $wc_order->get_address();

        $include_vat    = ! empty( $address['company'] ) ? false : true;
        $include_vat    = apply_filters( 'wetail_visma_sync_order_vat_included', $include_vat );
        $shipping_total =  $include_vat ? floatval( $wc_order->get_shipping_total() ) + floatval( $wc_order->get_shipping_tax() ) : $wc_order->get_shipping_total() ;
        $visma_shipping_article_id  = get_option( $shipping_option_key );

        if( empty( $visma_shipping_article_id ) ){
            $visma_shipping_article_id = self::create_shipping_article( $shipping_method, $wc_order->get_shipping_country(), $wc_order->get_shipping_method(), $vat_percentage );
        }

        return apply_filters( 'wetail_visma_shipping', [
            'LineNumber'                    => $index,
            'ArticleId'                     => $visma_shipping_article_id,
            'Text'                          => $shipping_method->get_name(),
            'DeliveredQuantity'             => 1,
            'Quantity'                      => 1,
            'IsTextRow'                     => false,
            'IsWorkCost'                    => false,
            'EligibleForReverseChargeOnVat' => false,
            'UnitPrice'                     => $shipping_total,
        ], $wc_order );
    }

    /**
     * Format status
     *
     * @param $payment_method
     * @return int
     * @internal param string $status
     */
    public static function format_status( $payment_method ){
        try{
            return get_option( 'visma_order_sync_settings_' . $payment_method )['order_status'];
        }
        catch ( Exception $e ){}
        return 1;
    }

    /**
     * Handle Fees
     *
     * @param WC_Order $wc_order
     * @param mixed $visma_order
     * @return mixed
     */
    public static function handle_fees( $wc_order, $visma_order ){

        # If order has a fee called 'Faktura'
        # add it as AdministrationFee in Visma
        foreach ( $wc_order->get_fees() as $fee ) {
            if ( $fee->get_name() == 'Faktura' ) {
                $invoice_fee = $wc_order->get_item_total($fee, false, false);
            }
        }
        if ( isset($invoice_fee ) )
            $visma_order['AdministrationFee'] = $invoice_fee;

        return $visma_order;
    }

    /**
     * Check wether order synced to Visma
     *
     * @param int $wc_order_id
     * @return mixed
     */
    public static function is_synced( $wc_order_id ) {
        $synced = WTV_Utils::get_order_meta_compat( $wc_order_id, '_visma_order_synced' );
        if( $synced ){
            return 1;
        }
    }

    /**
     * Checks if cart item is a variation
     *
     * @param mixed $item
     * @return boolean
     */
    public static function item_is_variation( $item ){
        if( ! empty( $item->get_variation_id() ) && wc_get_product($item->get_product_id()) ) {
            return true;
        }
        return false;
    }

    /**
     * Returns true if order exists in Visma.
     *
     * @param WC_Order $wc_order
     * @return boolean
     */
    public static function visma_order_exists( $wc_order ){

        try {
            self::get_order( $wc_order->get_id() );
            return true;
        }
        catch( Exception $error ) {
            wtv_write_log( __( 'Order finns ej i Visma eEkonomi', WTV_Plugin::TEXTDOMAIN) );
            return false;
        }

    }

    /**
     * Set Way of Delivery.
     *
     * @param WC_Order $wc_order
     * @param mixed $visma_order
     * @return mixed
     */
    public static function set_way_of_delivery( $wc_order, $visma_order ){

        $shipping_zones = WC_Shipping_Zones::get_zones();
        $my_zone        = 0;
        foreach( $shipping_zones as $zone ) {
            foreach ($zone['zone_locations'] as $zone_location) {
                if( $wc_order->get_shipping_country() == $zone_location->code ){
                    $my_zone = $zone['zone_id'];
                    break;
                }
            }
        }

        if( $shipping = $wc_order->get_items( 'shipping' ) ) {
            $shipping = reset( $shipping );
            //preg_match('/(.*)(:\d*)/', $shipping['method_id'], $shipping_method_id);
            $visma_shipping_code = get_option( 'visma_shipping_code_' . $shipping['method_id'] .':'.$my_zone);
            if( $visma_shipping_code ) {
	            $visma_order['DeliveryMethodName'] = $visma_shipping_code;
            }
        }

        $delivery_terms = apply_filters( 'wetail_visma_way_and_terms_of_delivery', [
            'DeliveryMethodName'    => array_key_exists('DeliveryMethodName', $visma_order ) ? $visma_order['WayOfDelivery'] : '',
            'DeliveryMethodCode'    => array_key_exists('WayOfDelivery', $visma_order ) ? $visma_order['WayOfDelivery'] : '',
            'DeliveryTermName'      => array_key_exists('WayOfDelivery', $visma_order ) ? $visma_order['WayOfDelivery'] : '',
            'DeliveryTermCode'      => array_key_exists('WayOfDelivery', $visma_order ) ? $visma_order['WayOfDelivery'] : '',
        ], $shipping);
        $visma_order += $delivery_terms;
        return $visma_order;
    }

    /**
     * Sets postmeta 'visma_order_id' of shop_order
     * @param WC_Order $wc_order
     * @param $visma_order_id
     */
    public static function set_visma_order_id( $wc_order, $visma_order_id ) {
        WTV_Utils::update_order_meta_compat( $wc_order->get_id(), 'visma_order_id', $visma_order_id );
    }

    /**
     * Fetches postmeta 'visma_order_id' of shop_order
     * @param WC_Order $wc_order
     * @return mixed
     */
    public static function get_visma_order_id( $wc_order ) {
        return $wc_order->get_meta( 'visma_order_id' );
    }

    /**
     * Sends order to Visma either as POST or PUT
     * @param mixed $visma_order
     * @param mixed $wc_order_id
     * @return mixed
     * @throws Exception
     */
    public static function send_order_to_visma( $visma_order, $wc_order_id ){
        # Create Order in Visma or update existing one
        $wc_order = wc_get_order( $wc_order_id );
        if ( self::visma_order_exists( $wc_order ) ) {
            $visma_order_id = self::get_visma_order_id( $wc_order );
            $response =WTV_Request::put( "/orders/{$visma_order_id}", $visma_order );
        }
        else {
            $response = WTV_Request::post( '/orders', $visma_order );
            self::set_visma_order_id( $wc_order, $response->Id );
        }
        return $response;
    }

    /**
     * Checks every cart item for skus. Throws exception if not
     * @throws Exception
     * @param WC_Order $wc_order
     * @return mixed
     */
    public static function validate_order_items( $wc_order ){

	    if( get_option( 'visma_auto_generate_sku' ) ){
	    	return true;
	    }
        foreach( $wc_order->get_items() as $item ) {
            $product_id = $item->get_product_id();
            $product = wc_get_product( $product_id );
            if ( ! $product->get_sku() ) {
                if( ! get_option( 'visma_sync_master_product' ) && $product->get_type() == 'variable' ){
                    continue;
                }

                throw new Exception( __( 'Produkt ID {$product_id} saknar SKU.', WTV_Plugin::TEXTDOMAIN ), WTV_Plugin::INTERNAL_EXCEPTION );
            }
        }
        return true;
    }

    /** Converts order to invoice
     * @param $wc_order
     * @throws Exception
     */
    public static function convert_order_to_invoice( $wc_order ){
        $wc_order->read_meta_data( true );
        $response = WTV_Request::post( '/orders/'. self::get_visma_order_id( $wc_order ) . '/convert', []);
        WTV_Invoices::set_visma_invoice_id( $wc_order, $response->Id );
        WTV_Invoices::set_visma_invoice_number( $wc_order, $response->InvoiceNumber );
    }

    /** Converts order to invoice
     * @param $wc_order
     * @throws Exception
     */
    public static function delete_order( $wc_order ){
        $wc_order->read_meta_data( true );
        WTV_Request::delete( '/orders/'. self::get_visma_order_id( $wc_order ) );
    }

    /**
     * Sets postmeta '_visma_order_synced' of shop_order to true
     * @param $wc_order
     * @param int $sync_status
     * @internal param int $wc_order_id
     */
    public static function set_order_as_synced( $wc_order, $sync_status, $visma_order_id=false ) {
        $wc_order->add_order_note( $sync_status == WTV_Plugin::SYNC_STATUS_ORDER_SYNCED ? __( 'Visma: Order synkroniserad, ID: ' . $visma_order_id, WTV_Plugin::TEXTDOMAIN ) : __( 'Visma: Verifikation skapad', WTV_Plugin::TEXTDOMAIN ) );
        WTV_Utils::update_order_meta_compat(  $wc_order->get_id(), '_visma_order_synced', $sync_status );
    }

    private static function get_vat_percentage( $tax_amount, $amount ){
        if( intval( $amount ) == 0 ){
            return 0;
        }
        return round(floatval($tax_amount/$amount ) * 100 );
    }

    /**
     * Add log to order
     *
     * @param WC_Order $wc_order
     * @param \Exception $error
     */
    public static function add_order_error_log( $wc_order, $error ){
        $wc_order->add_order_note( 'Visma: Fel vid synkronisering </br>' . $error->getMessage() );
    }
}
