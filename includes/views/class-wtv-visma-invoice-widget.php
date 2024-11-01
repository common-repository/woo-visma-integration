<?php

namespace includes\views;

use includes\api\WTV_Invoices;
use includes\WTV_Plugin;

/**
 * @description "With invoice" widget render on single order view to show pdf button
 * @wrike https://www.wrike.com/open.htm?id=1257978584
 * @since 2.3.0
 */
class WTV_Visma_Invoice_Widget {


	/**
	 * Nonce name for PDF links
	 */
	const nonce_name = 'visma_get_invoice_pdf_nonce';


	/**
	 * Handles wp-load.php request to provide PDF
	 */
	public static function maybe_handle_pdf_file_request() {
		if ( isset( $_SERVER['SCRIPT_FILENAME'] ) && basename( $_SERVER['SCRIPT_FILENAME'] ) == 'wp-load.php' ) {
			if ( isset( $_GET['order_id'], $_GET['visma_get_invoice_pdf_nonce'], $_GET['action'], $_GET['in'] ) ) {
				if ( 'visma_get_invoice_pdf' === $_GET['action'] ) {
					if (
						wp_verify_nonce( $_GET['visma_get_invoice_pdf_nonce'], self::nonce_name ) &&
						current_user_can( 'manage_woocommerce' ) &&
						md5( $_GET['visma_get_invoice_pdf_nonce'] . wp_get_current_user()->user_email ) === $_GET['in'] &&
						get_transient( self::nonce_name ) === $_GET['in']
					) {
						try {
							delete_transient( self::nonce_name );
							$pdf_url = WTV_Invoices::get_invoice_PDF_url( intval( $_GET['order_id'] ) );
							if ( $pdf_url ) {
								$pdf_contents = file_get_contents( $pdf_url );
								if ( $pdf_contents ) {
									header( 'Content-Type: application/pdf' );
									header( 'Content-Disposition: inline; filename="visma-invoice-for-order-' . intval( $_GET['order_id'] ) . '.pdf"' ); // You can change the filename if needed
									header( "Cache-Control: no-store, no-cache, must-revalidate, max-age=0" );
									header( "Cache-Control: post-check=0, pre-check=0", false );
									header( "Pragma: no-cache" );
									echo $pdf_contents;
									exit( 0 );
								} else {
									status_header( 500 );
									echo __( 'Failed to download PDF', WTV_Plugin::TEXTDOMAIN );
								}
							} else {
								status_header( 400 );
								echo __( 'No invoice found for requested order', WTV_Plugin::TEXTDOMAIN );
							}
						} catch ( \Throwable $t ) {
							echo __( 'Error occurred', WTV_Plugin::TEXTDOMAIN );

						}
					} else {
						wp_die( __( 'Link expired', WTV_Plugin::TEXTDOMAIN ) );
					}
				}
				exit( 0 );
			}
		}
	}

	/**
	 * Enqueues meta box
	 *
	 * @param $post_type string
	 * @param $post \WP_Post
	 */
	public static function render_widget( $post_type, $post ) {
		$order = wc_get_order( $post->ID );

		if ( $order && WTV_Invoices::get_visma_invoice_id( $order ) ) {
			add_meta_box(
				'visma_invoice_box',
				__( 'Visma', WTV_Plugin::TEXTDOMAIN ),
				[
					__CLASS__,
					'wtv_widget_order_meta_box'
				],
				get_current_screen()->base === 'woocommerce_page_wc-orders' ? 'woocommerce_page_wc-orders' : 'shop_order',
				'side',
				'high'
			);
		}
	}

	/**
	 * Renders metabox html
	 */
	public static function wtv_widget_order_meta_box( $post ) {
		$button = get_submit_button( __( 'Se faktura', WTV_Plugin::TEXTDOMAIN ), 'action', '', false, [
			'id' => 'printSingleOrderInvoicePdf'
		] );

		$spinner = '<span class="spin wtv-spinner"></span>';

		?>
        <div class="invoice-print-pdf">
			<?= $button ?>
			<?= $spinner ?>
        </div>
		<?php
	}

	/**
	 * Handles AJAX request
	 */
	public static function wtv_get_order_invoice_pdf() {
		if ( ! isset( $_POST['action'] ) ) {
			wp_send_json( array( 'message' => __( 'No action provided', WTV_Plugin::TEXTDOMAIN ) ), 500 );
		}
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json( array( 'message' => __( 'Insufficient permissions', WTV_Plugin::TEXTDOMAIN ) ), 401 );
		}

		$pid = $_POST['pid'] ?? false;

		if ( ! is_numeric( $pid ) ) {
			wp_send_json( array( 'message' => __( 'Invalid order id', WTV_Plugin::TEXTDOMAIN ) ), 400 );
		}

		$pid = intval( $pid );

		$order = wc_get_order( $pid );
		if ( ! $order ) {
			wp_send_json( array( 'message' => __( 'No such order', WTV_Plugin::TEXTDOMAIN ) ), 404 );
		}

		$visma_id = WTV_Invoices::get_visma_invoice_id( $order );
		if ( ! $visma_id ) {
			wp_send_json( array( 'message' => __( 'No associated Visma order', WTV_Plugin::TEXTDOMAIN ) ), 404 );
		}
		$nonce          = wp_create_nonce( self::nonce_name );
		$internal_nonce = md5( $nonce . wp_get_current_user()->user_email );
		set_transient( self::nonce_name, $internal_nonce, 180 );
		wp_send_json( array(
			'url' => home_url(
				'wp-load.php?order_id=' . $pid .
				'&action=visma_get_invoice_pdf&visma_get_invoice_pdf_nonce=' . $nonce .
				'&t=' . time() .
				'&in=' . $internal_nonce )
		), 200 );
	}
}