<?php

namespace includes\woo_api;


class WTV_Routes{

    /**
     * Register callback route
     */
    public static function register_routes(){
        $callback_controller = new WTV_Callback_Controller();
        $callback_controller->register_routes();

        $pull_sync_controller = new WTV_Pull_Sync_Controller();
        $pull_sync_controller->register_routes();

    }
}