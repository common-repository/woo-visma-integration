<?php

namespace includes\views;

use includes\wetail\admin\WTV_Settings;
use includes\WTV_Plugin;
use includes\WTV_Settings_Validator;


class WTV_Product_Settings_View{

    public static function render_settings(){

        $page = 'visma';

        WTV_Settings::add_tab( [
            'page' => $page,
            'name' => 'products',
            'title' => __( 'Produktinställningar', WTV_Plugin::TEXTDOMAIN ),
        ], true );

        WTV_Settings::add_section( [
            'page' => $page,
            'tab' => 'products',
            'name' => 'preferences',
            'title' => __( 'Allmänt', WTV_Plugin::TEXTDOMAIN ),
        ] );

        WTV_Settings::add_field( [
            'page' => $page,
            'tab' => 'products',
            'section' => 'preferences',
            'type' => 'checkboxes',
            'options' => [
	            [
		            'name' => 'visma_auto_sync_products',
		            'label' => __( 'Synkronisera produkter vid uppdatering', WTV_Plugin::TEXTDOMAIN ),
		            'tooltip' => __( 'När helst en produkt ändras i WooCommerce produktvy kommer ändringen att reflekteras i Visma.', WTV_Plugin::TEXTDOMAIN ),
	            ],
	            [
		            'name' => 'visma_sync_master_product',
		            'label' => __( 'Synkronisera även förälderprodukten för variationer', WTV_Plugin::TEXTDOMAIN ),
		            'tooltip' => __( 'När detta alternativ är valt kommer även förälderprodukten till en variation att synkroniseras, även om den inte finns i Visma från början.', WTV_Plugin::TEXTDOMAIN ),
	            ],
                [
                    'name' => 'visma_sync_existing_product',
                    'label' => __( 'Uppdatera exist. produkt i Visma vid ordersynkronisering', WTV_Plugin::TEXTDOMAIN ),
                    'tooltip' => '',
                ],
	            [
		            'name' => 'visma_skip_product_variations',
		            'label' => __( 'Synkronisera ej variationer, bara förälderprodukten', WTV_Plugin::TEXTDOMAIN ),
		            'tooltip' => __( 'När detta alternativ är valt kommer enbart förälderrodukten att synkroniseras.', WTV_Plugin::TEXTDOMAIN ),
	            ],
                [
                    'name' => 'visma_do_not_sync_price',
                    'label' => __( 'Uppdatera ej pris vid synk', WTV_Plugin::TEXTDOMAIN ),
                    'tooltip' => __( 'Normalt sett kommer en prisuppdatering i WooCommerce att uppdatera priset även i Visma. Denna inställning kommer att ignorera prisändringar i WooCommerce (ordrar där produktpriset skiljer sig åt kommer dock att använda sig av priset från WooCommerce).', WTV_Plugin::TEXTDOMAIN ),
                ],
                [
                    'name' => 'visma_auto_generate_sku',
                    'label' => __( 'Skapa ett SKU automatiskt för produkter som saknar SKU', WTV_Plugin::TEXTDOMAIN ),
                    'tooltip' => __( 'Artiklar i Visma kräver artikelnummer. Om en order i WooCommerce innehåller produkter utan artikelnummer kommer det här alternativet att se till att ett artikelnummer genereras baserat på deras titel.', WTV_Plugin::TEXTDOMAIN ),
                ],
            ]
        ] );
    }
}
