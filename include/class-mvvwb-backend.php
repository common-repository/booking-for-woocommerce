<?php

if ( !defined( 'ABSPATH' ) ) {
    exit;
}
class MVVWB_Backend
{
    /**
     * @var    object
     * @access  private
     * @since    1.0.0
     */
    private static  $_instance = null ;
    /**
     * The version number.
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public  $_version ;
    /**
     * The token.
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public  $_token ;
    /**
     * The main plugin file.
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public  $file ;
    /**
     * The main plugin directory.
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public  $dir ;
    /**
     * The plugin assets directory.
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public  $assets_dir ;
    /**
     * Suffix for Javascripts.
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public  $script_suffix ;
    /**
     * The plugin assets URL.
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public  $assets_url ;
    /**
     * Constructor function.
     * @access  public
     * @return  void
     * @since   1.0.0
     */
    public function __construct( $file = '', $version = '1.0.0' )
    {
        $this->_version = $version;
        $this->_token = MVVWB_TOKEN;
        $this->file = $file;
        $this->dir = dirname( $this->file );
        $this->assets_dir = trailingslashit( $this->dir ) . 'assets';
        $this->assets_url = esc_url( trailingslashit( plugins_url( '/assets/', $this->file ) ) );
        register_activation_hook( $this->file, array( $this, 'install' ) );
        register_deactivation_hook( $this->file, array( $this, 'deactivation' ) );
        add_action(
            'admin_enqueue_scripts',
            array( $this, 'admin_enqueue_scripts' ),
            10,
            1
        );
        add_action( 'admin_menu', array( $this, 'register_root_page' ) );
        $plugin = plugin_basename( $this->file );
        add_filter( "plugin_action_links_{$plugin}", array( $this, 'add_settings_link' ) );
        //        add_action('deleted_post', array($this, 'deleted_post'), 1, 10);
        add_filter(
            'manage_product_posts_columns',
            array( $this, 'manage_products_columns' ),
            10,
            1
        );
        add_action(
            'manage_product_posts_custom_column',
            array( $this, 'manage_products_column' ),
            10,
            2
        );
        add_action(
            'woocommerce_before_order_itemmeta',
            array( $this, 'before_order_itemmeta' ),
            10,
            3
        );
        add_filter(
            'product_type_options',
            array( $this, 'product_type_options' ),
            10,
            1
        );
        MVVWB_Product_Meta::instance();
    }
    
    /**
     *
     *
     *
     *
     * @return Main MVVWB instance
     * @see WordPress_Plugin_Template()
     * @since 1.0.0
     * @static
     */
    public static function instance( $file = '', $version = '1.0.0' )
    {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self( $file, $version );
        }
        return self::$_instance;
    }
    
    public function before_order_itemmeta( $item_id, $item, $product )
    {
        $booking_ids = MVVWB_Booking::getBookingByItemIds( $item_id );
        $this->view( 'booking-display-backend', [
            'booking_ids' => $booking_ids,
        ] );
    }
    
    static function view( $view, $data = array() )
    {
        extract( $data );
        include plugin_dir_path( __FILE__ ) . 'views/' . $view . '.php';
    }
    
    public function manage_products_columns( $columns )
    {
        $new = array_merge( array_slice(
            $columns,
            0,
            -2,
            true
        ), [
            'mvvwb_item' => __( 'Booking Item', '' ),
        ], array_slice(
            $columns,
            -2,
            null,
            true
        ) );
        return $new;
    }
    
    public function manage_products_column( $column_name, $post_id )
    {
        
        if ( $column_name == 'mvvwb_item' ) {
            $item = get_post_meta( $post_id, '_mvvwb_product_meta', true );
            $link = '';
            if ( $item ) {
                $link .= '<a href="' . admin_url( 'admin.php?page=mvvwb-dashboard#/item/' . $item ) . '">' . get_the_title( $item ) . '</a>, ';
            }
            echo  trim( $link, ', ' ) ;
        }
    
    }
    
    public function product_type_options( $type_options )
    {
        $type_options['mvvwb_bookable'] = array(
            'id'            => '_mvvwb_bookable',
            'wrapper_class' => '',
            'label'         => __( 'Bookable', 'booking-for-woocommerce' ),
            'description'   => __( 'Check if this product is Bookable', 'booking-for-woocommerce' ),
            'default'       => 'no',
        );
        return $type_options;
    }
    
    public function add_settings_link( $links )
    {
        $settings = '<a href="' . admin_url( 'admin.php?page=mvvwb-dashboard#/items' ) . '">' . __( 'Settings', 'booking-for-woocommerce' ) . '</a>';
        array_push( $links, $settings );
        return $links;
    }
    
    public function register_root_page()
    {
        add_submenu_page(
            null,
            'MVV Booking Old menu',
            'MVV Booking Old menu',
            'read',
            'mvvwb_admin_ui',
            array( $this, 'oldUrl' )
        );
        $this->hook_suffix[] = add_menu_page(
            __( 'Bookings', 'booking-for-woocommerce' ),
            __( 'Bookings', 'booking-for-woocommerce' ),
            'manage_woocommerce',
            'mvvwb-dashboard',
            array( $this, 'dashboard' ),
            'dashicons-calendar-alt',
            56
        );
        add_submenu_page(
            'mvvwb-dashboard',
            __( 'Services/Items', 'booking-for-woocommerce' ),
            __( 'Manage Items', 'booking-for-woocommerce' ),
            'manage_woocommerce',
            'mvvwb-dashboard#/items',
            array( $this, 'dashboard' )
        );
        add_submenu_page(
            'mvvwb-dashboard',
            __( 'WooCommerce Booking Calendar', 'booking-for-woocommerce' ),
            __( 'Calendar', 'booking-for-woocommerce' ),
            'manage_woocommerce',
            'mvvwb-dashboard#/calendar',
            array( $this, 'dashboard' )
        );
        add_submenu_page(
            'mvvwb-dashboard',
            __( 'WooCommerce Booking Settings', 'booking-for-woocommerce' ),
            __( 'Settings', 'booking-for-woocommerce' ),
            'manage_options',
            'mvvwb-dashboard#/settings',
            array( $this, 'dashboard' )
        );
    }
    
    public function oldUrl()
    {
        $link = admin_url( 'admin.php?page=mvvwb-dashboard#' );
        echo  '<a href="' . $link . '">This URL has changed, Please click here </a>' ;
    }
    
    public function dashboard()
    {
        echo  '<div id="mvvwb-dashboard">Loading....</div>' ;
    }
    
    /**
     * Load admin Javascript.
     * @access  public
     * @return  void
     * @since   1.0.0
     */
    public function admin_enqueue_scripts( $hook = '' )
    {
        if ( !isset( $this->hook_suffix ) || empty($this->hook_suffix) ) {
            return;
        }
        wp_register_style(
            $this->_token . '-style',
            esc_url( $this->assets_url ) . 'css/style.css',
            array(),
            $this->_version
        );
        wp_enqueue_style( $this->_token . '-style' );
        
        if ( !mvvwb_fs()->is_premium() ) {
            wp_register_style(
                $this->_token . '-backend',
                esc_url( $this->assets_url ) . 'css/backend.css',
                array(),
                $this->_version
            );
            wp_register_script(
                $this->_token . '-admin',
                esc_url( $this->assets_url ) . 'js/admin.js',
                array( 'wp-i18n' ),
                $this->_version,
                true
            );
            wp_register_script(
                $this->_token . '-productMeta',
                esc_url( $this->assets_url ) . 'js/productMeta.js',
                array( 'wp-i18n' ),
                $this->_version,
                true
            );
        }
        
        wp_enqueue_style( $this->_token . '-backend' );
        $screen = get_current_screen();
        wp_enqueue_script( 'jquery' );
        if ( !wp_script_is( 'wp-i18n', 'registered' ) ) {
            wp_register_script(
                'wp-i18n',
                esc_url( $this->assets_url ) . 'js/i18n.min.js',
                array(),
                $this->_version,
                true
            );
        }
        
        if ( in_array( $screen->id, $this->hook_suffix ) ) {
            wp_enqueue_script( 'wp-i18n' );
            $i18n = new MVVWB_I18n();
            wp_enqueue_script( $this->_token . '-admin' );
            wp_localize_script( $this->_token . '-admin', $this->_token . '_object', array(
                'api_nonce'  => wp_create_nonce( 'wp_rest' ),
                'root'       => rest_url( $this->_token . '/v1/' ),
                'locale'     => strtolower( get_locale() ),
                'assets_url' => esc_url( $this->assets_url ),
                'currency'   => ( $this->check_woocommerce_active() ? get_woocommerce_currency_symbol() : '' ),
                'gmt_offset' => get_option( 'gmt_offset' ) * HOUR_IN_SECONDS,
            ) );
            wp_enqueue_script(
                $this->_token . '-front',
                esc_url( $this->assets_url ) . 'js/front.js',
                [],
                $this->_version,
                true
            );
            wp_localize_script( $this->_token . '-front', $this->_token . '_config', array(
                'api_nonce'    => wp_create_nonce( 'wp_rest' ),
                'root'         => rest_url( $this->_token . '/v1/' ),
                'lang'         => ( $i18n->isActive() && !$i18n->isDefault() ? $i18n->currentLang : false ),
                'locale'       => false,
                'global'       => mvvwb_getAllConfig(),
                'config'       => [],
                'initTriggers' => [],
                'dateFormat'   => __( get_option( 'date_format' ) ),
                'timeFormat'   => __( get_option( 'time_format' ) ),
            ) );
        }
        
        
        if ( mvvwb_fs()->is_premium() ) {
            $content_path = dirname( $this->file ) . '/languages/';
        } else {
            $content_path = WP_LANG_DIR . '/plugins/';
        }
        
        $content_path = apply_filters( 'mvvwb_language_path', $content_path, dirname( $this->file ) );
        wp_set_script_translations( $this->_token . '-admin', 'booking-for-woocommerce', $content_path );
        wp_enqueue_script( $this->_token . '-productMeta' );
        wp_localize_script( $this->_token . '-productMeta', $this->_token . '_productMeta', array(
            'api_nonce'  => wp_create_nonce( 'wp_rest' ),
            'root'       => rest_url( $this->_token . '/v1/' ),
            'gmt_offset' => get_option( 'gmt_offset' ) * HOUR_IN_SECONDS,
        ) );
        wp_set_script_translations( $this->_token . '-productMeta', 'booking-for-woocommerce', $content_path );
    }
    
    public function check_woocommerce_active()
    {
        if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
            return true;
        }
        
        if ( is_multisite() ) {
            $plugins = get_site_option( 'active_sitewide_plugins' );
            if ( isset( $plugins['woocommerce/woocommerce.php'] ) ) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Cloning is forbidden.
     *
     * @since 1.0.0
     */
    public function __clone()
    {
        _doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), $this->_version );
    }
    
    /**
     * Unserializing instances of this class is forbidden.
     *
     * @since 1.0.0
     */
    public function __wakeup()
    {
        _doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), $this->_version );
    }
    
    /**
     * Installation. Runs on activation.
     * @access  public
     * @return  void
     * @since   1.0.0
     */
    public function install()
    {
        $this->_log_version_number();
        add_rewrite_endpoint( 'bookings', EP_PAGES );
        flush_rewrite_rules();
        if ( !wp_next_scheduled( 'mvvwb_cron_action_daily' ) ) {
            wp_schedule_event( time(), 'daily', 'mvvwb_cron_action_daily' );
        }
        MVVWB_Transient::clearTransientStore();
    }
    
    /**
     * Log the plugin version number.
     * @access  public
     * @return  void
     * @since   1.0.0
     */
    private function _log_version_number()
    {
        update_option( $this->_token . '_version', $this->_version );
    }
    
    public function deactivation()
    {
    }

}