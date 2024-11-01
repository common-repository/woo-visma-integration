<?php

namespace includes\api;

use Exception;
use includes\utils\WTV_Utils;
use includes\WTV_Plugin;
use includes\WTV_Sku_Generator;
use WC_Order;
use WC_Product;
use WC_Product_Variable;
use includes\http\WTV_Request;

class WTV_Products {

    /**
     * Check wether product is synced to Visma
     *
     * @param int $wc_product_id
     * @return bool|mixed
     */
	public static function is_synced( $wc_product_id ) {
		$wc_product = new WC_Product( $wc_product_id );

		if( $variations = self::has_variations($wc_product_id) ) {
			foreach($variations as $variation) {
				if( ! get_post_meta($variation->ID, '_is_synced_to_visma', true) ) return false;
			}
			return true;
		}else{
			return get_post_meta( $wc_product->get_id(), '_is_synced_to_visma', true );
		}
	}

    /**
     * Returns true if product is a variation product
     *
     * @param $wc_product
     * @return mixed
     */
	public static function is_variation( $wc_product ) {
		if( get_class( $wc_product ) == 'WC_Product_Variation' ) {
			return true;
		}
		return false;
	}

    /**
     * Get all product Ids
     *
     * @return mixed
     */
    public static function get_all_product_ids() {
	    global $wpdb;
        $results = $wpdb->get_results( "SELECT ID FROM $wpdb->posts p WHERE p.post_type IN ( 'product', 'product_variation') AND NOT EXISTS ( SELECT * FROM $wpdb->postmeta pm
               WHERE pm.meta_key = '_is_synced_to_visma'
        AND pm.post_id=p.ID)" );

        $arr = [];
        foreach ($results as $result){
            $arr[] = $result->ID;
        }
        return $arr;

    }

    /**
     * Product has variations
     *
     * @param int $wc_product_id
     * @return mixed
     */
	public static function has_variations( $wc_product_id ) {
		return get_posts( [
			'post_parent'   => $wc_product_id,
			'post_status'   => "publish",
			'post_type'     => "product_variation",
			'numberposts'   => -1 # remove the output limitation
		] );
	}

    /**
     * Product exists in Visma
     *
     * @param WC_Product $wc_product
     * @return mixed
     * @throws Exception
     */
	public static function exists_in_visma( $wc_product ){

        $visma_article_id = $wc_product->get_meta('visma_article_id');
        if ( empty ( $visma_article_id ) ) {
            return false;
        }
        try {
            $response = WTV_Request::get("/articles/" . $visma_article_id);
        } catch (\Exception $e) {
            return false;
        }
        return $response;
    }

    public static function get_product_by_sku_from_visma( $wc_product ){
        $response = WTV_Request::get('/articles?$filter=Number%20eq%20' . "'" .  $wc_product->get_sku(). "'") ;
        if( property_exists( $response, 'Data' ) &&  intval( $response->Meta->TotalNumberOfResults ) == 1 ){
            return $response->Data[0];
        }
        else{
            return false;
        }
	}

    /**
     * Get account coding
     *
     * @param WC_Product $wc_product
     * @return mixed
     * @throws Exception
     */
    public static function get_account_coding( $wc_product ) {
        return WTV_Visma_Settings::get_account_coding( WTV_Utils::get_wc_tax_rate( $wc_product, get_option('woocommerce_default_country') ), get_option('woocommerce_default_country') );
    }

    /**
     * Returns product from Visma
     *
     * @param int $wc_product_id
     * @return mixed
     * @throws Exception
     */
    public static function get_product( $wc_product_id ){
        $visma_product_id = wc_get_product( $wc_product_id )->get_meta('visma_article_id' );
        if( ! $visma_product_id ){
            throw new Exception( __( 'Produkt finns ej i Visma eEkonomi', WTV_Plugin::TEXTDOMAIN ), WTV_Plugin::INTERNAL_EXCEPTION );
        }
        return WTV_Request::get( "/articles/{$visma_product_id}");
    }

    /**
     * Returns product from Visma
     *
     * @param WC_Product $wc_product
     * @return mixed
     * @throws Exception
     */
    public static function get_visma_article_id( $wc_product ){

        $visma_product_id = $wc_product->get_meta( 'visma_article_id' );

        if( ! $visma_product_id ){
            throw new Exception( __( 'Produkt finns ej i Visma eEkonomi', WTV_Plugin::TEXTDOMAIN ), WTV_Plugin::INTERNAL_EXCEPTION );
        }
        return $visma_product_id;
    }

    /**
     * Get variation name
     *
     * @param \WC_Order_Item $item
     * @param WC_Product $wc_product
     * @return mixed
     * @throws Exception
     */
    public static function get_variation_name( $item, $wc_product ) {
        $attribute_keys = array();
        $attributes = $wc_product->get_attributes();
        foreach( $attributes as $k =>$v ){
            if( $v['is_variation'] == 1 ){
                array_push( $attribute_keys, $k );
            }
        }
        if( count( $attribute_keys ) > 0){
            $str = '';
            foreach( $attribute_keys as $k ){
                $str .= $item[$k];
            }
            return self::truncate_over_fifty($item['name'] . ' - ' . $str );
        }
        else{
            return self::truncate_over_fifty( $item['name'] );
        }
    }

    /**
     * Sanitize description
     *
     * @param string $description
     * @return string
     */
    public static function sanitize_description( $description ) {

        $description = mb_substr( sanitize_text_field( preg_replace('/\"|\|/', '', $description ) ), 0, 50  );
        $description = str_replace('&amp;', '&', $description);
        return $description;
    }

    /**
     * Sanitized SKU
     *
     * @since 2.0.4 - allowed all characters in SKU - https://www.wrike.com/open.htm?id=860942933
     *
     * @param string $sku
     * @return string
     */
    public static function sanitized_sku( $sku ) {
    	global $wpdb;
    	return $wpdb->_real_escape( str_replace( ' ', '-', $sku ) );
        // return preg_replace('/[^A-Za-z0-9-+._\/]/', '', $sku );
    }

    /**
     * Set Visma id
     *
     * @param WC_Product $wc_product
     * @param string $visma_product_id
     * @return string
     */
    public static function set_visma_product_id( $wc_product, $visma_product_id ) {
        $wc_product->delete_meta_data( 'visma_article_id' );
        $wc_product->add_meta_data( 'visma_article_id', $visma_product_id );
        $wc_product->save();
    }

    /**
     * Send article to Visma
     *
     * @param int $wc_product_id
     * @param array|bool $return_response
     * @param bool $sync_stock
     * @return mixed
     * @throws Exception
     */
	public static function sync( $wc_product_id, $return_response=false, $sync_stock=false ) {

		$wc_product = wc_get_product( $wc_product_id );
		$sku = $wc_product->get_sku();

		if( ! $sku ) {
			if( get_option( 'visma_auto_generate_sku' ) ){
				$sku = WTV_Sku_Generator::set_new_sku( $wc_product );
			}
			else{
				throw new Exception(
					__( 'Produkt ID {$wc_product_id} saknar SKU.', WTV_Plugin::TEXTDOMAIN , WTV_Plugin::INTERNAL_EXCEPTION )
				);
			}
		}

        $sku = WTV_Products::sanitized_sku( $sku );

		if( $variations = self::has_variations( $wc_product_id ) ) {
			if( ! get_option( 'visma_skip_product_variations' ) )
				foreach( $variations as $variation )
					self::sync( $variation->ID,false, $sync_stock );
			if( ! get_option( 'visma_sync_master_product' ) ){
                update_post_meta( $wc_product_id, '_is_synced_to_visma', 1 );
                return $sku;
            }

		}

		$wc_product_title = str_replace('"', "'", $wc_product->get_title() );

		if( get_class( $wc_product ) == 'WC_Product_Variation' ) {
			$variation_title_arr = $wc_product->get_variation_attributes();
			$variation_title = reset( $variation_title_arr );
            $wc_product_title = $wc_product->get_title() . ' - ' . $variation_title;
		}

		$article = apply_filters( 'wetail_visma_sync_product_article', [
			'IsActive'      => true,
			'Number'        => $sku,
			'Name'          => self::sanitize_description( $wc_product_title ),
            'CodingId'      => self::get_account_coding( $wc_product ),
            'UnitId'        => WTV_Visma_Settings::get_standard_unit_id(),
            'IsStock'       => $wc_product->managing_stock() ? 1 : 0
		]);


		if( $sync_stock ){
            $article['StockBalance'] = $wc_product->managing_stock() ? $wc_product->get_stock_quantity() : 0;
        }

        if( ! get_option( 'visma_do_not_sync_price' ) ){
            $article['NetPrice'] =  WTV_Utils::format_number( wc_get_price_excluding_tax( $wc_product ) );
            $co_eff = ( ( 100 + intval( WTV_Utils::get_wc_tax_rate( $wc_product, get_option('woocommerce_default_country') ) ) ) / 100  );
            $gross_price = $co_eff * wc_get_price_excluding_tax( $wc_product );
            $article['GrossPrice'] =  WTV_Utils::format_number( $gross_price );
        }

		if ( ! $visma_article =  self::exists_in_visma( $wc_product )) {
            $visma_article = self::create_article_in_visma( $wc_product, $article );
		}
		else {
            $visma_article = self::update_article_in_visma( $wc_product, $article, $visma_article );
		}
		update_post_meta( $wc_product_id, '_is_synced_to_visma', 1 );

		if ( $return_response )
			return $visma_article;

		return $sku;
	}

    /**
     * @param $wc_product
     * @param $payload
     */
    public static function create_article_in_visma( $wc_product, $payload ){
        try{
            $visma_article = WTV_Request::post( '/articles',  $payload );
        }
        catch ( Exception $exception ){
            if ( WTV_Request::ERROR_CODE_DUPLICATE === $exception->getCode() ){
                $visma_article = self::get_product_by_sku_from_visma( $wc_product );
            }
            else{
                throw $exception;
            }

        }
        self::set_visma_product_id( $wc_product, $visma_article->Id );
        return $visma_article;
    }

    /**
     * @param $wc_product
     * @param $payload
     */
    public static function update_article_in_visma( $wc_product, $article ){
        $visma_article_id = self::get_visma_article_id( $wc_product );
        $visma_article = WTV_Request::get("/articles/" . $visma_article_id);
        $payload = json_decode(json_encode( $visma_article ), true);
        wtv_write_log( "ARTICLE" );
        wtv_write_log( $article );
        $article['ArticleId'] = $visma_article_id;
        $payload = array_replace( $payload, $article );
        wtv_write_log( "payload" );
        wtv_write_log( $payload );
        $payload['NetPrice'] = WTV_Utils::format_number( $payload['NetPrice'] );
        $payload['GrossPrice'] = WTV_Utils::format_number( $payload['GrossPrice'] );
        return WTV_Request::put( "/articles/{$visma_article_id}", $payload );
    }

    /**
     * Update stock from Visma
     * @param int $wc_product_id
     * @return mixed
     * @throws Exception
     */
	public static function update_stock_from_visma( $wc_product_id ) {

		$wc_product = wc_get_product( $wc_product_id);

		if( $variations = self::has_variations( $wc_product_id ) ) {
			if( ! get_option( 'visma_skip_product_variations' ) ) {
				foreach( $variations as $variation ) {
					self::update_stock_from_visma( $variation->ID );
				}
			}
            $wc_product->set_manage_stock( false );
            WC_Product_Variable::sync_stock_status( $wc_product );
			if( ! get_option( 'visma_sync_master_product' ) || get_option( 'visma_sync_master_product' ) != 0 ) return;
		}

		$sku = $wc_product->get_sku();
        $sku = WTV_Products::sanitized_sku( $sku );

		if( ! $sku ){
			throw new Exception(
				__( 'Produkt ID {$wc_product_id} saknar SKU.', WTV_Plugin::TEXTDOMAIN, WTV_Plugin::INTERNAL_EXCEPTION )
			);
		}

		try {
            $visma_article_id = self::get_visma_article_id( $wc_product );
			$response = WTV_Request::get( '/articles/{$visma_article_id}' );

			if( isset( $response->Article->QuantityInStock ) ) {
                # Set stock status
				$wc_product->set_stock_quantity( $response->Article->QuantityInStock );
            }
		}
		catch( Exception $error ) {
            wtv_write_log( $error->getMessage() );
			throw new Exception( 'Product ID {$wc_product_id}: ' . $error->getMessage(), WTV_Plugin::INTERNAL_EXCEPTION );
		}
	}

    /**
     * Update price to Visma
     * @param int $wc_product_id
     * @return mixed
     * @throws Exception
     */
	public static function update_price_from_visma( $wc_product_id ) {

        $wc_product = wc_get_product( $wc_product_id);
		$sku = $wc_product->get_sku();
        $sku = WTV_Products::sanitized_sku( $sku );

		if( ! $sku ) {
			throw new Exception(
				__( 'Produkt ID {$wc_product_id} saknar SKU.', WTV_Plugin::TEXTDOMAIN, WTV_Plugin::INTERNAL_EXCEPTION )
			);
		}
		$visma_default_price_list = get_option( 'visma_default_price_list' );
		$priceList = empty( $visma_default_price_list ) ? 'A' : get_option( 'visma_default_price_list' );

        https://identity-sandbox.test.vismaonline.com/connect/authorize?client_id=wetail&redirect_uri=https://lagerkolltest.wetail.io/wp-json/visma/callback&scope=ea:api%20offline_access&response_type=code
		$response = WTV_Request::get( '/prices/{$priceList}/{$sku}' );

		if( ! empty( $response->ErrorInformation ) ) {
			throw new Exception( '{$response->ErrorInformation->message} (Felkod: {$response->ErrorInformation->code})' );
		}

		# Update regular price
		update_post_meta( $wc_product_id, '_regular_price', $response->Price->Price );

		if( ! get_post_meta( $wc_product_id, '_sale_price', true ) ) {
			update_post_meta( $wc_product_id, '_price', $response->Price->Price );
		}
	}

	# Get product from Visma
	public static function get( $wc_product ) {
        $visma_article_id = self::get_visma_article_id( $wc_product );

		try {
			$response = WTV_Request::get( "/articles/{$visma_article_id}" );
			return $response;
		}
		catch( Exception $error ) {
            wtv_write_log( $error->getMessage() );
			throw new Exception( $error->getMessage(), WTV_Plugin::INTERNAL_EXCEPTION );
		}
	}

    # Truncate string
    public static function truncate_over_fifty( $str ){
        return mb_substr($str , 0, 49);
    }

}
