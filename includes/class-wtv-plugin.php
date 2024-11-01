<?php

namespace includes;

use includes\views\WTV_General_Settings_View;
use includes\views\WTV_Accounting_Settings_View;
use includes\views\WTV_Sync_Settings_View;
use includes\utils\WTV_Utils;
use includes\views\WTV_Product_Settings_View;
use includes\views\WTV_Bulk_Settings_View;
use includes\wetail\admin\WTV_Settings;
use includes\api\WTV_Orders;
use includes\api\WTV_Products;
use Exception;

class WTV_Plugin {

	const TEXTDOMAIN = 'woo-visma-integration';
	const CLIENT_SECRET = '26D5MkGKOXDcffWvpFxErqbo9sFrDdJbHhmoYCNQIwrjwSmonQaW2ZlXLiwWIcK';
	const CLIENT_TEST_SECRET = '0a3yrdyeNvSXYNVH6qFIOyGz7ce9RA1p3HwILnCPEc';
	const CLIENT_ID = 'wetail';
    const SYNC_STATUS_ORDER_SYNCED = 1;
    const SYNC_STATUS_VOUCHER_SYNCED = 2;
    const INTERNAL_EXCEPTION = 9999;


    /**
     * Set sequential order
     *
     * @param $order_id
     * @param $post
     * @return bool
     */
	public static function set_sequential_order_number( $order_id, $post )
	{
		if( ! get_option( 'visma_order_number_prefix' ) )
			return false;

		if( is_array( $post ) || is_null( $post ) || ( 'shop_order' === $post->post_type && 'auto-draft' !== $post->post_status ) ) {
			$order_id = is_a( $order_id, 'WC_Order' ) ? $order_id->get_id() : $order_id;
			$order_number = WTV_Utils::get_order_meta_compat( $order_id, '_order_number' );

			update_post_meta( $order_id, '_order_number', get_option( 'visma_order_number_prefix' ) . $order_number );
		}
	}

    /**
     * Get sequential order number
     *
     * @param $order_number
     * @param $order
     * @return mixed|string
     */
	public static function get_sequential_order_number( $order_number, $order )
	{
		if( $order instanceof WC_Subscription )
			return $order_number;

		if( WTV_Utils::get_order_meta_compat( $order->get_id(), '_order_number_formatted' ) )
			return WTV_Utils::get_order_meta_compat( $order->get_id(), '_order_number_formatted' );

		if( ! get_option( 'visma_order_number_prefix' ) )
			return $order_number;

		return get_option( 'visma_order_number_prefix' ) . $order_number;
	}

    /**
     * Get plugin path
     *
     * @param string $path
     * @return string
     */
	public static function get_path( $path = '' )
	{
		return plugin_dir_path( dirname( __FILE__ ) ) . ltrim( $path, '/' );
	}

    /**
     * Get plugin URL
     *
     * @param string $path
     * @return string
     */
	public static function get_url( $path = '' ){
		return plugins_url( $path, dirname( __FILE__ ) );
	}

	/**
	 * Load textdomain
	 *
	 * @hook 'plugins_loaded'
	 */
	public static function load_text_domain(){
        $locale = ( is_admin()  && function_exists( 'get_user_locale' ) ? get_user_locale() : get_locale() );
        load_textdomain( WTV_Plugin::TEXTDOMAIN, WTV_Plugin::TEXTDOMAIN . '/languages/' . WTV_Plugin::TEXTDOMAIN . '-' . $locale . '.mo' );
		load_plugin_textdomain( WTV_Plugin::TEXTDOMAIN,false, WTV_Plugin::TEXTDOMAIN . '/languages');
	}


	/**
	 * Add settings
	 *
	 * @hook 'admin_init'
	 */
	public static function add_settings(){
        WTV_General_Settings_View::render_settings();
        WTV_Sync_Settings_View::render_settings();
		WTV_Accounting_Settings_View::render_settings();
        WTV_Product_Settings_View::render_settings();
		WTV_Bulk_Settings_View::render_settings();
	}

	/**
	 * Add settings page
	 *
	 * @hook 'admin_menu'
	 */
	public static function add_settings_page(){
		$page = WTV_Settings::addPage( [
			'slug'  => 'visma',
			'title' => __( 'Visma inställningar', WTV_Plugin::TEXTDOMAIN ),
			'menu'  => __( 'Visma', WTV_Plugin::TEXTDOMAIN )
		] );

        add_action( "admin_print_footer_scripts-". $page, [__CLASS__,'render_popup_for_visma_sync_orders_date_range'] );
    }

    public static function render_popup_for_visma_sync_orders_date_range(){?>
        <section id="visma-popups">
        <?php
            include WTV_PATH . "/assets/templates/admin/template-popup-visma-order-sync.php";
            include WTV_PATH . "/assets/templates/admin/template-popup-visma-product-sync.php";
        ?>
        </section><?php
	}

	/**
	 * Add admin scripts
	 */
	public static function add_admin_scripts(){
		wp_enqueue_script( 'mustache', self::get_url( 'assets/scripts/mustache.js' ) );
		wp_enqueue_script( 'thickbox' );
		wp_enqueue_style( 'thickbox' );
		wp_enqueue_script( 'visma', self::get_url( 'assets/scripts/admin.js' ), [ 'jquery', 'mustache', 'thickbox' ] );
		wp_enqueue_style( 'visma', self::get_url( 'assets/styles/admin.css' ) );
        wp_enqueue_script( 'jquery-ui-datepicker'  );
        wp_register_style( 'jquery-ui-style', WC()->plugin_url() . '/assets/css/jquery-ui/jquery-ui.min.css', array(), WC()->version );
        wp_enqueue_style( 'jquery-ui-style'  );
        wp_enqueue_style( 'woocommerce_admin_styles'  );
        wp_enqueue_style( 'font-awesome', self::get_url( 'assets/font-awesome/css/font-awesome.min.css' ) );
        wp_enqueue_style( 'font-awesome', self::get_url( 'assets/font-awesome/css/font-awesome.min.css' ) );
		wp_enqueue_script( 'jquery-tiptip' );

        wp_localize_script('visma', 'visma_scripts', array(
                'i18n' => array(
                        'with_invoice_popup_title' => __( 'Faktura', WTV_Plugin::TEXTDOMAIN ),
                        'with_invoice_popup_failed_message' => __('Invoice issue', self::TEXTDOMAIN),
                )
        ) );
	}

    /**
     * An array helper
     *
     * @param $array
     * @param $insert
     * @param $at
     * @return array
     */
	public static function array_insert( $array, $insert, $at ){
		$insert = ( array ) $insert;
		$left = array_slice( $array, 0, $at );
		$right = array_slice( $array, $at, count( $array ) );

		return $left + $insert + $right;
	}


    /**
     * Add orders table columns
     *
     * @param $columns
     * @return array
     */
	public static function add_orders_table_columns( $columns = [] ){
		$columns['visma'] = 'Visma';
		return $columns;
	}

    /**
     * Adds message in Admin if plugin config seems wrong
     *
     */
    public static function check_plugin_configuration() {
        if( ! WTV_Settings_Validator::all_settings_are_valid() ){
            $class = 'notice notice-error';
            $message = __( 'Visma: Det saknas inställningar för att kopplingen ska fungera korrekt. ',  WTV_Plugin::TEXTDOMAIN  );
            $link = admin_url() . 'options-general.php?page=visma';
            $text = __( 'Gå till inställningar',  WTV_Plugin::TEXTDOMAIN  );
            printf( '<div class="%1$s"><p>%2$s<a href="%3$s">%4$s</a></p></div>', esc_attr( $class ), esc_html( $message ), esc_html( $link ), esc_html( $text ) );
        }
    }

    /**
     * Adds message in Admin if settings not fetched from Visma
     *
     */
    public static function check_visma_settings() {
        if( ! WTV_Settings_Validator::validate_settings() ){
            $class = 'notice notice-error';
            $message = __( 'Det saknas inställningar som måste hämtas från Visma klicka på "Update Settings". ',  WTV_Plugin::TEXTDOMAIN  );
            $link = admin_url() . 'options-general.php?page=visma';
            $text = __( 'Gå till inställningar',  WTV_Plugin::TEXTDOMAIN  );
            printf( '<div class="%1$s"><p>%2$s<a href="%3$s">%4$s</a></p></div>', esc_attr( $class ), esc_html( $message ), esc_html( $link ), esc_html( $text ) );
        }
    }

    /**
     * Adds message in Admin if is logged out from Visma
     *
     */
    public static function check_visma_needs_login() {
        if( get_option( 'visma_needs_login' ) ){
            $class = 'notice notice-error';
            $message = __( 'Sessionen har gått ut, logga in i Visma igen. ',  WTV_Plugin::TEXTDOMAIN  );
            $link = admin_url() . 'options-general.php?page=visma';
            $text = __( 'Gå till inställningar',  WTV_Plugin::TEXTDOMAIN  );
            printf( '<div class="%1$s"><p>%2$s<a href="%3$s">%4$s</a></p></div>', esc_attr( $class ), esc_html( $message ), esc_html( $link ), esc_html( $text ) );
        }

    }

	/**
	 * Print orders table column content
	 *
	 * @param $column_name
	 * @param $post_id
	 */
	public static function print_orders_table_column_content( $column_name, $post_id ){
		if( 'visma' != $column_name )
			return;

        $order_id = false;
        if ( is_int( $post_id ) ) {
            $order_id = $post_id;
        }
        elseif ( 'Automattic\WooCommerce\Admin\Overrides\Order' === get_class( $post_id ) )  {
            $order_id = $post_id->get_id();
        }

		$nonce = wp_create_nonce( 'visma_woocommerce' );

		print '<a href="#" class="button wetail-button wetail-icon-repeat syncOrderToVisma" data-order-id="' . $order_id . '" data-nonce="' . $nonce . '" title="Sync order to Visma"></a> ';

		$synced = WTV_Orders::is_synced( $order_id );

		print '<span class="wetail-visma-status ' . ( 1 == $synced ? 'wetail-icon-check' : 'wetail-icon-cross' ) . '" title="' . ( 1 == $synced ? __( "Ordern har synkroniserats", WTV_Plugin::TEXTDOMAIN ) : __( "Ordern har inte synkroniserats", WTV_Plugin::TEXTDOMAIN ) ) . '"></span>';
		print '<span class="spinner visma-spinner"></span>';
	}

    /**
     * Add products table columns
     *
     * @param array $columns
     * @return array $columns
     */
	public static function add_products_table_columns( $columns = [] ){
		$columns['visma'] = 'Visma';
		return $columns;
	}

	/**
     *
     */
    public static function save_billing_company_number( $order_id ){
        if ( isset( $_POST['_billing_company_number'] ) ) {
            $billing_company_number = sanitize_text_field( $_POST['_billing_company_number'] );
            $wc_order = wc_get_order( $order_id );
            $wc_order->update_meta_data( '_billing_company_number', $billing_company_number );
            $wc_order->save();
        }
    }

	/**
	 * Print products table column content
	 *
	 * @param $columnName
	 * @param $postId
	 */
	public static function print_products_table_column_content( $columnName, $postId ){
		if( 'visma' != $columnName )
			return;

		$nonce = wp_create_nonce( 'visma_woocommerce' );

		print '<a href="#" class="button wetail-button wetail-icon-repeat syncProductToVisma" data-product-id="' . $postId . '" data-nonce="' . $nonce . '" title="Sync product to Visma"></a> ';

		$synced = WTV_Products::is_synced( $postId );

		print '<span class="wetail-visma-status ' . ( 1 == $synced ? 'wetail-icon-check' : 'wetail-icon-cross' ) . '" title="' . ( 1 == $synced ? __( "Produkten har synkroniserats", WTV_Plugin::TEXTDOMAIN ) : __( "Produkten har synkroniserats", WTV_Plugin::TEXTDOMAIN ) ) . '"></span>';
		print '<span class="spinner visma-spinner"></span>';
	}


    /**
     * Sync changes to Visma
     * @param int $post_id
     */
	public static function sync_changes_to_visma( $post_id ){

		if ( isset( $_POST['post_type'] ) ) {

			if( get_post_type( $post_id ) ==  'product' && ! empty( $_POST['visma_sync_product'] ) ) {

				# Check whether the post is revision
				if( get_post_status( $post_id ) !== 'publish') return;

				try {
					WTV_Products::sync( $_POST['ID'],  $return_response = false, $sync_stock=true );
				}
				catch( Exception $error ) {
					// Silently fail
				}

			# Sync Order
			}else if ( get_post_type( $post_id ) ==  'shop_order' && ! empty( $_POST['visma_sync_order'] ) ) {

				# Check whether the post is auto-draft
				if( get_post_status( $post_id ) == 'auto-draft' ) return;

				try {
					WTV_Orders::sync( $_POST['ID'] );
				}
				catch( Exception $error ) {
					// Silently fail
				}
			}
		}
	}

    /**
     * Displays org number in checkout
     * @param array $address_fields
     * @return array
     */
	public static function show_organization_number_form_field( $address_fields ) {
		$billing = $address_fields['billing'];
		$res = array_slice( $billing, 0, 3, true ) +
		array( 'billing_company_number' => array(
				'label'        => __( 'Organisationsnummer', WTV_Plugin::TEXTDOMAIN ),
				'class'        => array( 'form-row-wide' ),
                'required'   => get_option( 'make_organization_number_field_required' ) == 'yes' ? true : false,
				'priority'     => 31
		)) +
		array_slice( $billing, 3, count( $billing ) - 3, true );
		$address_fields['billing'] = $res;

		return $address_fields;
	}


    /**
     * @param \WC_Order $order
     */
	public static function custom_checkout_field_display_admin_order_meta( $order ){
        woocommerce_form_field( '_billing_company_number', array(

            'type' => 'text',
            'label'      => __('Organisationsnummer', WTV_Plugin::TEXTDOMAIN),
            'placeholder'   =>__('Organisationsnummer', WTV_Plugin::TEXTDOMAIN),
            'required'   => true,
            'clear'     => true,
        ), WTV_Utils::get_order_meta_compat( $order->get_id(), '_billing_company_number' ) );
	}

    /**
     * Get translated strings (i18n)
     */
    public static function get_translated_strings(){
        $strings = [
            'Save changes' => __( "Spara", WTV_Plugin::TEXTDOMAIN ),
        ];

        return $strings;
    }

}
