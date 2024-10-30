<?php

if (!defined('ABSPATH'))
    exit;

/**
 * Class MVVWB_Transient
 */
class MVVWB_Transient
{


    public $_version;


    function __construct()
    {


    }

    /**
     * clear mvvwb_transients option
     */
    static function clearTransientStore(){

        update_option('mvvwb_transients', ['products' => [], 'general' => [],'resources' => []]);
        $mvvTransients = self::get_transient_keys_with_prefix('mvvwb_slots_');
        $mvvTransients2 = self::get_transient_keys_with_prefix('mvvwb_temp_');
        foreach ($mvvTransients as $v){
            delete_transient($v);
        }
        foreach ($mvvTransients2 as $v){
            delete_transient($v);
        }
    }

    static function get_transient_keys_with_prefix( $prefix ) {
        global $wpdb;

        $prefix = $wpdb->esc_like( '_transient_' . $prefix );
        $sql    = "SELECT `option_name` FROM $wpdb->options WHERE `option_name` LIKE '%s'";
        $keys   = $wpdb->get_results( $wpdb->prepare( $sql, $prefix . '%' ), ARRAY_A );

        if ( is_wp_error( $keys ) ) {
            return [];
        }

        return array_map( function( $key ) {
            // Remove '_transient_' from the option name.
            return ltrim( $key['option_name'], '_transient_' );
        }, $keys );
    }
    static function dailyClear()
    {
        $transients = get_option('mvvwb_transients', ['products' => [], 'general' => [],'resources' => []]);
        foreach ($transients['products'] as $k=>$p) {
            foreach ($p as $i=>$key) {
                if(get_transient($key)===false){
                    unset($transients['products'][$k][$i]);
                }

            }
        }
        foreach ($transients['resources'] as $k=>$p) {
            foreach ($p as $i=>$key) {
                if(get_transient($key)===false){
                    unset($transients['resources'][$k][$i]);
                }

            }
        }
        $mvvTransients = self::get_transient_keys_with_prefix('mvvwb_slots_');
        $mvvTransients2 = self::get_transient_keys_with_prefix('mvvwb_temp_');
        foreach ($mvvTransients as $v){
            get_transient($v);
        }
        foreach ($mvvTransients2 as $v){
            get_transient($v);
        }
        update_option('mvvwb_transients', $transients);
    }

    static function clearTransByProduct($p_id)
    {
        $transients = get_option('mvvwb_transients', ['products' => [], 'general' => [],'resources'=>[]]);
        if (isset($transients['products'][$p_id])) {
            foreach ($transients['products'][$p_id] as $i => $key) {
                if (substr($key, 0, 11) !== "mvvwb_temp_") {
                    delete_transient($key);
                    unset($transients['products'][$p_id][$i]);
                }
            }
        }
        update_option('mvvwb_transients', $transients);
    }
    static function clearTransByResource($r_id)
    {
        $transients = get_option('mvvwb_transients', ['products' => [], 'general' => [],'resources'=>[]]);
        if (isset($transients['resources'][$r_id])) {
            foreach ($transients['resources'][$r_id] as $i => $key) {
                if (substr($key, 0, 11) !== "mvvwb_temp_") {
                    delete_transient($key);
                    unset($transients['products'][$r_id][$i]);
                }
            }
        }
        update_option('mvvwb_transients', $transients);
    }
    static function setTransient($key, $data, $p_id = false, $expire = false, $isResource = false)
    {
        set_transient($key, $data, $expire-time());
        $transients = get_option('mvvwb_transients', ['products' => [], 'general' => [], 'resources' => []]);
        if ($p_id) {
            if ($isResource) {
                if (isset($transients['resources'][$p_id])) {
                    if(!in_array($key, $transients['resources'][$p_id], true)){
                        $transients['resources'][$p_id][] = $key;
                    }
                } else {
                    $transients['resources'][$p_id] = [$key];
                }
            } else {
                if (isset($transients['products'][$p_id])) {

                    if(!in_array($key, $transients['products'][$p_id], true)){
                        $transients['products'][$p_id][] = $key;
                    }
                } else {
                    $transients['products'][$p_id] = [$key];
                }
            }

        } else {
            $transients['general'][] = $key;
        }
        update_option('mvvwb_transients', $transients);
    }
}
