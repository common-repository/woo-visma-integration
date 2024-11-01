<?php
/**
 * Contains the class that handles the plugins meta fields for WooCommerce products.
 *
 * @package Wetail\Wiz\Admin
 * @link    https://www.onlineforce.net
 * @since   3.1.0
 */


namespace includes\views;

use includes\WTV_Plugin;

defined( 'WPINC' ) || die;

/**
 * Handles the plugins meta fields for WooCommerce products.
 *
 * @since 3.1.0
 */
final class WTV_Product_Fields {

    /**
     * Registers the fields with WooCommerce.
     *
     * @return void
     * @since 3.1.0
     */
    public static function init() {


        self::add_visma_article_id_field();
        self::add_variations_visma_article_id_field();

        // Handle saves for non variants.
        add_action(
            'woocommerce_admin_process_product_object', function( $wc_product ) {
            self::process_save( $wc_product );
        }
        );

        // Handle saves for variations.
        add_action( 'woocommerce_save_product_variation', function( $post_id, $i ) {
            self::process_save_variation( $post_id, $i );
        }, 10, 2 );

        // Do not include visma_article_id on product duplication
        add_action( 'woocommerce_duplicate_product_exclude_meta', __CLASS__ . '::exclude_meta' );
    }

    /**
     * Do not include visma_article_id on product duplication
     *
     * @since 2.0.3
     * @url: https://www.wrike.com/open.htm?id=701034620
     *
     * @param array $excludes
     * @return array
     */
    static function exclude_meta( $excludes ){
        return array_merge( $excludes, [ 'visma_article_id' ] );
    }


    private static function add_visma_article_id_field() {
        add_action(
            'woocommerce_product_options_pricing', function() {
            global $post;
            $product = wc_get_product( $post->ID );
            if ( 'variable' === wc_get_product( $post->ID )->get_type() ) {
                return;
            }

            echo woocommerce_wp_text_input( // WPCS: XSS ok.
                [
                    'id'          => 'visma_article_id',
                    'value'       => $product->get_meta( 'visma_article_id' ),
                    'label'       => __( 'Visma produkt ID', WTV_Plugin::TEXTDOMAIN ),
                    'desc_tip'    => true,
                ]
            );
        }
        );
    }

    private static function add_variations_visma_article_id_field() {
        add_action(
            'woocommerce_product_after_variable_attributes',
            function( $loop, $variation_data, $variation ) {
                echo '<div class="variation-custom-fields">';
                $product = wc_get_product( $variation->ID );
                woocommerce_wp_text_input(
                    [
                        'id'            => 'visma_article_id[' . $loop . ']',
                        'label'         => __( 'Visma produkt ID', WTV_Plugin::TEXTDOMAIN ),
                        'wrapper_class' => 'form-row form-row-first',
                        'value'         => $product->get_meta( 'visma_article_id' ),
                    ]
                );

                echo '</div>';
            }, 10, 3
        );
    }


    private static function process_save( $wc_product ) {
        if ( 'variable' === $wc_product->get_type() ) {
            return;
        }

        $nonce_is_valid = wp_verify_nonce(
            $_REQUEST['woocommerce_meta_nonce'],
            'woocommerce_save_data'
        );

        if ( ! $nonce_is_valid ) {
            die( 'Invalid nonce' );
        }

        if ( isset( $_POST[ 'visma_article_id' ] ) ) {
            $wc_product->update_meta_data('visma_article_id', $_POST[ 'visma_article_id' ] );
            $wc_product->save_meta_data();
        }
    }

    private static function process_save_variation( $post_id, $i ) {

        if ( isset( $_POST[ 'visma_article_id' ][ $i ] ) ) {
            $wc_product = wc_get_product( $post_id );
            $wc_product->update_meta_data('visma_article_id', $_POST[ 'visma_article_id' ][ $i ] );
            $wc_product->save_meta_data();
        }
    }



}
