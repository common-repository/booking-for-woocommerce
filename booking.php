<?php

/*
 * Plugin Name: MVV WooCommerce Booking Addon
 * Version: 4.3.1
 * Plugin URI:
 * Description:Booking and Reservation Addon for WooCommerce
 * Author URI:http://mvvapps.com
 * Author:mvvapps
 * Requires at least: 4.0
 * Text Domain: booking-for-woocommerce
 * Tested up to: 5.9.3
 * WC requires at least: 3.3.0
 * WC tested up to: 6.4
 */
if ( !defined( 'ABSPATH' ) ) {
    exit;
}

if ( function_exists( 'mvvwb_fs' ) ) {
    mvvwb_fs()->set_basename( false, __FILE__ );
} else {
    // DO NOT REMOVE THIS IF, IT IS ESSENTIAL FOR THE `function_exists` CALL ABOVE TO PROPERLY WORK.
    
    if ( !function_exists( 'mvvwb_fs' ) ) {
        // Create a helper function for easy SDK access.
        function mvvwb_fs()
        {
            global  $mvvwb_fs ;
            
            if ( !isset( $mvvwb_fs ) ) {
                // Include Freemius SDK.
                require_once dirname( __FILE__ ) . '/freemius/start.php';
                $mvvwb_fs = fs_dynamic_init( array(
                    'id'             => '4871',
                    'slug'           => 'booking-for-woocommerce',
                    'type'           => 'plugin',
                    'public_key'     => 'pk_75a0160a689a4f6c76f519b6cdced',
                    'is_premium'     => false,
                    'has_addons'     => false,
                    'has_paid_plans' => true,
                    'trial'          => array(
                    'days'               => 7,
                    'is_require_payment' => false,
                ),
                    'menu'           => array(
                    'slug' => 'mvvwb-dashboard',
                ),
                    'is_live'        => true,
                ) );
            }
            
            return $mvvwb_fs;
        }
        
        // Init Freemius.
        mvvwb_fs();
        // Signal that SDK was initiated.
        do_action( 'mvvwb_fs_loaded' );
    }
    
    // ... Your plugin's main file logic ...
    define( 'MVVWB_VERSION', '4.3.1' );
    define( 'MVVWB_TOKEN', 'mvvwb' );
    define( 'MVVWB_ITEMS_PT', 'mvvwb_items' );
    define( 'MVVWB_BOOKING_PT', 'mvvwb_bookings' );
    define( 'MVVWB_RESOURCE_PT', 'mvvwb_resource' );
    define( 'MVVWB_RESOURCE_TAX', 'mvvwb_resource_tax' );
    define( 'MVVWB_CART_ITEM_KEY', 'mvvwb_cart_item_key' );
    define( 'MVVWB_ORDER_ITEM_KEY', '_mvvwb_order_item_key' );
    define( 'MVVWB_ORDER_BOOKING_ID_KEY', '_mvvwb_booking' );
    define( 'MVVWB___FILE__', __FILE__ );
    define( 'MVVWB_TEMPLATE_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) . '/templates/' );
    if ( !defined( 'MVVWB_TEMPLATE_PATH_THEME' ) ) {
        define( 'MVVWB_TEMPLATE_PATH_THEME', false );
    }
    require_once realpath( plugin_dir_path( __FILE__ ) ) . DIRECTORY_SEPARATOR . 'include/functions.php';
    if ( !function_exists( 'mvvwb_load_textdomain' ) ) {
        function mvvwb_load_textdomain()
        {
            load_plugin_textdomain( 'mvvwb', false, basename( dirname( __FILE__ ) ) . '/languages' );
        }
    
    }
    add_action( 'init', 'mvvwb_load_textdomain' );
    spl_autoload_register( 'mvvwb_autoloader' );
    if ( is_admin() ) {
        MVVWB_Backend::instance( __FILE__, MVVWB_VERSION );
    }
    new MVVWB_Api();
    new MVVWB_Front_End( __FILE__, MVVWB_VERSION );
}
