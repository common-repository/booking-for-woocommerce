<?php

if ( !defined( 'ABSPATH' ) ) {
    exit;
}
class MVVWB_Front_End
{
    static  $cart_error = array() ;
    /**
     * The single instance of WordPress_Plugin_Template_Settings.
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
     * The plugin assets URL.
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public  $assets_url ;
    public  $file ;
    function __construct( $file = '', $version = '1.0.0' )
    {
        $this->_version = $version;
        $this->_token = MVVWB_TOKEN;
        $this->file = $file;
        $this->assets_url = esc_url( trailingslashit( plugins_url( '/assets/', $this->file ) ) );
        add_action( 'woocommerce_before_add_to_cart_button', array( $this, 'before_add_to_cart_button' ), 10 );
        add_filter(
            'woocommerce_add_cart_item_data',
            array( $this, 'submit_booking' ),
            10,
            3
        );
        //        add_action('woocommerce_add_to_cart', array($this, 'add_to_cart'), 10, 6);
        add_action(
            'wp_enqueue_scripts',
            array( $this, 'enqueue_scripts' ),
            10,
            1
        );
        add_filter(
            'woocommerce_add_to_cart_validation',
            array( $this, 'add_to_cart_validation' ),
            99,
            4
        );
        add_action(
            'woocommerce_checkout_create_order_line_item',
            array( $this, 'create_order_line_item' ),
            10,
            4
        );
        add_action(
            'woocommerce_checkout_update_order_meta',
            array( $this, 'update_order_meta' ),
            10,
            1
        );
        //        add_action('__experimental_woocommerce_blocks_checkout_update_order_meta', array($this, 'update_order_meta'), 10, 1);
        add_action(
            '__experimental_woocommerce_blocks_checkout_update_order_from_request',
            array( $this, 'update_order_meta' ),
            10,
            1
        );
        add_filter(
            'woocommerce_get_item_data',
            array( $this, 'get_item_data' ),
            10,
            2
        );
        //        add_filter('woocommerce_product_get_price', array($this, 'get_product_price'), 10, 2);
        add_filter(
            'woocommerce_is_sold_individually',
            array( $this, 'is_sold_individually' ),
            10,
            2
        );
        add_filter(
            'woocommerce_product_add_to_cart_text',
            array( $this, 'add_to_cart_text' ),
            10,
            2
        );
        add_filter(
            'woocommerce_product_single_add_to_cart_text',
            array( $this, 'add_to_cart_text' ),
            10,
            2
        );
        add_action( 'woocommerce_single_product_summary', array( $this, 'product_purchasable_warning' ), 30 );
        add_action(
            'woocommerce_order_item_meta_end',
            array( $this, 'booking_display' ),
            10,
            3
        );
        add_filter(
            'post_class',
            array( $this, 'product_class' ),
            10,
            3
        );
        add_filter(
            'woocommerce_cart_item_quantity',
            array( $this, 'cart_item_quantity' ),
            10,
            3
        );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ), 10 );
        add_action(
            'woocommerce_check_cart_items',
            array( $this, 'check_cart_items' ),
            10,
            1
        );
        //        add_action('wooocommerce_store_api_validate_cart_item', array($this, 'api_validate_cart_item'), 10, 2); // added for block cart
        add_action(
            'woocommerce_cart_item_removed',
            array( $this, 'cart_item_removed' ),
            10,
            1
        );
        //
        add_action(
            'woocommerce_after_cart_item_name',
            array( $this, 'conf_required_message' ),
            10,
            2
        );
        add_filter(
            'woocommerce_product_add_to_cart_url',
            array( $this, 'add_to_cart_url' ),
            20,
            2
        );
        add_filter(
            'woocommerce_product_supports',
            array( $this, 'product_supports' ),
            10,
            3
        );
        add_action( 'woocommerce_account_menu_items', array( $this, 'account_menu_items' ), 10 );
        //        add_action('woocommerce_cart_loaded_from_session', array($this, 'before_calculate_totals'), 9, 1);
        add_action(
            'woocommerce_before_calculate_totals',
            array( $this, 'before_calculate_totals' ),
            9,
            1
        );
        add_action( 'woocommerce_account_bookings_endpoint', array( $this, 'bookings_endpoint' ) );
        add_filter(
            'woocommerce_quantity_input_args',
            array( $this, 'quantity_input_args' ),
            10,
            2
        );
        add_filter(
            'woocommerce_add_to_cart_quantity',
            array( $this, 'add_to_cart_quantity' ),
            10,
            2
        );
        add_action( 'mvvwb_cron_action_daily', array( 'MVVWB_Transient', 'dailyClear' ) );
        add_action( 'wp_footer', array( $this, 'footer_scripts' ) );
        new MVVWB_Booking_Actions();
        new MVVWB_Cron_Manager();
        add_action( 'init', array( $this, 'init' ) );
        //        add_filter('query_vars', array($this, 'add_query_vars'), 0);
    }
    
    public static function init( $parent )
    {
        require_once plugin_dir_path( dirname( __FILE__ ) ) . '/include/compatibility/multi-currencies.php';
        add_rewrite_endpoint( 'bookings', EP_PAGES );
        $post_type = MVVWB_BOOKING_PT;
        $labels = array(
            'name'               => __( 'Booking', 'booking-for-woocommerce' ),
            'singular_name'      => __( 'Booking', 'booking-for-woocommerce' ),
            'name_admin_bar'     => 'MVVWB_Booking',
            'add_new'            => _x( 'Add New Booking', $post_type, '' ),
            'add_new_item'       => sprintf( __( 'Add New %s', 'booking-for-woocommerce' ), 'Booking' ),
            'edit_item'          => sprintf( __( 'Edit %s', 'booking-for-woocommerce' ), 'Booking' ),
            'new_item'           => sprintf( __( 'New %s', 'booking-for-woocommerce' ), 'Booking' ),
            'all_items'          => sprintf( __( 'All Booking', 'booking-for-woocommerce' ), 'Booking' ),
            'view_item'          => sprintf( __( 'View %s', 'booking-for-woocommerce' ), 'Booking' ),
            'search_items'       => sprintf( __( 'Search %s', 'booking-for-woocommerce' ), 'Booking' ),
            'not_found'          => sprintf( __( 'No %s Found', 'booking-for-woocommerce' ), 'Booking' ),
            'not_found_in_trash' => sprintf( __( 'No %s Found In Trash', 'booking-for-woocommerce' ), 'Booking' ),
            'parent_item_colon'  => sprintf( __( 'Parent %s', 'booking-for-woocommerce' ), 'Booking' ),
            'menu_name'          => 'Booking',
        );
        $args = array(
            'labels'              => apply_filters( $post_type . '_labels', $labels ),
            'description'         => '',
            'public'              => false,
            'publicly_queryable'  => false,
            'exclude_from_search' => true,
            'show_ui'             => false,
            'show_in_nav_menus'   => false,
            'query_var'           => false,
            'can_export'          => true,
            'rewrite'             => false,
            'capability_type'     => 'post',
            'has_archive'         => false,
            'rest_base'           => $post_type,
            'hierarchical'        => false,
            'show_in_rest'        => false,
            'supports'            => array( 'title' ),
            'menu_position'       => 58,
            'menu_icon'           => 'dashicons-admin-post',
        );
        register_post_type( $post_type, apply_filters( $post_type . '_register_args', $args, $post_type ) );
        register_taxonomy( MVVWB_RESOURCE_TAX, MVVWB_RESOURCE_PT, array(
            'label'        => __( 'Resources' ),
            'public'       => false,
            'rewrite'      => false,
            'hierarchical' => false,
        ) );
        register_post_status( 'complete' );
        register_post_status( 'paid' );
        register_post_status( 'confirmed' );
        register_post_status( 'unpaid' );
        register_post_status( 'pending-confirmation' );
        register_post_status( 'cancelled' );
        register_post_status( 'in-cart' );
        register_post_status( 'was-in-cart' );
    }
    
    public static function instance( $parent )
    {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self( $parent );
        }
        return self::$_instance;
    }
    
    public function include_gateway( $gateways )
    {
    }
    
    public function product_purchasable_warning()
    {
        global  $product ;
        
        if ( !$product->is_purchasable() && $product->is_type( [ 'simple', 'variable' ] ) ) {
            $product_id = $product->get_id();
            if ( current_user_can( 'manage_options' ) && $this->isBookable( $product_id ) ) {
                echo  '<div style="color:#f0841a">' . __( 'Don\'t leave product price field blank, Set a value , then only this will show the booking form', 'booking-for-woocommerce' ) . '</div>' ;
            }
        }
    
    }
    
    public function isBookable( $product_id )
    {
        $mvv_bookable = ( 'yes' === get_post_meta( $product_id, '_mvvwb_bookable', true ) ? true : false );
        return $mvv_bookable;
    }
    
    public function footer_scripts()
    {
        $this->view( 'footer-scripts' );
    }
    
    static function view( $view, $data = array() )
    {
        extract( $data );
        include plugin_dir_path( __FILE__ ) . 'views/' . $view . '.php';
    }
    
    public function product_supports( $support, $feature, $product )
    {
        $mvv_bookable = ( 'yes' === get_post_meta( $product->get_id(), '_mvvwb_bookable', true ) ? true : false );
        if ( $feature == 'ajax_add_to_cart' && $mvv_bookable ) {
            $support = FALSE;
        }
        return $support;
    }
    
    public function product_class( $classes = array(), $class = false, $product_id = false )
    {
        $mvv_bookable = ( 'yes' === get_post_meta( $product_id, '_mvvwb_bookable', true ) ? true : false );
        if ( $mvv_bookable ) {
            $classes[] = 'mvvwb_bookable';
        }
        return $classes;
    }
    
    public function add_to_cart_url( $url, $product )
    {
        $mvv_bookable = ( 'yes' === get_post_meta( $product->get_id(), '_mvvwb_bookable', true ) ? true : false );
        
        if ( $mvv_bookable ) {
            return $product->get_permalink();
        } else {
            return $url;
        }
    
    }
    
    public function add_query_vars( $vars )
    {
        $vars[] = 'bookings';
        return $vars;
    }
    
    public function conf_required_message( $cart_item, $cart_item_key )
    {
    }
    
    public function cart_item_removed()
    {
        $session = MVVWB_Customer::get_session();
        $clearedIds = [];
        
        if ( method_exists( WC()->cart, 'get_removed_cart_contents' ) ) {
            $removed_cart_contents = WC()->cart->get_removed_cart_contents();
        } else {
            $removed_cart_contents = [];
        }
        
        foreach ( $removed_cart_contents as $item ) {
            
            if ( $item['variation_id'] !== 0 ) {
                $p_id = $item['variation_id'];
            } else {
                $p_id = $item['product_id'];
            }
            
            
            if ( !in_array( $p_id, $clearedIds ) ) {
                $temp = new MVVWB_Temp( $p_id );
                $temp->clear( $session );
                $clearedIds[] = $p_id;
            }
        
        }
    }
    
    /**
     * Check all cart items for errors.
     */
    public function check_cart_items()
    {
        $return = true;
        $needConfirmation = null;
        foreach ( WC()->cart->get_cart() as $cart_item_key => $values ) {
            $product = $values['data'];
            $booking = new MVVWB_Booking_Item( $product->get_id() );
            
            if ( $booking->isValid && isset( $values[MVVWB_CART_ITEM_KEY] ) ) {
                $bookingData = $values[MVVWB_CART_ITEM_KEY]['bookingData'];
                $bookingData['quantity'] = $values['quantity'];
                $booking->setBookingData( $bookingData );
                $date = $values[MVVWB_CART_ITEM_KEY]['bookingData']['start'];
                
                if ( $needConfirmation !== null && $needConfirmation !== $booking->requiresConfirmation() ) {
                    // using this logic to check if there any item which need not admin cofirm along with item which need confirmation
                    wc_add_notice( __( 'Sorry, You can\'t have  required confirmation item along with other items. Please remove items from cart .', 'booking-for-woocommerce' ), 'error' );
                    return false;
                }
                
                $needConfirmation = $booking->requiresConfirmation();
                // Check stock based on stock-status.
                $avail = $booking->checkAvailability( false, false, true );
                
                if ( $avail === false || isset( $avail['status'] ) && $avail['status'] === false ) {
                    /* translators: %s: product name */
                    wc_add_notice( sprintf( __( 'Sorry, "%s" is not available on date %s. Please edit your cart and try again. We apologize for any inconvenience caused.', 'booking-for-woocommerce' ), $product->get_name(), $date->format( 'Y-m-d' ) ), 'error' );
                    return false;
                }
            
            }
        
        }
        
        if ( !wp_doing_ajax() ) {
            $session = MVVWB_Customer::get_session();
            $clearedIds = [];
            
            if ( method_exists( WC()->cart, 'get_removed_cart_contents' ) ) {
                $removed_cart_contents = WC()->cart->get_removed_cart_contents();
            } else {
                $removed_cart_contents = [];
            }
            
            foreach ( $removed_cart_contents as $item ) {
                
                if ( $item['variation_id'] !== 0 ) {
                    $p_id = $item['variation_id'];
                } else {
                    $p_id = $item['product_id'];
                }
                
                
                if ( !in_array( $p_id, $clearedIds ) ) {
                    $temp = new MVVWB_Temp( $p_id );
                    $temp->clear( $session );
                    $clearedIds[] = $p_id;
                }
            
            }
        }
        
        return $return;
    }
    
    public function bookings_endpoint( $current_page )
    {
        $current_page = ( empty($current_page) ? 1 : absint( $current_page ) );
        $user_id = get_current_user_id();
        $per_page = 10;
        //      $now = new DateTime("now", new DateTimeZone(timezone_name_from_abbr("", get_option('gmt_offset') * HOUR_IN_SECONDS, false)));
        try {
            $now = new DateTime( "now", mvvwb_getTimeZone() );
        } catch ( Exception $e ) {
            $now = new DateTime( "now" );
        }
        $current_page = ( empty($current_page) ? 1 : absint( $current_page ) );
        $bookings = array();
        //        $bookings['today'] = MVVWB_Booking::getBookingsByUser($user_id, array(
        //            'order_by' => 'start_date',
        //            'order' => 'ASC',
        //            'date_after' => clone $now,
        //            'date_before' =>   $now->modify('+2 day'),
        //            'offset' => ($current_page - 1) * $per_page,
        //            'limit' => $per_page,
        //        ));
        $bookings['upcoming'] = MVVWB_Booking::getBookingsByUser( $user_id, array(
            'order_by'   => 'start_date',
            'order'      => 'ASC',
            'date_after' => $now,
            'offset'     => ($current_page - 1) * $per_page,
            'limit'      => $per_page,
        ) );
        $bookings['past'] = MVVWB_Booking::getBookingsByUser( $user_id, array(
            'order_by'    => 'start_date',
            'order'       => 'DESC',
            'date_before' => $now,
            'offset'      => ($current_page - 1) * $per_page,
            'limit'       => $per_page,
        ) );
        wc_get_template(
            'myaccount/bookings.php',
            array(
            'bookings' => $bookings,
            'page'     => $current_page,
            'per_page' => $per_page,
        ),
            'mvv_booking/',
            MVVWB_TEMPLATE_PATH
        );
    }
    
    public function account_menu_items( $items )
    {
        // Remove logout menu item.
        
        if ( array_key_exists( 'customer-logout', $items ) ) {
            $logout = $items['customer-logout'];
            unset( $items['customer-logout'] );
        }
        
        // Add bookings menu item.
        $items['bookings'] = __( 'Bookings', '' );
        // Add back the logout item.
        if ( isset( $logout ) ) {
            $items['customer-logout'] = $logout;
        }
        return $items;
    }
    
    //    public function get_product_price($price, $product)
    //    {
    //        $p_id = $product->get_id();
    //        $booking = wp_cache_get('mvvwb_object_' . $p_id);
    //        if (false === $booking) {
    //            $booking = new MVVWB_Booking_Item($p_id);
    //            wp_cache_set('mvvwb_object_' . $p_id, $booking);
    //        }
    //        if ($booking->isValid === false) {
    //            return $price;
    //        }
    //        return $booking->getPrice();
    //    }
    public function before_calculate_totals( $cart )
    {
        if ( is_admin() && !defined( 'DOING_AJAX' ) ) {
            return;
        }
        
        if ( method_exists( $cart, 'get_cart' ) ) {
            $cartContents = $cart->get_cart();
        } else {
            $cartContents = $cart->cart_contents;
        }
        
        $session = MVVWB_Customer::get_session();
        $clearedIds = [];
        foreach ( $cartContents as $key => $value ) {
            $price = 0.0;
            //            if (isset($cartContents[$key]['mvvwb_price'])) {
            //                continue;
            //            }
            
            if ( isset( $value[MVVWB_CART_ITEM_KEY] ) && is_array( $value[MVVWB_CART_ITEM_KEY] ) ) {
                $productId = $value['data']->get_id();
                $booking = new MVVWB_Booking_Item( $productId );
                if ( $booking->isValid === false ) {
                    return $price;
                }
                $bookingData = $value[MVVWB_CART_ITEM_KEY]['bookingData'];
                $bookingData['quantity'] = $value['quantity'];
                $booking->setBookingData( $bookingData );
                $bookingData = $booking->getBookingData();
                
                if ( !wp_doing_ajax() ) {
                    // checking this to avoid refreshing the timestamp of the temp data.
                    // term data time stamp can update only if the site loaded without ajax
                    $temp = new MVVWB_Temp( $productId );
                    if ( !in_array( $productId, $clearedIds ) ) {
                        $temp->clear( $session );
                    }
                    $clearedIds[] = $productId;
                    $TempData = [
                        'start'     => $bookingData['start'],
                        'end'       => $bookingData['end'],
                        'session'   => $session,
                        'quantity'  => $bookingData['quantity'],
                        'count'     => $bookingData['count'],
                        'resources' => $bookingData['resources'],
                    ];
                    $temp->insert( $TempData );
                }
                
                $price = $booking->calculateCost( 'edit', false );
                //                $cartContents[$key]['mvvwb_price'] = $price;
                //                if (method_exists($cart, 'set_cart_contents')) {
                //                    $cart->set_cart_contents($cartContents);
                //                } else {
                //                    $cart->cart_contents = $cartContents;
                //                }
                $multiCurrency = new MVVWB_Compatibility_MC();
                $value['data']->set_price( $multiCurrency->cartConvert( $price['total'] ) );
            }
        
        }
        
        if ( !wp_doing_ajax() ) {
            
            if ( method_exists( $cart, 'get_removed_cart_contents' ) ) {
                $removed_cart_contents = $cart->get_removed_cart_contents();
            } else {
                $removed_cart_contents = [];
            }
            
            foreach ( $removed_cart_contents as $item ) {
                
                if ( $item['variation_id'] !== 0 ) {
                    $p_id = $item['variation_id'];
                } else {
                    $p_id = $item['product_id'];
                }
                
                
                if ( !in_array( $p_id, $clearedIds ) ) {
                    $temp = new MVVWB_Temp( $p_id );
                    $temp->clear( $session );
                    $clearedIds[] = $p_id;
                }
            
            }
        }
        
        remove_action( 'woocommerce_before_calculate_totals', array( $this, 'before_calculate_totals' ), 9 );
    }
    
    public function enqueue_styles()
    {
        wp_register_style(
            $this->_token . '-style',
            esc_url( $this->assets_url ) . 'css/style.css',
            array(),
            $this->_version
        );
        wp_enqueue_style( $this->_token . '-style' );
    }
    
    public function enqueue_scripts()
    {
        $i18n = new MVVWB_I18n();
        include 'flatpicker.php';
        
        if ( $i18n->isActive() ) {
            $locale = $i18n->currentLang;
        } else {
            $locale = strtolower( get_locale() );
        }
        
        wp_enqueue_script(
            $this->_token . '-front',
            esc_url( $this->assets_url ) . 'js/front.js',
            [],
            $this->_version,
            true
        );
        $flatPickerLocale = false;
        
        if ( in_array( $locale, $supportedLangs ) ) {
            $flatPickerLocale = [
                'code' => $locale,
                'src'  => esc_url( $this->assets_url ) . 'js/flatpicker/' . $locale . '.js',
            ];
            //            wp_enqueue_script($this->_token . '-flatpicker-lang-' . $locale[0], esc_url($this->assets_url) . 'js/flatpicker/' . $locale . '.js', [], $this->_version, true);
        } else {
            $locale = preg_split( '/[-_]/', $locale );
            
            if ( isset( $locale[0] ) && in_array( $locale[0], $supportedLangs ) ) {
                $flatPickerLocale = [
                    'code' => $locale[0],
                    'src'  => esc_url( $this->assets_url ) . 'js/flatpicker/' . $locale[0] . '.js',
                ];
                //                $flatPickerLocale = esc_url($this->assets_url) . 'js/flatpicker/' . $locale[0] . '.js';
                //                wp_enqueue_script($this->_token . '-flatpicker-lang-' . $locale[0], esc_url($this->assets_url) . 'js/flatpicker/' . $locale[0] . '.js', [], $this->_version, true);
            }
        
        }
        
        $initTriggers = mvvwb_getConfig( 'initTriggers', [] );
        if ( !is_array( $initTriggers ) && !empty($initTriggers) ) {
            $initTriggers = [ $initTriggers ];
        }
        $initTriggers = array_unique( array_merge( $initTriggers, array(
            'qv_loader_stop',
            'quick_view_pro:load',
            'woosq_loaded',
            'xt-woo-quick-view-displayed'
        ) ) );
        wp_localize_script( $this->_token . '-front', $this->_token . '_config', array(
            'api_nonce'    => wp_create_nonce( 'wp_rest' ),
            'root'         => rest_url( $this->_token . '/v1/' ),
            'lang'         => ( $i18n->isActive() && !$i18n->isDefault() ? $i18n->currentLang : false ),
            'locale'       => $flatPickerLocale,
            'config'       => [],
            'initTriggers' => $initTriggers,
            'dateFormat'   => __( get_option( 'date_format' ) ),
            'timeFormat'   => __( get_option( 'time_format' ) ),
        ) );
    }
    
    public function add_to_cart_text( $text, $_product )
    {
        $booking = new MVVWB_Booking_Item( $_product->get_id() );
        
        if ( $booking->isValid === false ) {
            return $text;
        } else {
            
            if ( $booking->requiresConfirmation() ) {
                return mvvwb_getConfig( 'button.checkAvail', 'Check Availability' );
            } else {
                return mvvwb_getConfig( 'button.bookNow', 'Book Now' );
            }
        
        }
    
    }
    
    public function add_to_cart_quantity( $quantity, $product_id )
    {
        $booking = new MVVWB_Booking_Item( $product_id );
        if ( $booking->isValid === true && $booking->isQuantityEnabled() && isset( $_POST['mvvwb_quantity'] ) ) {
            $quantity = sanitize_text_field( $_POST['mvvwb_quantity'] );
        }
        return $quantity;
    }
    
    public function quantity_input_args( $args, $product )
    {
        $booking = new MVVWB_Booking_Item( $product->get_id() );
        
        if ( $booking->isValid === true && $booking->isQuantityEnabled() ) {
            $args['min_value'] = 1;
            $args['max_value'] = 1;
        }
        
        return $args;
    }
    
    public function cart_item_quantity( $product_quantity, $cart_item_key, $cart_item )
    {
        if ( isset( $cart_item[MVVWB_CART_ITEM_KEY] ) ) {
            $product_quantity = sprintf( '%d <input type="hidden" name="cart[%s][qty]" value="1" />', $cart_item['quantity'], $cart_item_key );
        }
        return $product_quantity;
    }
    
    public function is_sold_individually( $value, $_product )
    {
        $booking = new MVVWB_Booking_Item( $_product->get_id() );
        
        if ( $booking->isValid === false || $booking->isQuantityEnabled() ) {
            return $value;
        } else {
            return true;
        }
    
    }
    
    public function create_order_line_item(
        $item,
        $cart_item_key,
        $values,
        $order
    )
    {
        if ( !isset( $values[MVVWB_CART_ITEM_KEY] ) || empty($values[MVVWB_CART_ITEM_KEY]) ) {
            return;
        }
        $product = ( is_callable( array( $item, 'get_product' ) ) ? $item->get_product() : false );
        $item->add_meta_data( MVVWB_ORDER_ITEM_KEY, $values[MVVWB_CART_ITEM_KEY]['bookingData'] );
        $item->add_meta_data( MVVWB_ORDER_ITEM_KEY . '_costs', $values[MVVWB_CART_ITEM_KEY]['costs'] );
    }
    
    public function update_order_meta( $order_id )
    {
        //$order_id it can be order object some time
        $order = wc_get_order( $order_id );
        $items = $order->get_items();
        if ( is_array( $items ) ) {
            foreach ( $items as $item_id => $item ) {
                $this->update_order_item( $item, $order->get_id() );
            }
        }
    }
    
    public function update_order_item( $item, $order_id )
    {
        $meta_data = $item->get_meta( MVVWB_ORDER_ITEM_KEY );
        $costs = $item->get_meta( MVVWB_ORDER_ITEM_KEY . '_costs' );
        
        if ( $meta_data ) {
            $product_id = $item->get_product_id();
            // get from $item
            $variation_id = $item->get_variation_id();
            // get from $item
            $booking = new MVVWB_Booking_Item( $product_id, $variation_id );
            $bookingData = $meta_data;
            $bookingData['quantity'] = $item->get_quantity();
            $bookingData['costs'] = $costs;
            $booking->setBookingData( $bookingData );
            //‌‌$item->get_quantity();
            $bookingId = $booking->addBooking( $order_id, $item );
            MVVWB_Transient::clearTransByProduct( $product_id );
            $item->update_meta_data( MVVWB_ORDER_BOOKING_ID_KEY, $bookingId );
        }
    
    }
    
    public function cancel_booking()
    {
    }
    
    public function add_to_cart_validation(
        $passed,
        $product_id,
        $quantity = 1,
        $variation_id = false
    )
    {
        $booking = new MVVWB_Booking_Item( $product_id, $variation_id );
        if ( $passed === false ) {
            return $passed;
        }
        
        if ( $booking->isValid ) {
            
            if ( !isset( $_REQUEST['mvvwb_start'] ) ) {
                $this->add_cart_message( mvvwb_getConfig( 'messages.invalidSelection', 'Invalid Selection' ) );
                return false;
            }
            
            
            if ( $booking->isDateRangeEnabled() && !isset( $_REQUEST['mvvwb_end'] ) ) {
                $this->add_cart_message( mvvwb_getConfig( 'messages.invalidSelection', 'Invalid Selection' ) );
                return false;
            }
            
            $prePare = $booking->prepareData( [], true );
            
            if ( !$prePare ) {
                $this->add_cart_message( mvvwb_getConfig( 'messages.invalidSelection', 'Invalid Selection' ) );
                return false;
            }
            
            $isAvailable = $booking->checkAvailability();
            
            if ( $isAvailable === false || isset( $isAvailable['status'] ) && $isAvailable['status'] === false ) {
                $this->add_cart_message( mvvwb_getConfig( 'messages.notAvailable', 'Selected date range is not available' ) );
                return false;
            }
        
        }
        
        return $passed;
    }
    
    public function add_cart_message( $message, $type = "error" )
    {
        wc_add_notice( $message, $type );
    }
    
    public function submit_booking( $cart_item_data, $product_id, $variation_id )
    {
        $booking = new MVVWB_Booking_Item( $product_id, $variation_id );
        
        if ( $booking->isValid ) {
            $prePare = $booking->prepareData( [], true );
            
            if ( !$prePare ) {
                $this->add_cart_message( mvvwb_getConfig( 'messages.invalidSelection', 'Invalid Selection' ) );
                return;
            }
            
            $isAvailable = $booking->checkAvailability();
            
            if ( $isAvailable === false || isset( $isAvailable['status'] ) && $isAvailable['status'] === false ) {
                $this->add_cart_message( mvvwb_getConfig( 'messages.notAvailable', 'Selected date range is not available' ) );
                return;
            }
            
            
            if ( !isset( $cart_item_data[MVVWB_CART_ITEM_KEY] ) ) {
                // if already set by order again option
                $cost = $booking->calculateCost( 'edit', false );
                // calling this here to update the resources list and price
                $cart_item_data[MVVWB_CART_ITEM_KEY] = $booking->getCartData( true );
                //
                $cart_item_data[MVVWB_CART_ITEM_KEY]['costs'] = $cost;
                $bookingData = $booking->getBookingData();
                //
                $temp = new MVVWB_Temp( $product_id );
                $session = MVVWB_Customer::get_session();
                $TempData = [
                    'start'     => $bookingData['start'],
                    'end'       => $bookingData['end'],
                    'session'   => $session,
                    'quantity'  => $bookingData['quantity'],
                    'count'     => $bookingData['count'],
                    'resources' => $bookingData['resources'],
                ];
                $temp->insert( $TempData );
            }
        
        }
        
        return $cart_item_data;
    }
    
    public function get_item_data( $item_data, $cart_item )
    {
        if ( !is_array( $item_data ) ) {
            $item_data = array();
        }
        
        if ( isset( $cart_item[MVVWB_CART_ITEM_KEY] ) ) {
            $bookingData = $cart_item[MVVWB_CART_ITEM_KEY]['bookingData'];
            $labels = $cart_item[MVVWB_CART_ITEM_KEY]['labels'];
            $dateFormatView = get_option( 'date_format' );
            $item_data[] = array(
                'name'  => $labels['dateStart'],
                'key'   => $labels['dateStart'],
                'value' => mvvwb_date( $dateFormatView, $bookingData['start']->getTimeStamp() ),
            );
            if ( isset( $bookingData['end'] ) && $bookingData['end'] !== false ) {
                $item_data[] = array(
                    'name'  => $labels['dateEnd'],
                    'key'   => $labels['dateEnd'],
                    'value' => mvvwb_date( $dateFormatView, $bookingData['end']->getTimeStamp() ),
                );
            }
            
            if ( isset( $bookingData['timeStart'] ) && $bookingData['timeStart'] !== false ) {
                $dt = new DateTime();
                $dt->setTimeZone( mvvwb_getTimeZone() );
                $dt->setTime( 0, $bookingData['timeStart'], 0 );
                $item_data[] = array(
                    'name'  => $labels['timeStart'],
                    'key'   => $labels['timeStart'],
                    'value' => mvvwb_date( get_option( 'time_format' ), $dt->getTimeStamp() ),
                );
            }
            
            if ( isset( $bookingData['duration'] ) && $bookingData['duration'] !== false ) {
                $item_data[] = array(
                    'name'  => $labels['duration'],
                    'key'   => $labels['duration'],
                    'value' => $bookingData['duration'],
                );
            }
            if ( isset( $bookingData['persons'] ) && $bookingData['persons'] !== false ) {
                $item_data[] = array(
                    'name'  => $labels['persons'],
                    'key'   => $labels['persons'],
                    'value' => $bookingData['persons'],
                );
            }
            if ( isset( $bookingData['adult'] ) && $bookingData['adult'] !== false ) {
                $item_data[] = array(
                    'name'  => $labels['adult'],
                    'key'   => $labels['adult'],
                    'value' => $bookingData['adult'],
                );
            }
            if ( isset( $bookingData['children'] ) && $bookingData['children'] !== false ) {
                $item_data[] = array(
                    'name'  => $labels['children'],
                    'key'   => $labels['children'],
                    'value' => $bookingData['children'],
                );
            }
            
            if ( isset( $bookingData['resources'] ) && count( $bookingData['resources'] ) ) {
                $value = '';
                foreach ( $bookingData['resources'] as $res ) {
                    if ( $res['hidden'] ) {
                        continue;
                    }
                    $value .= '<span>' . $res['name'];
                    $value .= '</span> ' . (( isset( $res['quantity'] ) && $res['quantity'] ? 'x' . $res['quantity'] : '' ));
                    
                    if ( isset( $res['price'] ) && $res['price'] > 0 ) {
                        $multiCurrency = new MVVWB_Compatibility_MC();
                        $value = $value . ' (' . wc_price( $multiCurrency->convert( $res['price'] ) ) . ')';
                    }
                    
                    $value .= ' <br />';
                }
                $item_data[] = array(
                    'name'  => 'Resources',
                    'key'   => 'Resources',
                    'value' => $value,
                );
            }
        
        }
        
        return $item_data;
    }
    
    public function booking_display( $item_id, $item, $order )
    {
        $booking_ids = MVVWB_Booking::getBookingByItemIds( $item_id );
        $this->view( 'booking-display', [
            'booking_ids' => $booking_ids,
        ] );
    }
    
    public function before_add_to_cart_button()
    {
        global  $post ;
        $booking = new MVVWB_Booking_Item( $post->ID );
        if ( !$booking->isValid ) {
            return;
        }
        $data = [
            'postId'      => $post->ID,
            'config'      => $booking->getConfig( 'frontEnd' ),
            'resources'   => $booking->getResources( 'frontEnd' ),
            'global'      => mvvwb_getAllConfig(),
            'timeZone'    => '',
            'unAvailable' => $booking->getSlotes(),
        ];
        echo  '<div data-conf=\'' . htmlspecialchars( wp_json_encode( $data ), ENT_QUOTES ) . '\'  class="' . MVVWB_TOKEN . '_booking_panel"></div>' ;
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

}