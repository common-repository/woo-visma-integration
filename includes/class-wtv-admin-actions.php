<?php

namespace includes;

use Exception;
use includes\api\WTV_Error_Handling;
use includes\api\WTV_Orders;
use includes\api\WTV_Products;
use includes\api\WTV_Refunds;
use includes\api\WTV_Sync_Controller;
use includes\api\WTV_Visma_Settings;
use includes\utils\WTV_Utils;

class WTV_Admin_Actions
{
    /**
     * @return array
     */
    public static function update_settings()
    {
        WTV_Visma_Settings::get_and_save_settings();
        return [
            'error' => false,
            'message' => __("Inställningar hämtade från Visma.", WTV_Plugin::TEXTDOMAIN)
        ];
    }

    /**
     *
     */
    public static function bulk_sync_orders()
    {
        $order_ids = self::get_orders_for_date_range_sync($_REQUEST['from_date'], $_REQUEST['to_date'], $_REQUEST['status']);
        if (!$order_ids) {
            return [
                'error' => true,
                'message' => __('Inga ordrar hittades i intervallet.',
                    WTV_Plugin::TEXTDOMAIN),
            ];
        }
        return [
            'error' => false,
            'order_ids' => $order_ids,
        ];
    }

    /**
     *
     * @param $from_date
     * @param $to_date
     * @return array
     */
    private static function get_orders_for_date_range_sync($from_date, $to_date, $status)
    {
        $query_args = [
            'numberposts' => -1,
            'post_type' => 'shop_order',
            'post_status' => 'wc-' . $status,
            'orderby' => 'post_date',
            'order' => 'ASC',
            'date_query' => [
                'after' => $from_date . '00:00:00',
                'before' => $to_date . '23:59:59',
            ],
        ];
        return wp_list_pluck(get_posts($query_args), 'ID');
    }

    /**
     *
     */
    public static function bulk_sync_products()
    {
        $product_ids = self::get_products();
        if (!$product_ids) {
            return [
                'error' => true,
                'message' => __( 'Inga produkter finns tillgängliga.', WTV_Plugin::TEXTDOMAIN),
            ];
        }
        return [
            'error' => false,
            'product_ids' => $product_ids,
        ];
    }

    /**
     * @return array
     */
    private static function get_products()
    {
        $query = new \WC_Product_Query(array(
            'limit' => -1,
            'orderby' => 'date',
            'order' => 'DESC',
            'return' => 'ids',
        ));
        return $query->get_products();
    }

    /**
     * @return array
     */
    public static function sync_order( $order_id )
    {
        try {
            $response = WTV_Sync_Controller::sync( $order_id );
            if( $refund_response = self::maybe_sync_refund( $order_id ) ){
                $response = $refund_response;
            }
        } catch ( Exception $error ) {
            if ( $error->getCode() === WTV_Orders::VISMA_ERROR_CODE_ORDER_ALREADY_INVOICED ){
                if( $refund_response = self::maybe_sync_refund( $order_id ) ){
                    wtv_write_log("Doing refund!");
                    return $refund_response;
                }
            }
            return [
                'error' => true,
                'message' => WTV_Error_Handling::get_error_message( $error->getMessage(), $error->getCode() )
            ];
        }
        if ( empty( $response['error'] ) )
            return [
                'error' => false,
                'message' => __( "Order har synkroniserats.", WTV_Plugin::TEXTDOMAIN )
            ];
    }


    /**
     * @param $order_id
     * @return array
     * @throws \Exception
     */
    private static function maybe_sync_refund( $order_id ) {
        $did_do_refund = false;

        /** Checks if there are any WooCommerce refunds made on this order.
         * If so, then condition is true and all refund_ids are assigned
         * to var $refund_ids
         */
        if( $refund_ids = WTV_Utils::get_refunds( $order_id ) ){

            $wc_order = wc_get_order( $order_id );

            /** Checks if order is refunded and that all refunds are NOT synced to visma
             * If so, a full refund is processed
             */
            if( $wc_order->get_status() == 'refunded' && ! WTV_Refunds::is_refund_synced( $order_id ) ){
                $did_do_refund = true;
                $order = wc_get_order( $order_id );
                WTV_Refunds::process_full_refund( $order, $refund_ids[0] );
            }
            else{
                /** If order is not refunded or if all refunds are not synced to Fortnox, every
                 * refund of order is processed individually as partial refunds
                 */
                foreach ( $refund_ids as $refund_id ){
                    if( ! WTV_Refunds::is_refund_synced( $refund_id ) ){
                        $did_do_refund = true;
                        $order = wc_get_order( $order_id );
                        WTV_Refunds::process_partial_refund( $order, $refund_id );
                    }
                }
            }

            if( $did_do_refund ){
                return [
                    'error' => false,
                    'message' => __( "Order har synkroniserats.", WTV_Plugin::TEXTDOMAIN )
                ];
            }
        }
    }
    /**
     * @return array
     */
    public static function sync_product()
    {
        if (empty($_REQUEST['product_id'])) {
            return [
                'error' => true,
                'message' => __("Produkt ID saknas.", WTV_Plugin::TEXTDOMAIN)
            ];
        }

        try {
            WTV_Products::sync($_REQUEST['product_id'], $return_response = false, $sync_stock = true);
        } catch (Exception $error) {
            return [
                'error' => true,
                'message' => WTV_Error_Handling::get_error_message($error->getMessage(), $error->getCode())
            ];
        }

        if ( empty($response['error'] ) )
            return [
                'error' => false,
                'message' => __("Produkt har synkroniserats.", WTV_Plugin::TEXTDOMAIN)
            ];
    }
}
