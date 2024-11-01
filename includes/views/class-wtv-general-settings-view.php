<?php

namespace includes\views;

use includes\wetail\admin\WTV_Settings;
use includes\WTV_Plugin;
use includes\WTV_Settings_Validator;


class WTV_General_Settings_View{
    /**
     * Renders settings for general tab
     */
    public static function render_settings(){
        $page = 'visma';
        $mail_info = 'eAccountingAPI@visma.com?subject=Aktivering av WooCommerce-integration till Visma eEkonomi&cc=support@wetail.io&body=' . rawurlencode('Hej Visma!

Vår kund behöver hjälp med att aktivera sin shop, kan du hjälpa oss att lägga till:
' . get_rest_url() . 'visma/callback ?');

        WTV_Settings::add_tab( [
            'page' => $page,
            'name' => 'general' ,
            'title'=> __( 'Allmänt' , WTV_Plugin::TEXTDOMAIN )
        ], WTV_Settings_Validator::visma_settings_are_present() );

        WTV_Settings::add_section( [
            'page'          => $page,
            'tab'           => 'general' ,
            'name'          => 'license' ,
            'title'         => '',
            'description'   => ''
        ] );

        WTV_Settings::add_section( [
            'page'  => $page,
            'tab'   => 'general' ,
            'name'  => 'woo_api_url' ,
            'title' => __( 'Visma' , WTV_Plugin::TEXTDOMAIN ),
        ] );

        WTV_Settings::add_field( [
            'page'      => $page,
            'tab'       => 'general' ,
            'section'   => 'license' ,
            'name'      => 'wetail_visma_license_key' ,
            'title'     => __( 'Wetail licensnyckel' , WTV_Plugin::TEXTDOMAIN ),
            'description' => __( 'Din Wetail licensnyckel som du fått i bekräftelsemailet på din order.<br>Om du inte har tecknat ett abonnemang än, använd <a href="https://wetail.io/service/integrationer/woocommerce-visma/" target="_blank">denna länk</a>.' , WTV_Plugin::TEXTDOMAIN ),
            'after' =>
	            '<a href="#" class="button visma-check-connection">' . __( 'Spara och kontrollera' , WTV_Plugin::TEXTDOMAIN ) . '</a> ' .
	            '<span class="spinner visma-spinner"></span><span class="alert"></span>'
        ] );

        WTV_Settings::add_section( [
            'page'  => $page,
            'tab'   => 'general' ,
            'name'  => 'settings' ,
        ] );

        WTV_Settings::add_field( [
            'page'      => $page,
            'tab'       => 'general' ,
            'section'   => 'settings' ,
            'title'     => __( 'Koppla ihop med Visma' , WTV_Plugin::TEXTDOMAIN ),
            'type'      => 'button' ,
            'description' => sprintf( __( 'För att du ska kunna interagera med Visma måste du först registrera en så kallad "Callback URL". Detta gör du genom att skicka <a href="mailto:%s">detta mail</a> till dem. Vänligen invänta svar från dem (brukar ta max en arbetsdag) innan du försöker logga in.', WTV_Plugin::TEXTDOMAIN ), $mail_info ),
            'button'    => [
                'text'      => __( 'Logga in' , WTV_Plugin::TEXTDOMAIN ),
            ],
            'data'      => [
                [
                    'key'   => 'visma-admin-action',
                    'value' => 'visma_get_auth_url'
                ]
            ],
        ] );

        WTV_Settings::add_field( [
            'page' => $page,
            'tab' => 'general',
            'section' => 'settings',
            'title' => __( 'Hämta inställningar från Visma', WTV_Plugin::TEXTDOMAIN ),
            'type' => 'button',
            'button' => [
                'text' => __( 'Hämta' , WTV_Plugin::TEXTDOMAIN ),
            ],
            'data' => [
                [
                    'key' => 'visma-admin-action',
                    'value' => 'visma_update_settings'
                ]
            ],
            'description' => __( 'Varje inloggning till Visma är endast giltig en begränsad tid. Om du inte hämtar inställningarna innan dess måste du logga in igen.', WTV_Plugin::TEXTDOMAIN )
        ] );

	    // class-wf-products section
	    WTV_Settings::add_section([
		    'page' => $page,
		    'tab' => "general",
		    'name' => "debug",
	    ]);

	    WTV_Settings::add_field( [
		    'page'          => $page,
		    'tab'           => 'general',
		    'section'       => 'debug',
		    'type'          => 'checkboxes',
		    'title'         => __( 'Debuggning', WTV_Plugin::TEXTDOMAIN ),
		    'options'   => [
			    [
				    'name' => 'visma_debug_log',
				    'label' => __('Aktivera loggning', WTV_Plugin::TEXTDOMAIN ),
				    'description' => __( 'Ej nödvändigt loggande kan belasta dina systemresurser.', WTV_Plugin::TEXTDOMAIN ) . ' <span class="red warning">' . __( 'Stäng av när du inte debuggar!', WTV_Plugin::TEXTDOMAIN ) . '</span><br>' . __( 'Debug-loggen finns under <b>WooCommerce</b> -> <b>Status</b> -> <b>Loggar</b>', WTV_Plugin::TEXTDOMAIN )
			    ],
			    [
				    'name'  => 'visma_test',
				    'label' => __( 'Sandbox', WTV_Plugin::TEXTDOMAIN ),
				    'description' => __( 'Skicka data till din test-miljö istället för din riktiga bokföring (kräver att denna miljö är aktiverad hos Visma).', WTV_Plugin::TEXTDOMAIN )
			    ]
		    ]
	    ] );
    }
}
