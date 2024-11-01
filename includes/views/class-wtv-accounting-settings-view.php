<?php

namespace includes\views;

use includes\wetail\admin\WTV_Settings;
use includes\WTV_Plugin;
use includes\utils\WTV_Utils;
use includes\WTV_Settings_Validator;

class WTV_Accounting_Settings_View{


    /**
     * Adds all required setting fields for Accounting View
     */
    public static function render_settings()
    {
        $page = "visma";
	    $country_settings = WTV_Settings_Validator::get_country_settings();
	    $tax_settings = WTV_Settings_Validator::get_tax_settings();
	    $valid = 'create_orders' === get_option( 'visma_sync_order_method' ) || WTV_Settings_Validator::accounting_settings_are_valid();

        // Automation settings tab
        WTV_Settings::add_tab( [
            'page' => $page,
            'name' => "accounting",
            'title' => __( "Bokföring", WTV_Plugin::TEXTDOMAIN )
        ], $valid );

	    // Accounting section
	    WTV_Settings::add_section( [
		    'page' => $page,
		    'tab' => "accounting",
		    'name' => "accounting",
		    'title' => __( "Momskonton", WTV_Plugin::TEXTDOMAIN ),
		    'description' => __( 'Konton för olika momssatser.', WTV_Plugin::TEXTDOMAIN ),
	    ] );

	    WTV_Settings::add_field( [
		    'page' => $page,
		    'tab' => 'accounting',
		    'section' => 'accounting',
		    'name' => 'visma_domestic_vat_25_account',
		    'type' => 'number',
		    'min' => '1',
		    'max' => '9999',
		    'title' => __( 'Konto, utgående moms 25% Sverige', WTV_Plugin::TEXTDOMAIN ),
		    'tooltip' => __( 'Kontot för produkter med 25% moms i Sverige', WTV_Plugin::TEXTDOMAIN ),
	    ], WTV_Utils::setting_needed( 'visma_domestic_vat_25_account', $country_settings['sells_to_se'] && $tax_settings['25'] ) );

	    WTV_Settings::add_field( [
		    'page' => $page,
		    'tab' => 'accounting',
		    'section' => 'accounting',
		    'name' => 'visma_domestic_vat_12_account',
		    'type' => 'number',
		    'min' => '1',
		    'max' => '9999',
		    'title' => __( 'Konto, utgående moms 12% Sverige', WTV_Plugin::TEXTDOMAIN ),
		    'tooltip' => __( 'Kontot för produkter med 12% moms i Sverige', WTV_Plugin::TEXTDOMAIN ),
	    ], WTV_Utils::setting_needed( 'visma_domestic_vat_12_account', $country_settings['sells_to_se'] && $tax_settings['12'] ) );

	    WTV_Settings::add_field( [
		    'page' => $page,
		    'tab' => 'accounting',
		    'section' => 'accounting',
		    'name' => 'visma_domestic_vat_6_account',
		    'type' => 'number',
		    'min' => '1',
		    'max' => '9999',
		    'title' => __( 'Konto, utgående moms 6% Sverige', WTV_Plugin::TEXTDOMAIN ),
		    'tooltip' => __( 'Kontot för produkter med 6% moms i Sverige', WTV_Plugin::TEXTDOMAIN ),
	    ], WTV_Utils::setting_needed( 'visma_domestic_vat_6_account', $country_settings['sells_to_se'] && $tax_settings['6'] ) );

	    WTV_Settings::add_field( [
		    'page' => $page,
		    'tab' => 'accounting',
		    'section' => 'accounting',
		    'name' => 'visma_eu_vat_25_account',
		    'type' => 'number',
		    'min' => '1',
		    'max' => '9999',
		    'title' => __( 'Konto, utgående moms 25% EU', WTV_Plugin::TEXTDOMAIN ),
		    'tooltip' => __( 'Kontot för produkter med 25% moms i EU', WTV_Plugin::TEXTDOMAIN ),
	    ], WTV_Utils::setting_needed( 'visma_eu_vat_25_account', $country_settings['sells_to_eu'] && $tax_settings['25'] ) );

	    WTV_Settings::add_field( [
		    'page' => $page,
		    'tab' => 'accounting',
		    'section' => 'accounting',
		    'name' => 'visma_eu_vat_12_account',
		    'type' => 'number',
		    'min' => '1',
		    'max' => '9999',
		    'title' => __( 'Konto, utgående moms 12% EU', WTV_Plugin::TEXTDOMAIN ),
		    'tooltip' => __( 'Kontot för produkter med 12% moms i EU', WTV_Plugin::TEXTDOMAIN ),
	    ], WTV_Utils::setting_needed( 'visma_eu_vat_12_account', $country_settings['sells_to_eu'] && $tax_settings['12'] ) );

	    WTV_Settings::add_field( [
		    'page' => $page,
		    'tab' => 'accounting',
		    'section' => 'accounting',
		    'name' => 'visma_eu_vat_6_account',
		    'type' => 'number',
		    'min' => '1',
		    'max' => '9999',
		    'title' => __( 'Konto, utgående moms 6% EU', WTV_Plugin::TEXTDOMAIN ),
		    'tooltip' => __( 'Kontot för produkter med 6% moms i EU', WTV_Plugin::TEXTDOMAIN ),
	    ], WTV_Utils::setting_needed( 'visma_eu_vat_6_account', $country_settings['sells_to_eu'] && $tax_settings['6'] ) );

		if ( get_option( 'visma_sync_order_method' ) == 'create_vouchers') {
			// Shipping section
			WTV_Settings::add_section( [
				'page' => $page,
				'tab' => 'accounting',
				'name' => 'shipping_accounts',
				'title' => __( 'Fraktkonton', WTV_Plugin::TEXTDOMAIN ),
				'description' => __( 'Konton för olika fraktkonton.', WTV_Plugin::TEXTDOMAIN )
			] );

			WTV_Settings::add_field( [
				'page' => $page,
				'tab' => 'accounting',
				'section' => 'shipping_accounts',
				'name' => 'visma_shipping_account_se',
				'type' => 'number',
				'min' => '1',
				'max' => '9999',
				'title' => __( 'Konto för frakt inom Sverige', WTV_Plugin::TEXTDOMAIN ),
				'description' => $country_settings['sells_to_se'] ? self::get_description( 'visma_shipping_account_se' ) : '',
			], WTV_Utils::setting_needed( 'visma_shipping_account_se', $country_settings['sells_to_se'] ) );

			WTV_Settings::add_field( [
				'page' => $page,
				'tab' => 'accounting',
				'section' => 'shipping_accounts',
				'name' => 'visma_shipping_account_eu',
				'type' => 'number',
				'min' => '1',
				'max' => '9999',
				'title' => __( 'Konto för frakt, EU', WTV_Plugin::TEXTDOMAIN ),
				'description' => $country_settings['sells_to_eu'] ? self::get_description( 'visma_shipping_account_eu' ) : '',
			], WTV_Utils::setting_needed( 'visma_shipping_account_eu', $country_settings['sells_to_eu'] ) );

			WTV_Settings::add_field( [
				'page' => $page,
				'tab' => 'accounting',
				'section' => 'shipping_accounts',
				'name' => 'visma_shipping_account_world',
				'type' => 'number',
				'min' => '1',
				'max' => '9999',
				'title' => __( 'Konto för frakt, världen', WTV_Plugin::TEXTDOMAIN ),
				'description' => $country_settings['sells_to_world'] ? self::get_description( 'visma_shipping_account_world' ) : '',
			], WTV_Utils::setting_needed( 'visma_shipping_account_world', $country_settings['sells_to_world'] ) );
		}
    }

	/**
	 * @param $option
	 * @return string
	 */
	public static function get_description( $option ){
		$descriptions = array(
			'visma_shipping_account_se'     => __( 'I dina inställningar ser vi att du levererar till Sverige, detta bokföringskonto behövs då. Vanligt fraktkonto är 3520.', WTV_Plugin::TEXTDOMAIN ),
			'visma_shipping_account_eu'     => __( 'I dina inställningar ser vi att du levererar till EU, detta bokföringskonto behövs då. Vanligt fraktkonto är 3521.', WTV_Plugin::TEXTDOMAIN ),
			'visma_shipping_account_world'  => __( 'I dina inställningar ser vi att du levererar till resten av världen, detta bokföringskonto behövs då.', WTV_Plugin::TEXTDOMAIN ),
		);

		return $descriptions[$option];

	}
}
