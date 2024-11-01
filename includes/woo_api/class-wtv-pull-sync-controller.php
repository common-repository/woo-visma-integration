<?php

namespace includes\woo_api;

use includes\api\WTV_Products;
use includes\http\WTV_Request;
use WC_Product_Variable;
use WP_REST_Controller;

class WTV_Pull_Sync_Controller extends WP_REST_Controller{

    /**
     * Inits callback route
     */
    public function register_routes(){

        /** Endpoint called by Lagerkoll Service**/
        register_rest_route( WTV_API_NAMESPACE, '/pull_sync/', array(
            'methods' => 'GET',
            'callback' => array( $this, 'run_pull_sync' ),
        ));

        register_rest_route( WTV_API_NAMESPACE, '/match_skus/', array(
            'methods' => 'GET',
            'callback' => array( $this, 'match_products' ),
        ));

        register_rest_route( WTV_API_NAMESPACE, '/compare_skus/', array(
            'methods' => 'GET',
            'callback' => array( $this, 'match_products' ),
        ));
    }

    /**
     * Main fetching function which is triggered by scheduler Loops through all
     * articles in Visma and updates inventory + price in WooCommerce.
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     * @throws \Exception
     */
    public static function run_pull_sync( $request ) {
        wtv_write_log("RUNNING PULL TASK");

        foreach ( self::get_all_visma_products() as $product ) {
                wtv_write_log('Updating ' . $product['visma_article_id'] . ' ' . $product['stock_balance_available']);
                self::update_inventory( $product['visma_article_id'], $product['stock_balance_available'] );
        }
        update_option('latest_visma_pull_sync', time());
        return new \WP_REST_Response( 'ALL OK', 200 );
    }

    /**
     * Returns all products that have been modified since
     * @return array
     * @throws \Exception
     */
    public static function get_all_visma_products(){
        $num_pages = self::get_visma_products_num_pages();
        $current_page = 1;

        $products = [];
        while ( $current_page <= $num_pages) {
            $products = array_merge( $products, self::get_visma_products( $current_page ) );
            $current_page++;
        }
        return $products;
    }

    /**
     * Return query to appned to URL
     */
    private static function get_url( $page=1 ){
        if( ! get_option( 'latest_visma_pull_sync' ) ){
            return '/articles?$pagesize=1000&$page=' . $page;
        }
        else{
            $filter = '$pagesize=1000&$page=' . $page . '&$filter';
            return "/articles?$filter=ChangedUtc%20gt%20" . str_replace('+00:00', 'Z', gmdate('c', get_option( 'latest_visma_pull_sync' ) ) );
        }
    }

    /**
     * Gets products (articles) from Visma and returns stock and price
     *
     *
     * @return array
     * @throws \Exception
     */
    public static function get_visma_products_num_pages() {
        $response = WTV_Request::get( self::get_url() );

        if( property_exists( $response, 'Meta' ) ){
            return $response->Meta->TotalNumberOfPages;
        }
    }


    /**
     * Gets products (articles) from Visma and returns stock and price
     *
     *
     * @return array
     * @throws \Exception
     */
    public static function get_visma_products( $page=1 ) {

        $response = WTV_Request::get( self::get_url( $page ) );

        $arr = [];
        if( property_exists( $response, 'Data' ) ){
            foreach ( $response->Data as $article) {
                $arr[] = [
                    'visma_article_id'          => $article->Id,
                    'sku'                       => $article->Number,
                    'stock_balance_available'   => $article->StockBalanceAvailable
                ];
            }
        }
        else {
            return [];
        }

        return $arr;

    }

    /**
     * Update inventory from Visma
     *
     * @param string $visma_article_id The Visma Id
     * @param int $new_quantity The new inventory quantity
     * @return bool
     */
    public static function update_inventory( $visma_article_id, $new_quantity ) {

        global $wpdb;

        $product_id = self::get_wc_product_id_by_visma_article_id( $visma_article_id );

        if ( $product_id === null) {
            wtv_write_log('Returning ' . $visma_article_id );
            return false;
        }

        $product = wc_get_product( $product_id );
        if( ! $product ){
            return false;
        }

        if( ! $product->managing_stock() ){
            wtv_write_log('Returning ' . $visma_article_id );
            return false;
        }

        if( intval( $product->get_stock_quantity() ) == intval( $new_quantity ) ){
            return false;
        }

        $product->set_stock_quantity( $new_quantity );
        $product->save();

        if ( is_a( $product, 'WC_Product_Variation' ) ) {
            \WC_Product_Variable::sync_stock_status( $product_id );
        }

    }

    /**
     * Gets a WC product ID by it's visma_article_id
     *
     * @param string $visma_article_id    The visma_article_id to query
     *
     * @return int
     */
    public static function get_wc_product_id_by_visma_article_id( $visma_article_id ) {

        global $wpdb;

        $product_id = $wpdb->get_var( $wpdb->prepare(
            "SELECT post_id FROM $wpdb->postmeta WHERE meta_key='visma_article_id' AND meta_value='%s' LIMIT 1",
            $visma_article_id
        ));

        if ( $product_id ) {
            return $product_id;
        }

        return null;

    }

    /** Fetches all products in DB and then fetches visma product by Visma Article ID then compares Woo Product SKU to
     * Visma Product SKU
     * @param int $request
     */
    public static function compare_skus( $request=1 ) {
        $index = 0;
        foreach ( wc_get_products( ['posts_per_page' => -1] ) as $product) {

            if( is_a($product, 'WC_Product_Variable') ){
                foreach ( $product->get_children() as $child_id) {
                    $child = wc_get_product( $child_id );
                    self::compare_visma_sku( $child) ;
                }
            }
            else{
                self::compare_visma_sku( $product );
            }
            $index++;
        }

    }

    public static function compare_visma_sku( $product ){
        $visma_article_id = $product->get_meta('visma_article_id' );

        if( ! $visma_article_id ){
            wtv_write_log("Visma SKU: missing for"  . $product->get_sku() . " ");
            return;
        }
        try{
            $visma_article = WTV_Request::get("/articles/" . $visma_article_id );
        }
        catch (\Exception $e){
            return;
        }

        if( $visma_article->Number != $product->get_sku() ){
            wtv_write_log("Visma SKU: " . $visma_article->Number . "\t\t\t\t\t\t\t ". "Woo SKU: " . $product->get_sku() . " ");
        }

    }

    /** Fetches all Visma products and matches to Woo Product by SKU and then sets correct Visma Article ID
     * @param int $page
     * @throws \Exception
     */
    public static function match_products( $page=1 ) {

        $articles = self::get_all_visma_products();
        wtv_write_log($articles);
        foreach ( $articles as $article) {

            wtv_write_log("Visma SKU "  . $article['sku']);
            $woo_id = wc_get_product_id_by_sku( $article['sku'] );

            if( $woo_id ){
                $product = wc_get_product( $woo_id );
                WTV_Products::set_visma_product_id( $product, $article['visma_article_id']);
                update_post_meta( $woo_id, '_is_synced_to_visma', 1 );
            }
        }
    }

}
