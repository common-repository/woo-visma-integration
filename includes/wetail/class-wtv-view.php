<?php
namespace includes\wetail;

use Mustache_Autoloader;
use Mustache_Engine;
use Mustache_Loader_FilesystemLoader;

class WTV_View
{
	/**
	 * Get Mustache instance (singleton)
	 */
	public static function get_mustache()
	{
		static $mustache = null;

		if( empty( $mustache ) ) {
			$pluginDir = plugin_dir_path( dirname( dirname( __FILE__ ) ) );
			$templateDir = "{$pluginDir}assets/templates";
			//$partialsDir = "{$templateDir}/partials";

			if( ! class_exists( 'Mustache_Autoloader' ) ) {
				require_once "{$pluginDir}vendor/mustache-php/src/Mustache/Autoloader.php";

				Mustache_Autoloader::register();
			}

			$mustache = new Mustache_Engine( [
				'loader' => new Mustache_Loader_FilesystemLoader( $templateDir, [
					'extension' => "ms"
				] ),
				/*'partials_loader' => new Mustache_Loader_FilesystemLoader( $partialsDir, [
					'extension' => "ms"
				] ),*/
				'cache' => WP_CONTENT_DIR . '/cache/mustache',
				'helpers' => self::get_mustache_helpers()
			] );

			$mustache->addHelper('wc_help_tip', array(
				'wc_help_tip' => function() {
					ob_start();
					return ob_get_clean();
				},
			));

			function wc_help_tip( $tip, $allow_html = false ) {
				if ( $allow_html ) {
					$tip = wc_sanitize_tooltip( $tip );
				} else {
					$tip = esc_attr( $tip );
				}

				return '<span class="woocommerce-help-tip" data-tip="' . $tip . '"></span>';
			}
		}

		return $mustache;
	}

	/**
	 * Get Mustache helpers
	 */
	public static function get_mustache_helpers()
	{
		$helpers = [];

		$helpers['i18n'] = function( $text, $helper ) {
			return $helper->render( __( $text ) );
		};

		$helpers['formatTooltip'] = function( $tip, $helper ) {
			return $helper->render( '<span class="woocommerce-help-tip" data-tip="' . esc_attr( $tip ) . '"></span>' );
		};

		$helpers['formatHtmlTooltip'] = function( $tip, $helper ) {
			return $helper->render( '<span class="woocommerce-help-tip" data-tip="' . wc_sanitize_tooltip( $tip ) . '"></span>' );
		};

		return $helpers;
	}

	/**
	 * Render view
	 *
	 * @param $template
	 * @param $data
	 */
	public static function render( $template, $data = [] )
	{
		$mustache = self::get_mustache();

		print $mustache->render( $template, $data );
	}
}
