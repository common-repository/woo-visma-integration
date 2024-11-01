<?php

namespace includes\api;

use Exception;
use includes\WTV_Plugin;
use includes\utils\WTV_Utils;
use WC_Customer;
use WC_Order;
use includes\http\WTV_Request;

class WTV_Customers {


    /**
     * Sync customer
     *
     * 
     * @param WC_Order $wc_order
     * @return int
     * @throws Exception
     */
    static public function sync( $wc_order ) {

        try{
            $existing_customer = null;
            $visma_customer_id = self::customer_exists( $wc_order );

            if( $visma_customer_id ){
                $existing_customer = self::get( $visma_customer_id );
            }

            if( empty( $existing_customer ) ) {
                $response = WTV_Request::post( '/customers',  self::format_params( $wc_order ) );
                self::set_visma_customer_id( $wc_order, $response->Id );
                $visma_customer_id = $response->Id;
            }
            elseif( ! empty( $existing_customer ) ) {
                if( ! get_option( 'visma_do_not_update_customer_on_order_sync' ) ){
                    self::update_existing_customer( $existing_customer, self::format_params( $wc_order ), $visma_customer_id );
                }
            }

            return $visma_customer_id;

        }
        catch( Exception $error ) {
            throw new Exception( $error->getMessage(), WTV_Plugin::INTERNAL_EXCEPTION );
        }
    }

    /**
     * @param $wc_order
     * @throws Exception
     */
    static function create_customer( $wc_order ){
        $response = WTV_Request::post( '/customers',  self::format_params( $wc_order ) );
        self::set_visma_customer_id( $wc_order, $response->Id );
        return $response->Id;
    }

    /**
     *  Queries Visma API if customer exists
     * @param WC_Order $wc_order
     * @return mixed|void
     * @throws Exception
     */
    static function customer_exists( $wc_order ){

        $visma_customer_id = self::get_visma_customer_id( $wc_order->get_billing_email() );
        if( $visma_customer_id && get_option('visma_customer_unique_identifier') != 'organization_number'){
            return $visma_customer_id;
        }
        else{
            if( get_option('visma_customer_unique_identifier') === 'organization_number' ){
                $query = rawurlencode("CorporateIdentityNumber eq '" . WTV_Utils::get_order_meta_compat( $wc_order->get_id(), get_option('visma_organization_number_meta_key') ) . "'");
            }
            else{
                $query = rawurlencode("EmailAddress eq '" . $wc_order->get_billing_email() . "'");
            }
            $response = WTV_Request::get( '/customers/?$filter=' . $query);
            if( ! empty( $response ) && count( $response->Data) > 0){
                self::set_visma_customer_id( $wc_order, $response->Data[0]->Id );
                return $response->Data[0]->Id;
            }
        }
    }

    /**
     * Get customer number by visma_customer_id
     *
     * @param string $visma_customer_id
     * @return mixed|void
     * @throws Exception
     */
	static public function get( $visma_customer_id ) {

		// If no email is sent, fake one that can't possibly be used already
		if ( empty( $visma_customer_id ) || !isset( $visma_customer_id ) )
			return;

		try {
			$response = WTV_Request::get( '/customers/' . $visma_customer_id );
			if( ! empty( $response ) )
				return $response;
		}
		catch( Exception $error ) {
            return false;
            wtv_write_log( "EXCEPTION" . $error->getMessage() );
			throw new Exception( $error->getMessage(), WTV_Plugin::INTERNAL_EXCEPTION );
		}
	}

    /**
     * Get customer
     *
     * @param $customer_email
     * @return array
     * @throws Exception
     * @internal param WC_Customer $customer
     */
    static public function get_customer( $customer_email ) {
        $visma_customer_id = get_option('visma_customer_' . $customer_email );

        if( ! $visma_customer_id ){
            throw new Exception( __( 'Kund finns ej i Visma eEkonomi', WTV_Plugin::TEXTDOMAIN ), WTV_Plugin::INTERNAL_EXCEPTION );
        }
        return WTV_Request::get( "/customers/{$visma_customer_id}");
    }

    /**
     * Returns customer number
     * @param WC_Order $wc_order
     * @return int
     * @throws Exception
     */
    public static function get_customer_number( $wc_order ){
        $customer = WTV_Customers::format_params( $wc_order );
        return WTV_Customers::sync( $customer );
    }

    /**
     * Sets Visma customer ID
     *
     * 
     * @param string $email
     * @return int $visma_customer_id
     */
    static private function get_visma_customer_id( $email ) {
        return get_option( 'visma_customer_' . $email );
    }

    /**
     * Sync customer
     *
     * 
     * @param WC_Order $wc_order
     * @return int
     * @throws Exception
     */
    static public function get_visma_customer_number( $wc_order ) {
        $visma_customer_id = self::get_visma_customer_id( $wc_order->get_billing_email() );
        if( $visma_customer_id ){
            return $visma_customer_id;
        }
        return false;
    }

    /**
     * Format Customer.
     *
     * @param WC_Order $wc_order
     * @return mixed
     */
    public static function format_params( $wc_order ){

        $settings = get_option( 'visma_order_sync_settings_' . $wc_order->get_payment_method() );
        $address = $wc_order->get_address();
        $shipping_address2 = $address['country'] == 'US' ? $wc_order->get_shipping_state() : $wc_order->get_shipping_address_2();
        $customer = apply_filters( 'wetail_visma_sync_modify_customer', [
            'ContactPersonEmail'                    => $address['email'],
            'EmailAddress'                          => $address['email'],
            'ContactPersonName'                     => $wc_order->get_billing_first_name() .' '. $wc_order->get_billing_last_name(),
            'DeliveryCustomerName'                  => ! empty( $wc_order->get_shipping_company() ) ? $wc_order->get_shipping_company() : $wc_order->get_shipping_first_name() . ' ' . $wc_order->get_shipping_last_name(),
            'CurrencyCode'                          => $wc_order->get_currency(),
            'Name'                                  => ! empty( $address['company'] ) ? $address['company'] : $address['first_name'] . ' ' . $address['last_name'],
            'IsPrivatePerson'                       => ( ! empty( $address['company'] ) ? false : true ),
            'CorporateIdentityNumber'               => WTV_Utils::get_order_meta_compat( $wc_order->get_id(), '_billing_company_number' ),
            'VatNumber'                             => WTV_Utils::get_order_meta_compat( $wc_order->get_id(), '_vat_number' ),
            'Address1'                              => $wc_order->get_billing_address_1(),
            'Address2'                              => $wc_order->get_billing_address_2(),
            'ZipCode'                               => $wc_order->get_billing_postcode(),
            'City'                                  => $wc_order->get_billing_city(),
            'CountryCode'                           => $wc_order->get_billing_country(),
            'ReverseChargeOnConstructionServices'   => false,
            'IsActive'                              => true,
            'InvoiceAddress1'                       => $wc_order->get_billing_address_1(),
            'InvoiceAddress2'                       => $wc_order->get_billing_address_2(),
            'InvoiceCity'                           => $wc_order->get_billing_city(),
            'InvoiceCountryCode'                    => $wc_order->get_billing_country(),
            'InvoicePostalCode'                     => $wc_order->get_billing_postcode(),
            'DeliveryAddress1'                      => $wc_order->get_shipping_address_1(),
            'DeliveryAddress2'                      => $shipping_address2,
            'DeliveryCity'                          => $wc_order->get_shipping_city(),
            'Currency'                              => $wc_order->get_currency(),
            'DeliveryCountryCode'                   => $wc_order->get_shipping_country() ? $wc_order->get_shipping_country() : $wc_order->get_billing_country() ,
            'DeliveryName'                          => $wc_order->get_shipping_first_name() .' '. $wc_order->get_shipping_last_name(),
            'DeliveryPostalCode'                    => $wc_order->get_shipping_postcode(),
            'ContactPersonPhone'                    => $address['phone'],
            'Telephone'                             => $address['phone'],
            'TermsOfPaymentId'                      => $settings['terms_of_payment_id'],
        ], $wc_order->get_user(), $wc_order);

        return $customer;

    }

    /**
     * Sets Visma customer ID
     *
     * 
     * @param WC_Order $wc_order
     * @param $visma_customer_id
     * @return int $visma_customer_id
     */
    static private function set_visma_customer_id( $wc_order, $visma_customer_id ) {
        update_option( 'visma_customer_' . $wc_order->get_billing_email(), $visma_customer_id );
    }

    public static function update_existing_customer( $existing_customer, $customer_params, $visma_customer_id ){
        $customer_response = json_decode(json_encode( $existing_customer ), TRUE);
        $customer_params = array_replace($customer_response, $customer_params );
        try{
            WTV_Request::put( '/customers/' . $visma_customer_id, $customer_params );

        } catch ( Exception $e ){
        }
    }
}
