<?php

namespace includes;

class WTV_Migrate{

    /**
     * @param $upgrader_object
     * @param $options
     */
    public static function wp_update_completed( $upgrader_object, $options ) {

        if( $options['action'] == 'update' && $options['type'] == 'plugin' && isset( $options['plugins'] ) ) {
            foreach( $options['plugins'] as $plugin ) {
                if( $plugin == 'woocommerce-visma-integration/plugin.php' ) {
                    self::maybe_update_db();
                }
            }
        }
    }

    /**
     *
     */
    public static function maybe_update_db(){
        $db_version = self::get_db_version();

        if ( $db_version === 1.0 ){
            self::update_db();
            self::update_db_version( 2.0 );
        }

    }

    /**
     *
     */
    private static function update_db() {
        $license_key = get_option( 'wetail_license_key' );
        update_option( 'wetail_visma_license_key', $license_key );

        if( get_option( 'visma_sync_orders' ) ){
            update_option('visma_sync_order_method', 'create_orders');
        }
        else{
            update_option('visma_sync_order_method', 'create_vouchers');
        }
        delete_option('visma_sync_orders' );

    }

    /**
     * @param $version
     */
    private static function update_db_version( $version ) {
        update_option( 'visma_db_version', $version );
    }

    /***
     * @return float|int
     */
    private static function get_db_version() {

        $db_version = (int)get_option( 'visma_db_version' );

        if ( ! $db_version ) {
            return 1.0;
        }
        return $db_version;

    }
}