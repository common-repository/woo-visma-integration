<?php

namespace includes\views;

use includes\wetail\admin\WTV_Settings;
use includes\WTV_Plugin;


class WTV_Bulk_Settings_View {
	/**
	 * Adds all required setting fields for Bulk Settings View
	 */
	public static function render_settings() {
		$page = 'visma';

		WTV_Settings::add_tab( [
			'page' => $page,
			'name' => 'bulk-actions',
			'title' => __( "Massändringar", WTV_Plugin::TEXTDOMAIN ),
			'saveButton' => null,
		] );

		WTV_Settings::add_section( [
			'page' => $page,
			'tab' => 'bulk-actions',
			'name' => "bulk-actions",
			'title' => __( "Massändringar", WTV_Plugin::TEXTDOMAIN ),
			'description' => __( "Användbara masshanteringalternativ som kan användas retroaktivt mellan WooCommerce och Visma.", WTV_Plugin::TEXTDOMAIN )
		] );

		WTV_Settings::add_field( [
			'page' => $page,
			'tab' => 'bulk-actions',
			'title' => __( 'Synkronisera produkter till Visma', WTV_Plugin::TEXTDOMAIN ),
			'type' => 'button',
            'button' => [
                'text' => __( "Synkronisera produkter", WTV_Plugin::TEXTDOMAIN ),
            ],
            'data' => [
                [
                    'key' => "visma-admin-action",
                    'value' => "visma_sync_products"
                ],
                [
                    'key' => "modal",
                    'value' => true
                ]
            ],
            'description' => __( "Synkronisera produkter till Visma", WTV_Plugin::TEXTDOMAIN )
        ] );

        WTV_Settings::add_field( [
            'page' => $page,
            'tab' => "bulk-actions",
            'section' => "bulk-actions",
            'title' => __( "Synkronisera ordrar till Visma", WTV_Plugin::TEXTDOMAIN ),
            'type' => "button",
            'button' => [
                'text' => __( "Synkronisera ordrar", WTV_Plugin::TEXTDOMAIN ),
            ],
            'data' => [
                [
                    'key' => "visma-admin-action",
                    'value' => "visma_sync_orders_date_range"
                ],
                [
                    'key' => "modal",
                    'value' => true
                ]
            ],
            'description' => __( 'Synkronisera ordrar i datumintervall till Visma.', WTV_Plugin::TEXTDOMAIN )
        ] );
	}
}
