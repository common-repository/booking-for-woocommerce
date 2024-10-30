<?php

if ( !function_exists( 'mvvwb_autoloader' ) ) {
    function mvvwb_autoloader( $class_name )
    {
        
        if ( 0 === strpos( $class_name, 'MVVWB_Email' ) ) {
            $classes_dir = realpath( plugin_dir_path( MVVWB___FILE__ ) ) . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR . 'emails' . DIRECTORY_SEPARATOR;
            $class_file = 'class-' . str_replace( '_', '-', strtolower( $class_name ) ) . '.php';
            require_once $classes_dir . $class_file;
        } else {
            
            if ( 0 === strpos( $class_name, 'MVVWB' ) ) {
                $classes_dir = realpath( plugin_dir_path( MVVWB___FILE__ ) ) . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR;
                $class_file = 'class-' . str_replace( '_', '-', strtolower( $class_name ) ) . '.php';
                require_once $classes_dir . $class_file;
            }
        
        }
    
    }

}
if ( !function_exists( 'mvvwb_popDates' ) ) {
    function mvvwb_popDates( $dates, $start, $end )
    {
        if ( count( $dates ) ) {
            $dates = array_filter( $dates, function ( $va ) use( $start, $end ) {
                if ( $va <= $end && $va >= $start ) {
                    return false;
                }
                return true;
            } );
        }
        return $dates;
    }

}
if ( !function_exists( 'mvvwb_popDatesByDays' ) ) {
    function mvvwb_popDatesByDays( $dates, $days )
    {
        if ( count( $dates ) ) {
            $dates = array_filter( $dates, function ( $va ) use( $days ) {
                if ( in_array( date( 'D', $va ), $days ) ) {
                    return false;
                }
                return true;
            } );
        }
        return $dates;
    }

}
if ( !function_exists( 'mvvwb_booking_status' ) ) {
    function mvvwb_booking_status( $type = 'reserve' )
    {
        
        if ( $type == 'all' ) {
            $status = [
                'unpaid',
                'pending-confirmation',
                'confirmed',
                'paid',
                'complete',
                'cancelled'
            ];
        } else {
            
            if ( $type == 'reserve' ) {
                $status = [
                    'unpaid',
                    'pending-confirmation',
                    'confirmed',
                    'paid',
                    'complete'
                ];
            } else {
                $status = [
                    'unpaid',
                    'pending-confirmation',
                    'confirmed',
                    'paid',
                    'complete'
                ];
            }
        
        }
        
        return $status;
    }

}
if ( !function_exists( 'mvvwb_getBookingDetails' ) ) {
    function mvvwb_getBookingDetails( $item_id )
    {
        $booking_ids = MVVWB_Booking::getBookingByItemIds( $item_id );
        
        if ( $booking_ids ) {
            $details = [];
            foreach ( $booking_ids as $booking_id ) {
                $booking = new MVVWB_Booking( $booking_id );
                $details[] = $booking->getDetails();
                //$details['booking']['start']
                //$details['booking']['end']
            }
            return $details;
        }
        
        return false;
    }

}
if ( !function_exists( 'mvvwb_dateFormatPhpToJs' ) ) {
    function mvvwb_dateFormatPhpToJs( $str )
    {
        return str_replace( 's', '', str_replace( 'S', '', $str ) );
    }

}
if ( !function_exists( 'mvvwb_dateStringToObject' ) ) {
    function mvvwb_dateStringToObject( $start, $end = false )
    {
        $timeZoneName = timezone_name_from_abbr( "", get_option( 'gmt_offset' ) * HOUR_IN_SECONDS, false );
        $start = new DateTime( $start, mvvwb_getTimeZone() );
        if ( $end ) {
            $end = new DateTime( $end, mvvwb_getTimeZone() );
        }
        return [
            'start'    => $start,
            'end'      => $end,
            'duration' => '',
        ];
    }

}
if ( !function_exists( 'mvvwb_bookings_get_status_label' ) ) {
    function mvvwb_bookings_get_status_label( $status )
    {
        $statuses = array(
            'unpaid'               => __( 'Unpaid', 'booking-for-woocommerce' ),
            'pending-confirmation' => __( 'Pending Confirmation', 'booking-for-woocommerce' ),
            'confirmed'            => __( 'Confirmed', 'booking-for-woocommerce' ),
            'paid'                 => __( 'Paid', 'booking-for-woocommerce' ),
            'cancelled'            => __( 'Cancelled', 'booking-for-woocommerce' ),
            'complete'             => __( 'Complete', 'booking-for-woocommerce' ),
        );
        return ( array_key_exists( $status, $statuses ) ? $statuses[$status] : $status );
    }

}
function mvvwb_booking_order_requires_confirmation( $order )
{
    $requires = false;
    if ( $order ) {
        foreach ( $order->get_items() as $item ) {
            
            if ( mvvwb_booking_requires_confirmation( $item['product_id'] ) ) {
                $requires = true;
                break;
            }
        
        }
    }
    return $requires;
}

function mvvwb_cart_requires_confirmation()
{
    $requires = false;
    if ( !empty(WC()->cart->cart_contents) ) {
        foreach ( WC()->cart->cart_contents as $item ) {
            
            if ( mvvwb_booking_requires_confirmation( $item['product_id'] ) ) {
                $requires = true;
                break;
            }
        
        }
    }
    return $requires;
}

function mvvwb_price( $price )
{
    extract( array(
        'ex_tax_label'       => false,
        'currency'           => '',
        'decimal_separator'  => wc_get_price_decimal_separator(),
        'thousand_separator' => wc_get_price_thousand_separator(),
        'decimals'           => wc_get_price_decimals(),
        'price_format'       => get_woocommerce_price_format(),
    ) );
    
    if ( $decimal_separator ) {
        $decimal_separator = trim( $decimal_separator );
        $price = str_replace( $decimal_separator, '.', $price );
    }
    
    //$unformatted_price = $price;
    $negative = $price < 0;
    $price = floatval( ( $negative ? $price * -1 : $price ) );
    //
    $price = number_format(
        $price,
        $decimals,
        $decimal_separator,
        $thousand_separator
    );
    $return = html_entity_decode( (( $negative ? '-' : '' )) . sprintf( $price_format, get_woocommerce_currency_symbol( $currency ), $price ) );
    return $return;
}

function mvvwb_daysDiff( $start, $end, $isAllDayBooking = false )
{
    $interval = $start->diff( $end );
    if ( $interval->invert ) {
        return false;
    }
    /* if ($interval->format('%a') == 0) { omiting this codes, we just returing adding 1 with any diff, if same day, it will be 1,
             //$end->modify('+1 day'); removed this as it affects the source $end object
    
         }*/
    $interval = $start->diff( $end );
    
    if ( $isAllDayBooking ) {
        return $interval->format( '%a' ) + 1;
    } else {
        return $interval->format( '%a' );
    }

}

function mvvwb_booking_requires_confirmation( $id )
{
    $item = new MVVWB_Booking_Item( $id );
    if ( $item->isValid && $item->requiresConfirmation() ) {
        return true;
    }
    return false;
}

function mvvwb_timezone_string( $offset )
{
    $offset = (double) $offset;
    $hours = (int) $offset;
    $minutes = $offset - $hours;
    $sign = ( $offset < 0 ? '-' : '+' );
    $abs_hour = abs( $hours );
    $abs_mins = abs( $minutes * 60 );
    $tz_offset = sprintf(
        '%s%02d:%02d',
        $sign,
        $abs_hour,
        $abs_mins
    );
    return $tz_offset;
}

function mvvwb_date( $format, $datetime )
{
    // wrapper for wp_date in 5.3
    if ( function_exists( 'wp_date' ) ) {
        return wp_date( $format, $datetime );
    }
    $dt = new DateTime( '@' . $datetime );
    $offset = mvvwb_getTimeZone()->getOffset( $dt );
    return date_i18n( $format, $datetime + $offset );
}

function mvvwb_getTimeZone()
{
    if ( function_exists( 'wp_timezone' ) ) {
        return wp_timezone();
    }
    $timeZoneName = timezone_name_from_abbr( "", get_option( 'gmt_offset' ) * HOUR_IN_SECONDS, false );
    return new DateTimeZone( $timeZoneName );
}

if ( !function_exists( 'mvvwb_getConfig' ) ) {
    function mvvwb_getAllConfig( $sec = false )
    {
        $default = [
            'labels'     => [
            'dateStart'            => 'Start Date',
            'dateEnd'              => 'End Date',
            'dateRange'            => 'Date Range',
            'timeStart'            => 'Start Time',
            'duration'             => 'Duration',
            'persons'              => 'Persons',
            'quantity'             => 'Quantity',
            'total'                => 'Total',
            'adult'                => 'Adult',
            'children'             => 'Children',
            'bookingPrice'         => 'Booking Price',
            'fixedCharge'          => 'Fixed Charge',
            'bookingPricePersons'  => 'Booking Price Persons',
            'selectedRange'        => 'Selected:',
            'bookingPriceAdult'    => 'Price (Adult)',
            'bookingPriceChildren' => 'Price (Children)',
        ],
            'resource'   => [
            'resourcesTitle' => 'Resources',
            'resPriPerU'     => 'Per Unit',
            'resPriPerP'     => 'Per Person',
            'resPriPerPpU'   => 'Per unit Per Person',
            'resPriOnce'     => 'One Time',
        ],
            'button'     => [
            'bookNow'    => 'Book Now',
            'checkAvail' => 'Check Availability',
        ],
            'messages'   => [
            'notAvailable'         => 'Selected date range is not available',
            'invalidSelection'     => 'Invalid Selection',
            'availQuantityS'       => 'Only %d Quantity is available',
            'availQuantityP'       => 'Only %d Quantities are available',
            'resourceNotAvailable' => 'Resource %s Not Available',
            'minBookable'          => 'Minimum %s slots to be selected',
            'maxBookable'          => 'Maximum %s slots can be selected',
        ],
            'timePicker' => [
            'format' => '12',
        ],
        ];
        $option = get_option( 'mvvwb_config', $default );
        foreach ( $default as $key => $val ) {
            
            if ( !isset( $option[$key] ) ) {
                $option[$key] = $val;
                continue;
            }
            
            foreach ( $val as $k => $v ) {
                if ( !isset( $option[$key][$k] ) ) {
                    $option[$key][$k] = $v;
                }
            }
        }
        if ( !isset( $option['conf'] ) ) {
            $option['conf'] = [];
        }
        $option = apply_filters( 'mvvwb_config_filter', $option );
        if ( $sec !== false ) {
            return ( isset( $option[$sec] ) ? $option[$sec] : [] );
        }
        return $option;
    }

}
if ( !function_exists( 'mvvwb_getConfig' ) ) {
    function mvvwb_getConfig( $key, $default = false )
    {
        $config = mvvwb_getAllConfig();
        $keys = explode( '.', $key );
        $configValue = $default;
        
        if ( isset( $keys[0] ) && isset( $config[$keys[0]] ) ) {
            $config = $config[$keys[0]];
            if ( isset( $keys[1] ) && isset( $config[$keys[1]] ) ) {
                $configValue = $config[$keys[1]];
            }
        }
        
        $configValue = apply_filters( 'mvvwb_get_config', $configValue, $key );
        return $configValue;
    }

}
if ( !function_exists( 'is_mvvwb_booking_product' ) ) {
    function is_mvvwb_booking_product( $product_id )
    {
        return 'yes' === get_post_meta( $product_id, '_mvvwb_bookable', true );
    }

}