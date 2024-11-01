<?php


namespace includes\views;

use includes\utils\WTV_Utils;
use includes\wetail\admin\WTV_Settings;
use includes\WTV_Plugin;
use includes\WTV_Settings_Validator;

class WTV_Sync_Settings_View{

    const VOUCHER_SERIES_CHOICES = [
        [
            'id' => 1000,
            'name' => 'Bokför ej',
        ],
        [
            'id' => 1,
            'name' => 'A',
        ],
        [
            'id' => 2,
            'name' => 'B',
        ],
        [
            'id' => 3,
            'name' => 'C',
        ],
        [
            'id' => 4,
            'name' => 'D',
        ],
        [
            'id' => 5,
            'name' => 'E',
        ],
        [
            'id' => 6,
            'name' => 'F',
        ],
        [
            'id' => 7,
            'name' => 'G',
        ],
        [
            'id' => 8,
            'name' => 'H',
        ],
        [
            'id' => 9,
            'name' => 'I',
        ],
        [
            'id' => 10,
            'name' => 'J',
        ],
        [
            'id' => 11,
            'name' => 'K',
        ],
        [
            'id' => 12,
            'name' => 'L',
        ],
        [
            'id' => 13,
            'name' => 'M',
        ],
        [
            'id' => 14,
            'name' => 'N',
        ],
        [
            'id' => 15,
            'name' => 'O',
        ],
        [
            'id' => 16,
            'name' => 'P',
        ],
        [
            'id' => 17,
            'name' => 'Q',
        ],
        [
            'id' => 18,
            'name' => 'R',
        ],
        [
            'id' => 19,
            'name' => 'S',
        ],
        [
            'id' => 20,
            'name' => 'T',
        ],
        [
            'id' => 21,
            'name' => 'U',
        ],
        [
            'id' => 22,
            'name' => 'V',
        ],
    ];

    private static function visma_order_statuses(){
        return [
            [
                'id' => 1,
                'name' => __( 'Utkast', WTV_Plugin::TEXTDOMAIN ),
            ],
            [
                'id' => 2,
                'name' => __( 'Pågående', WTV_Plugin::TEXTDOMAIN ),
            ],
            [
                'id' => 3,
                'name' => __( 'Skickad', WTV_Plugin::TEXTDOMAIN ),
            ],
        ];
    }

    private static function visma_yes_no(){
        return [
            [
                'id' => 1,
                'name' => __( 'Ja', WTV_Plugin::TEXTDOMAIN ),
            ],
            [
                'id' => 2,
                'name' => __( 'Nej', WTV_Plugin::TEXTDOMAIN ),
            ]
        ];
    }

    /**
     * Renders settings for order tab
     */
    public static function render_settings(){

        $valid = 'create_vouchers' === get_option( 'visma_sync_order_method' ) || WTV_Settings_Validator::order_sync_settings_are_valid();
        $page = 'visma';

        WTV_Settings::add_tab( [
            'page' => $page,
            'name' => 'sync',
            'title' => __( 'Synkronisering', WTV_Plugin::TEXTDOMAIN )
        ], $valid );

        WTV_Settings::add_section( [
            'page' => $page,
            'tab' => 'sync',
            'name' => 'sync_order',
            'title' => __( 'Synkroniseringsflöde', WTV_Plugin::TEXTDOMAIN ),
        ] );

        WTV_Settings::add_field( [
            'page'          => $page,
            'tab'           => 'sync',
            'section'       => 'sync_order',
            'type'          => 'radio',
            'name'          => 'visma_sync_order_method',
            'description' => __( 'Här väljer du om du vill skapa bokföringsverifikationer eller ordrar i Visma', WTV_Plugin::TEXTDOMAIN ),
            'default' => 'create_vouchers',
            'options'   => [
                [
	                'label' => __( 'Skapa bokföringsverifikationer i Visma', WTV_Plugin::TEXTDOMAIN ),
                	'value' => 'create_vouchers',
                ],
	            [
		            'label' => __( 'Skapar ordrar i Visma', WTV_Plugin::TEXTDOMAIN ),
		            'value' => 'create_orders',
	            ]
            ]
        ] );

        WTV_Settings::add_section( [
            'page' => $page,
            'tab' => 'sync',
            'name' => 'payments',
            'title' => __( 'Betalsätt', WTV_Plugin::TEXTDOMAIN ),
	        'class' => 'test',
        ] );

	    $enabled_payment_gateways = WTV_Utils::get_enabled_payment_gateways();

        foreach( $enabled_payment_gateways as $payment_method) {

            WTV_Settings::add_field( [
                'page'          => $page,
                'tab'           => 'sync',
                'section'       => 'payments',
                'title'         => $payment_method->title,
                'type'          => 'table',
                'name'          => 'visma_order_sync_settings_' . $payment_method->id,
                'description'   => '',
                'table'     => [
                    'table'     => [
                        'id'        => 'visma-order-sync-settings',
                        'columns'   => [
                            [
                                'column' => [
                                    'name' => 'sync_on_status',
                                    'title' => __( 'Synkronisera på status', WTV_Plugin::TEXTDOMAIN ),
                                    'tooltip' => __( 'Välj en orderstatus som du vill ska initiera en synkning av ordern. Observera att detta inte berör ordrar som redan har denna status, dessa måste synkroniseras manuellt. <br><br>Om du inte väljer en status här kommer ingen automatisk synkronisering att ske utan enbart manull synkronisering är möjlig.', WTV_Plugin::TEXTDOMAIN ),
                                ]
                            ],
                            [
                                'column' => [
                                    'name' => 'payment_account',
                                    'title' => __( 'Bokföringskonto', WTV_Plugin::TEXTDOMAIN ),
                                    'tooltip' => __( 'Välj det bokföringskonto du vill att ordern ska bokföras mot när den markeras som betald.', WTV_Plugin::TEXTDOMAIN ),
                                ]
                            ],
                            [
                                'column' => [
                                    'name' => 'voucher_series',
                                    'title' => __( 'Verifikationsserie', WTV_Plugin::TEXTDOMAIN ),
                                    'tooltip' => __( 'Välj den serie som verifikatet ska bokföras i. Du kan även välja att inte bokföra verifikatet alls.', WTV_Plugin::TEXTDOMAIN ),
                                ]
                            ],
                            [
                                'column' => [
                                    'name' => 'terms_of_payment_id',
                                    'title' => __( 'Betalvillkor', WTV_Plugin::TEXTDOMAIN ),
                                    'tooltip' => __( 'Välj det betalvillkor som ska gälla för den utställda fakturan.', WTV_Plugin::TEXTDOMAIN ),
                                ]
                            ],
                            [
                                'column' => [
                                    'name' => 'order_status',
                                    'title' => __( 'Sätt till status i Visma', WTV_Plugin::TEXTDOMAIN ),
                                    'tooltip' => __( 'Välj den status som ordern ska sättas till i Visma.', WTV_Plugin::TEXTDOMAIN ),
                                ]
                            ],
                            [
                                'column' => [
                                    'name' => 'order_convert_to_invoice',
                                    'title' => __( 'Gör order till faktura', WTV_Plugin::TEXTDOMAIN ),
                                    'tooltip' => __( 'Konvertera order till faktura. Kräver att ordern har status skickad.', WTV_Plugin::TEXTDOMAIN ),
                                ]
                            ],
                            [
                                'column' => [
                                    'name' => 'order_add_invoice_payment',
                                    'title' => __( 'Sätt faktura som betald', WTV_Plugin::TEXTDOMAIN ),
                                    'tooltip' => __( 'Du kan välja att automatiskt registrera en full inbetalning mot det konto som anges nedan i <br><i><b">Bankkonto som används för inbetalningar</b></i>.', WTV_Plugin::TEXTDOMAIN ),
                                ]
                            ],
                        ],
                        'rows'      => self::get_payment_method_settings( $payment_method->id ),
                    ]
                ],
            ] );
        }

        WTV_Settings::add_section( [
            'page'  => $page,
            'tab'   => 'sync',
            'name'  => 'customer_identifier',
            'title' => __( 'Kund identifikator', WTV_Plugin::TEXTDOMAIN )
        ] );

        WTV_Settings::add_field( [
            'page'          => $page,
            'tab'           => 'sync',
            'section'       => 'customer_identifier',
            'type'          => 'radio',
            'name'          => 'visma_customer_unique_identifier',
            'description' => __( 'Här väljer du hur en kund ska identifieras i Visma', WTV_Plugin::TEXTDOMAIN ),
            'default' => 'email',
            'options'   => [
                [
                    'label' => __( 'Email', WTV_Plugin::TEXTDOMAIN ),
                    'value' => 'email',
                ],
                [
                    'label' => __( 'Organisationsnummer', WTV_Plugin::TEXTDOMAIN ),
                    'value' => 'organization_number',
                ]
            ]
        ] );

        WTV_Settings::add_field( [
            'page' => $page,
            'tab' => 'sync',
            'section' => 'customer_identifier',
            'name' => 'visma_organization_number_meta_key',
            'type' => 'text',
            'title' => __( 'Metafältsnamn för organisationsnummer ', WTV_Plugin::TEXTDOMAIN ),
            'tooltip' => __( 'Kontot för produkter med 25% moms i EU', WTV_Plugin::TEXTDOMAIN ),
        ] );


        WTV_Settings::add_section( [
            'page'  => $page,
            'tab'   => 'sync',
            'name'  => 'preferences',
            'title' => __( 'Övrigt', WTV_Plugin::TEXTDOMAIN )
        ] );

	    if( ! is_plugin_active( 'woocommerce-sequential-order-numbers-pro/woocommerce-sequential-order-numbers.php' ) ) {
		    WTV_Settings::add_field( [
			    'page'          => $page,
			    'tab'           => 'sync',
			    'section'       => 'preferences',
			    'name'          => 'visma_order_number_prefix',
			    'type'          => 'number',
			    'min'           => '1',
			    'max'           => '9999999',
			    'class'         => 'order-number-prefix',
			    'title'         => __( 'Orderprefix', WTV_Plugin::TEXTDOMAIN ),
			    'description'   => __( 'Orderprefix som läggs till framför WooCommerce ordernummer vid synk.', WTV_Plugin::TEXTDOMAIN )
		    ] );
	    }

	    WTV_Settings::add_field( [
		    'page' => $page,
		    'tab' => "sync",
		    'section' => "preferences",
		    'type' => "checkboxes",
		    'title' => __( "Övrigt", WTV_Plugin::TEXTDOMAIN ),
		    'options' => [
			    [
				    'name'  => 'visma_credit_voucher_on_refund',
				    'label' => __( 'Skapa krediteringverifikation när order krediterats', WTV_Plugin::TEXTDOMAIN ),
			    ],
			    [
				    'name'          => 'show_organization_number_field_in_billing_address_form',
				    'label'         => __( 'Visa organisationsnummerfält i kassan', WTV_Plugin::TEXTDOMAIN ),
				    'description'       => __( "Visar ett extra fält i kassaformuläret för organisationsnummer.", WTV_Plugin::TEXTDOMAIN )
			    ],
			    [
				    'class'         => 'make-organization-number-field-required',
				    'name'          => 'make_organization_number_field_required',
				    'label'         => __( 'Gör organisationsnummerfältet obligatoriskt', WTV_Plugin::TEXTDOMAIN ),
			    ],
			    [
				    'name'          => 'visma_do_not_update_customer_on_order_sync',
				    'label'         => __( 'Uppdatera ej kund vid ordersynkronisering', WTV_Plugin::TEXTDOMAIN ),
			    ]
		    ]
	    ] );

        WTV_Settings::add_section( [
            'page' => $page,
            'tab' => "sync",
            'name' => "refund"
        ] );

        WTV_Settings::add_field( [
            'page' => $page,
            'tab' => "sync",
            'section' => "refund",
            'type' => "checkboxes",
            'title' => __( "Retur inställningar", WTV_Plugin::TEXTDOMAIN ),
            'options' => [
                [
                    'name' => "visma_credit_note_on_refund",
                    'label' => __( "Hantera returer", WTV_Plugin::TEXTDOMAIN ),
                    'tooltip' => __( 'Skapar en kreditfaktura för ordrar som blivit fakturerade i Visma. Ordrar som ej blivit fakturerade i Visma ändras.', WTV_Plugin::TEXTDOMAIN ),
                ],
                [
                    'name' => "visma_auto_set_refund_invoice_as_paid",
                    'label' => __( "Sätt kreditfakturor som betalda", WTV_Plugin::TEXTDOMAIN ),
                    'tooltip' => __( 'När en kredit faktura skapas sätts den automatiskt som betald', WTV_Plugin::TEXTDOMAIN ),
                ]
            ]
        ] );

        WTV_Settings::add_field( [
            'page' => $page,
            'tab' => "sync",
            'section' => "preferences",
            'name' => "visma_default_unit",
            'title' => __( "Standard enhet", WTV_Plugin::TEXTDOMAIN ),
            'type' => "dropdown",
            'options' => self::get_units(),
        ] );

        WTV_Settings::add_field( [
            'page' => $page,
            'tab' => "sync",
            'section' => "preferences",
            'name' => "visma_invoice_payment_bank_account",
            'title' => __( "Bankkonto som används för inbetalningar", WTV_Plugin::TEXTDOMAIN ),
            'type' => "dropdown",
            'options' => self::get_bank_accounts(),
            'tooltip' => __( "Välj det bokföringskonto du vill att ordern ska bokföras mot när den markeras som betald.", WTV_Plugin::TEXTDOMAIN ),
        ] );


    }

    /**
     * Get payment method settings
     * @param string $payment_method_id
     * @return array
     */
    private static function get_payment_method_settings( $payment_method_id ){
        $settings = get_option( 'visma_order_sync_settings_' . $payment_method_id, [] );
        return [
            'columns' => [
                [
                    'column' => [
                        'name'      => 'sync_on_status',
                        'content'   => self::get_sync_on_status_dropdown( $payment_method_id, isset( $settings['sync_on_status'] ) ? $settings['sync_on_status'] : ''),
                    ]
                ],
                [
                    'column' => [
                        'name'      => 'payment_account',
                        'content'   => self::get_payment_account_dropdown( $payment_method_id, isset( $settings['payment_account'] ) ? $settings['payment_account'] : '' ),
                    ]
                ],
                [
                    'column' => [
                        'name'      => 'voucher_series',
                        'content'   => self::get_voucher_series_dropdown( $payment_method_id, isset( $settings['voucher_series'] ) ? $settings['voucher_series'] : '' ),
                    ]
                ],
                [
                    'column' => [
                        'name'      => 'terms_of_payment_id',
                        'content'   => self::get_terms_of_payment_dropdown( $payment_method_id, isset( $settings['terms_of_payment_id'] ) ? $settings['terms_of_payment_id'] : '' ),
                    ]
                ],
                [
                    'column' => [
                        'name'      => 'order_status',
                        'content'   => self::get_order_status_dropdown( $payment_method_id, isset( $settings['order_status'] ) ? $settings['order_status'] : '' ),
                    ]
                ],
                [
                    'column' => [
                        'name'      => 'order_convert_to_invoice',
                        'content'   => self::get_order_convert_to_invoice_dropdown( $payment_method_id, isset( $settings['order_convert_to_invoice'] ) ? $settings['order_convert_to_invoice'] : '' ),
                    ]
                ],
                [
                    'column' => [
                        'name'      => 'order_add_invoice_payment',
                        'content'   => self::get_order_add_invoice_payment_dropdown( $payment_method_id, isset( $settings['order_add_invoice_payment'] ) ? $settings['order_add_invoice_payment'] : '' ),
                    ]
                ],
            ]
        ];

    }

    /**
     * Get sync on status dropdown
     * @param string $payment_method_id
     * @param int $selected
     * @return string
     */
    public static function get_sync_on_status_dropdown( $payment_method_id, $selected ){

        $statuses = array();

        foreach ( wc_get_order_statuses() as $status => $title) {
            $statuses[] = array(
                'id'    => substr( $status, 3),
                'name'  => $title
            );
        }

        array_unshift( $statuses, [
            'id' => '',
            'name' => __( 'Manuell synkronisering', WTV_Plugin::TEXTDOMAIN )
        ]);

        $options = array_map( function( $status ) use( $selected ) {
            $selected = ( $status['id'] == $selected ) ? ' selected="selected"' : '';

            return '<option value="' . $status['id'] . '"' . $selected . '>' . $status['name'] . '</option>';
        }, array_values( $statuses ) );

        return '<select name="visma_order_sync_settings_' . $payment_method_id . '[sync_on_status]">' . join( '', $options ) . '</select>';
    }

    /**
     * Get sync on voucher series dropdown
     * @param string $payment_method_id
     * @param int $selected
     * @return array
     */
    public static function get_voucher_series_dropdown( $payment_method_id,  $selected ){

        $options = array_map( function( $choice ) use( $selected ) {
            $selected = ( $choice['id'] == $selected ) ? ' selected="selected"' : '';

            return '<option value="' . $choice['id'] . '"' . $selected . '>' . $choice['name'] . '</option>';
        }, array_values( self::VOUCHER_SERIES_CHOICES ) );

        return '<select name="visma_order_sync_settings_' . $payment_method_id . '[voucher_series]"><option value=""></option>' . join( '', $options ) . '</select>';
    }

    /**
     * Get sync on order status dropdown
     * @param string $payment_method_id
     * @param int $selected
     * @return array
     */
    public static function get_order_status_dropdown( $payment_method_id, $selected ){

        $options = array_map( function( $order_status ) use( $selected ) {
            $selected = ( $order_status['id'] == $selected ) ? ' selected="selected"' : '';

            return '<option value="' . $order_status['id'] . '"' . $selected . '>' . $order_status['name'] . '</option>';
        }, array_values( self::visma_order_statuses() ) );

        return '<select name="visma_order_sync_settings_' . $payment_method_id . '[order_status]"><option value=""></option>' . join( '', $options ) . '</select>';
    }

    /**
     * Get convert order to invoice setting
     * @param string $payment_method_id
     * @param int $selected
     * @return array
     */
    public static function get_order_convert_to_invoice_dropdown( $payment_method_id, $selected ){

        $options = array_map( function( $yes_no ) use( $selected ) {
            $selected = ( $yes_no['id'] == $selected ) ? ' selected="selected"' : '';

            return '<option value="' . $yes_no['id'] . '"' . $selected . '>' . $yes_no['name'] . '</option>';
        }, array_values( self::visma_yes_no() ) );

        return '<select name="visma_order_sync_settings_' . $payment_method_id . '[order_convert_to_invoice]"><option value=""></option>' . join( '', $options ) . '</select>';
    }

    /**
     * Get add invoice payment setting
     * @param string $payment_method_id
     * @param int $selected
     * @return array
     */
    public static function get_order_add_invoice_payment_dropdown( $payment_method_id, $selected ){

        $options = array_map( function( $yes_no ) use( $selected ) {
            $selected = ( $yes_no['id'] == $selected ) ? ' selected="selected"' : '';
            return '<option value="' . $yes_no['id'] . '"' . $selected . '>' . $yes_no['name'] . '</option>';
        }, array_values( self::visma_yes_no() ) );

        return '<select name="visma_order_sync_settings_' . $payment_method_id . '[order_add_invoice_payment]"><option value=""></option>' . join( '', $options ) . '</select>';
    }

    /**
     * Get sync on terms of payment dropdown
     * @param string $payment_method_id
     * @param int $selected
     * @return array
     */
    public static function get_terms_of_payment_dropdown( $payment_method_id, $selected ){
        $terms_of_payments = get_option( 'visma_terms_of_payments', [] );

        array_unshift( $terms_of_payments, [
            'id' => '',
            'name' => __( 'Välj...', WTV_Plugin::TEXTDOMAIN )
        ]);

        $options = array_map( function( $term_of_payment ) use( $selected ) {
            $selected = ( $term_of_payment['id'] == $selected ) ? ' selected="selected"' : '';

            return '<option value="' . $term_of_payment['id'] . '"' . $selected . '>' . $term_of_payment['name'] . '</option>';
        }, array_values( $terms_of_payments ) );

        return '<select name="visma_order_sync_settings_' . $payment_method_id . '[terms_of_payment_id]">' . join( '', $options ) . '</select>';
    }

    /**
     * Get sync on payment_account dropdown
     * @param string $payment_method_id
     * @param int $selected
     * @return string
     */
    public static function get_payment_account_dropdown( $payment_method_id, $selected ){
        $payment_account_arr = [];
        $payment_accounts = get_option( 'visma_payment_accounts', [] );
        foreach ( $payment_accounts as $payment_account) {
            $payment_account_arr[] = [
                'number' => $payment_account
            ];
        }

        $options = array_map( function( $payment_account ) use( $selected ) {
            $selected = ( $payment_account['number'] == $selected ) ? ' selected="selected"' : '';

            return '<option value="' . $payment_account['number'] . '"' . $selected . '>' . $payment_account['number'] . '</option>';
        }, array_values( $payment_account_arr ) );

        return '<select name="visma_order_sync_settings_' . $payment_method_id . '[payment_account]"><option value=""></option>' . join( '', $options ) . '</select>';
    }

    /**
     * Returns units
     * @return array
     */
    public static function get_units(){
        $units_arr = [];
        $units = get_option( 'visma_units', [] );

        foreach ( $units as $unit) {
            $units_arr[] = [
                'value' => $unit->Id,
                'label' => $unit->Abbreviation
            ];
        }
        array_unshift( $units_arr, [
            'value' => '',
            'label' => __( 'Välj...', WTV_Plugin::TEXTDOMAIN )
        ]);

        return $units_arr;
    }

    /**
     * Returns units
     * @return array
     */
    public static function get_bank_accounts(){

        $bank_accounts = get_option( 'visma_bank_accounts', [] );
        array_unshift( $bank_accounts, [
            'value' => '',
            'label' => __( 'Välj...', WTV_Plugin::TEXTDOMAIN )
        ]);
        return $bank_accounts;
    }
}
    
