<?php

/**
 * Plugin Name: WooCommerce Visma integration
 * Description: Synkronisera kunder, produkter and och ordrar WooCommerce till Visma eEkonomi. Spara tid och pengar på minskad administration.
 * Version: 2.3.4
 * Author: Wetail
 * Author URI: https://wetail.io
 * Text domain: woo-visma-integration
 * WC requires at least: 4.0.0
 * WC tested up to: 9.1
 * Tested up to: 6.6
 */

define ( 'WTV_API_NAMESPACE', 'visma/' );
define ( 'WTV_PATH', dirname(__FILE__ ) );

define("WOOCOMMERCE_VISMA_INTEGRATION_TESTING", false );

require_once 'autoload.php';

use includes\api\WTV_Sync_Controller;
use includes\api\WTV_Refunds;
use includes\api\WTV_Vouchers;
use includes\views\WTV_Product_Fields;
use includes\views\WTV_Visma_Invoice_Widget;
use includes\WTV_Ajax;
use includes\WTV_Plugin;
use includes\woo_api\WTV_Routes;
use includes\WTV_Migrate;


add_action( 'before_woocommerce_init', function() {
    if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
    }
} );


if ( ! function_exists('wtv_write_log')) {
    function wtv_write_log ( $log )  {
        if( get_option( 'visma_debug_log' ) ){
            $logger = wc_get_logger();
            $context = array( 'source' => 'wetail_visma' );
            if ( is_array( $log ) || is_object( $log ) ) {
                $logger->debug( print_r( $log, true ), $context );
            } else {
                $logger->debug( $log, $context );
            }
        }
        else if( WOOCOMMERCE_VISMA_INTEGRATION_TESTING ) {
            if ( is_array( $log ) || is_object( $log ) ) {
                print_r( $log, true );
            } else {
               echo $log;
            }
        }
    }
}

/**
 * Load plugin textdomain and enable order auto sync action
 */
add_action( 'plugins_loaded', function() {
    WTV_Plugin::load_text_domain();
	# Auto sync order if set in options page
    add_action( 'woocommerce_order_status_changed', function( $order_id,  $status_transition_from, $status_transition_to, $order ) {
        try {
            $sync_settings = get_option( 'visma_order_sync_settings_' . $order->get_payment_method(),[] );
            wtv_write_log($sync_settings);
            if( array_key_exists( 'sync_on_status', $sync_settings ) && $sync_settings['sync_on_status'] == $status_transition_to ){
                WTV_Sync_Controller::sync( $order_id );
            }

        }
        catch( Exception $error ) {
            wtv_write_log($error->getMessage() . ' (Felkod: ' . $error->getCode() . ')' );
            wp_die( $error->getMessage() . ' (Felkod: ' . $error->getCode() . ')' );
        }
    }, 10, 4 );


	# create credit note on refund
	if ( get_option('visma_credit_voucher_on_refund') ) {
        add_action( 'woocommerce_order_refunded', [ 'includes\api\WTV_Vouchers', 'trigger_credit_voucher' ], 10, 2 );
	}

    # create credit note on refund
	if ( get_option('visma_credit_note_on_refund') ) {
        add_action( 'woocommerce_order_refunded', [ 'includes\api\WTV_Refunds', 'handle_refund' ], 10, 2 );
	}

	# Single order invoice widget
	add_action( 'init', [ 'includes\views\WTV_Visma_Invoice_Widget', 'maybe_handle_pdf_file_request' ] );
	add_action( 'add_meta_boxes', [ 'includes\views\WTV_Visma_Invoice_Widget', 'render_widget' ], 10, 2 );
	add_action( 'wp_ajax_wtv_get_order_invoice_pdf', [
		'includes\views\WTV_Visma_Invoice_Widget',
		'wtv_get_order_invoice_pdf'
	] );
} );

/**
 * init
 */
add_action( 'init', function() {

	add_action( 'woocommerce_checkout_update_order_meta', [ 'includes\WTV_Plugin', 'set_sequential_order_number' ], 10, 2 );
	add_action( 'woocommerce_process_shop_order_meta', [ 'includes\WTV_Plugin', 'set_sequential_order_number' ], 10, 2 );
	add_action( 'woocommerce_before_resend_order_emails', [ 'includes\WTV_Plugin', 'set_sequential_order_number' ], 10, 2 );
	add_action( 'woocommerce_api_create_order', [ 'includes\WTV_Plugin', 'set_sequential_order_number' ], 10, 2 );
	add_action( 'woocommerce_deposits_create_order', [ 'includes\WTV_Plugin', 'set_sequential_order_number' ], 10, 2 );

	// Get sequential order number
	add_filter( 'woocommerce_order_number', [ 'includes\WTV_Plugin', 'get_sequential_order_number' ], 10, 2 );

	if ( get_option('show_organization_number_field_in_billing_address_form') ) {
		add_filter( 'woocommerce_checkout_fields', ['includes\WTV_Plugin', 'show_organization_number_form_field'], 10, 1);
		add_action( 'woocommerce_process_shop_order_meta', [
			'includes\WTV_Plugin',
			'save_billing_company_number'
		], 10, 1 );
		add_action( 'woocommerce_admin_order_data_after_billing_address', ['includes\WTV_Plugin', 'custom_checkout_field_display_admin_order_meta'], 10, 1 );
	}

} );



/**
 * admin_init
 */
add_action( 'admin_init', function() {
	// Add settings
    WTV_Plugin::load_text_domain();
	WTV_Plugin::add_settings();
    WTV_Migrate::maybe_update_db();
    add_action( 'admin_notices', [ 'includes\WTV_Plugin', 'check_visma_settings'] );
    add_action( 'admin_notices', [ 'includes\WTV_Plugin', 'check_plugin_configuration'] );
    add_action( 'admin_notices', [ 'includes\WTV_Plugin', 'check_visma_needs_login'] );
    WTV_Product_Fields::init();

	// Add admin scripts
	add_action( 'admin_enqueue_scripts', [ 'includes\WTV_Plugin', 'add_admin_scripts' ] );

	// Add Visma column to Orders table
	add_filter( 'manage_edit-shop_order_columns', [ 'includes\WTV_Plugin', 'add_orders_table_columns' ] );
    add_filter( 'woocommerce_shop_order_list_table_columns', [  'includes\WTV_Plugin', 'add_orders_table_columns' ] );

	// Get Fornox column content to Orders table
	add_action( 'manage_shop_order_posts_custom_column', [ 'includes\WTV_Plugin', 'print_orders_table_column_content' ], 10, 2 );
    add_action( 'woocommerce_shop_order_list_table_custom_column', [ 'includes\WTV_Plugin', 'print_orders_table_column_content' ], 10, 2 );

	// Add Visma column to product table
	add_filter( 'manage_edit-product_columns', [ 'includes\WTV_Plugin', 'add_products_table_columns' ] );

	// Get Visma column content to Products table
	add_action( 'manage_product_posts_custom_column', [ 'includes\WTV_Plugin', 'print_products_table_column_content' ], 10, 2 );

	# Sync of the product with Visma
	add_action( 'save_post', [ 'includes\WTV_Plugin', 'sync_changes_to_visma' ] );

    add_action( 'upgrader_process_complete', [ 'includes\WTV_Migrate', 'wp_update_completed' ], 10, 2 );
} );

/**
 * Add settings page
 */
add_action( 'admin_menu', function() {
	WTV_Plugin::add_settings_page();
} );

/**
 * Update settings thorugh AJAX
 */
add_action( 'wp_ajax_visma_update_setting', function() {
	WTV_Ajax::update_setting();
} );

/**
 * Visma bulk actions
 */
add_action( 'wp_ajax_visma_admin_action', function() {
	WTV_Ajax::admin_action();
} );

/**
 * Check Visma API key thorugh AJAX
 */
add_action( 'wp_ajax_check_wetail_visma_license_key', function() {
	WTV_Ajax::check_wetail_visma_license_key();
} );

function wtv_init_routes( ) {
    WTV_Routes::register_routes();
}

add_filter( 'rest_api_init' , 'wtv_init_routes' );
