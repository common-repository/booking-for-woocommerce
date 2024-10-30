<?php

if ( !defined( 'ABSPATH' ) ) {
    exit;
}
class MVVWB_Booking
{
    public  $bookingId = false ;
    public  $timeZoneName = 'UTC' ;
    public  $timeZone = false ;
    private  $orderId ;
    function __construct( $bookingId )
    {
        $this->bookingId = $bookingId;
        $timeZoneOffset = get_option( 'gmt_offset' );
        $this->timeZone = mvvwb_getTimeZone();
        $this->timeZoneName = $this->timeZone->getName();
    }
    
    public static function getBookingsByUser( $user_id, $args )
    {
        $args = wp_parse_args( $args, array(
            'status'       => array(
            'unpaid',
            'pending-confirmation',
            'confirmed',
            'paid',
            'cancelled',
            'complete'
        ),
            'limit'        => -1,
            'offset'       => 0,
            'order_by'     => 'date_created',
            'order'        => 'DESC',
            'date_before'  => false,
            'date_after'   => false,
            'date_between' => array(
            'start' => false,
            'end'   => false,
        ),
        ) );
        $booking_ids = self::getBookingIdsByCustomer( $user_id, $args );
        return array_map( function ( $id ) {
            return new MVVWB_Booking( $id );
        }, $booking_ids );
    }
    
    //get_booking_ids_by
    public static function getBookingIdsByCustomer( $pId, $args = array() )
    {
        global  $wpdb ;
        $args = wp_parse_args( $args, array(
            'status'       => false,
            'limit'        => -1,
            'offset'       => 0,
            'order_by'     => 'date_created',
            'order'        => 'DESC',
            'date_before'  => false,
            'date_after'   => false,
            'date_between' => array(
            'start' => false,
            'end'   => false,
        ),
        ) );
        $query = self::generateSearchQuery( $pId, $args, 'customer' );
        return array_filter( wp_parse_id_list( $wpdb->get_col( $query ) ) );
    }
    
    public static function generateSearchQuery( $id, $args, $item )
    {
        global  $wpdb ;
        $join = '';
        $where = "WHERE p.post_type = '" . MVVWB_BOOKING_PT . "' AND ";
        
        if ( !empty($id) ) {
            if ( !is_array( $id ) ) {
                $id = [ $id ];
            }
            
            if ( $item == 'product' ) {
                $join = " SELECT p.ID FROM {$wpdb->posts} p LEFT JOIN {$wpdb->postmeta} _mvvwb_product_id ON p.ID = _mvvwb_product_id.post_id AND _mvvwb_product_id.meta_key = '_mvvwb_product_id' ";
                $where .= "_mvvwb_product_id.meta_value IN ('" . implode( "','", $id ) . "') AND ";
            } else {
                
                if ( $item == 'order' ) {
                    $join = " SELECT p.ID FROM {$wpdb->posts} p LEFT JOIN {$wpdb->postmeta} _mvvwb_order_id ON p.ID = _mvvwb_order_id.post_id AND _mvvwb_order_id.meta_key = '_mvvwb_order_id' ";
                    $where .= "_mvvwb_order_id.meta_value IN ('" . implode( "','", $id ) . "') AND ";
                } else {
                    
                    if ( $item == 'customer' ) {
                        $join = " SELECT p.ID FROM {$wpdb->posts} p LEFT JOIN {$wpdb->postmeta} _mvvwb_booking_customer_id ON p.ID = _mvvwb_booking_customer_id.post_id AND _mvvwb_booking_customer_id.meta_key = '_mvvwb_booking_customer_id' ";
                        $where .= "_mvvwb_booking_customer_id.meta_value IN ('" . implode( "','", $id ) . "') AND ";
                    } else {
                        
                        if ( $item == 'resource' ) {
                            $join = " SELECT p.ID FROM {$wpdb->posts} p LEFT JOIN {$wpdb->postmeta} _mvvwb_resource_id ON p.ID = _mvvwb_resource_id.post_id AND _mvvwb_resource_id.meta_key = '_mvvwb_resource_id' ";
                            $where .= "_mvvwb_resource_id.meta_value IN ('" . implode( "','", $id ) . "') AND ";
                        }
                    
                    }
                
                }
            
            }
        
        } else {
            $join = " SELECT p.ID FROM {$wpdb->posts} p ";
        }
        
        $joinMeta = [];
        $saveAsGmt = mvvwb_getConfig( 'settings.save_as_gmt', true );
        if ( $args['status'] ) {
            $where .= "p.post_status IN ('" . implode( "','", $args['status'] ) . "') AND ";
        }
        
        if ( !empty($args['date_between']['start']) && !empty($args['date_between']['end']) ) {
            $start = clone $args['date_between']['start'];
            $end = clone $args['date_between']['end'];
            
            if ( $saveAsGmt ) {
                $start->setTimezone( new DateTimeZone( 'GMT' ) );
                $end->setTimezone( new DateTimeZone( 'GMT' ) );
            }
            
            $joinMeta[] = '_mvvwb_booking_start';
            $joinMeta[] = '_mvvwb_booking_end';
            $joinMeta[] = '_mvvwb_all_day';
            $join .= "LEFT JOIN {$wpdb->postmeta} _mvvwb_booking_start ON p.ID = _mvvwb_booking_start.post_id AND _mvvwb_booking_start.meta_key = '_mvvwb_booking_start' ";
            $join .= "LEFT JOIN {$wpdb->postmeta} _mvvwb_booking_end ON p.ID = _mvvwb_booking_end.post_id AND _mvvwb_booking_end.meta_key = '_mvvwb_booking_end' ";
            $join .= "LEFT JOIN {$wpdb->postmeta} _mvvwb_all_day ON p.ID = _mvvwb_all_day.post_id AND _mvvwb_all_day.meta_key = '_mvvwb_all_day' ";
            $where .= "( ( _mvvwb_booking_start.meta_value <= '" . esc_sql( $end->format( 'YmdHis' ) ) . "' AND ";
            $where .= "_mvvwb_booking_end.meta_value >= '" . esc_sql( $start->format( 'YmdHis' ) ) . "' AND _mvvwb_all_day.meta_value = '0') OR ";
            $where .= " ( _mvvwb_booking_start.meta_value <= '" . esc_sql( $end->format( 'Ymd000000' ) ) . "' AND ";
            $where .= " _mvvwb_booking_end.meta_value >= '" . esc_sql( $start->format( 'Ymd000000' ) ) . "' AND  _mvvwb_all_day.meta_value = '1') ) AND ";
        }
        
        
        if ( !empty($args['date_after']) ) {
            $date_after = clone $args['date_after'];
            if ( $saveAsGmt ) {
                $date_after->setTimezone( new DateTimeZone( 'GMT' ) );
            }
            
            if ( !in_array( '_mvvwb_booking_start', $joinMeta ) ) {
                $joinMeta[] = '_mvvwb_booking_start';
                $join .= "LEFT JOIN {$wpdb->postmeta} _mvvwb_booking_start ON p.ID = _mvvwb_booking_start.post_id AND _mvvwb_booking_start.meta_key = '_mvvwb_booking_start' ";
            }
            
            $where .= "_mvvwb_booking_start.meta_value >= '" . esc_sql( $date_after->format( 'YmdHis' ) ) . "' AND ";
        }
        
        
        if ( !empty($args['date_before']) ) {
            $date_before = clone $args['date_before'];
            if ( $saveAsGmt ) {
                $date_before->setTimezone( new DateTimeZone( 'GMT' ) );
            }
            
            if ( !in_array( '_mvvwb_booking_end', $joinMeta ) ) {
                $joinMeta[] = '_mvvwb_booking_end';
                $join .= "LEFT JOIN {$wpdb->postmeta} _mvvwb_booking_end ON p.ID = _mvvwb_booking_end.post_id AND _mvvwb_booking_end.meta_key = '_mvvwb_booking_end' ";
            }
            
            $where .= "_mvvwb_booking_end.meta_value <= '" . esc_sql( $date_before->format( 'YmdHis' ) ) . "' AND ";
        }
        
        
        if ( !empty($args['order_by']) ) {
            switch ( $args['order_by'] ) {
                case 'date_created':
                    $args['order_by'] = 'p.post_date';
                    break;
                case 'start_date':
                    
                    if ( !in_array( '_mvvwb_booking_start', $joinMeta ) ) {
                        $joinMeta[] = '_mvvwb_booking_start';
                        $join .= "LEFT JOIN {$wpdb->postmeta} _mvvwb_booking_start ON p.ID = _mvvwb_booking_start.post_id AND _mvvwb_booking_start.meta_key = '_mvvwb_booking_start' ";
                    }
                    
                    $args['order_by'] = '_mvvwb_booking_start.meta_value';
                    break;
            }
            $order = ' ORDER BY ' . $args['order_by'] . ' ' . $args['order'];
        } else {
            $order = '';
        }
        
        
        if ( $args['limit'] > 0 ) {
            $limit = ' LIMIT ' . $args['offset'] . ',' . $args['limit'];
        } else {
            $limit = '';
        }
        
        $where = trim( $where, 'AND ' );
        return "{$join} {$where} {$order} {$limit};";
    }
    
    //    public static function get_booking_ids_by($filters = array())
    //    {
    //        global $wpdb;
    //
    //        $filters = wp_parse_args(
    //            $filters,
    //            array(
    //                'object_id' => 0,
    //                'object_type' => 'product',
    //                'status' => false,
    //                'limit' => -1,
    //                'offset' => 0,
    //                'order_by' => 'date_created',
    //                'order' => 'DESC',
    //                'date_before' => false,
    //                'date_after' => false,
    //                'date_between' => array(
    //                    'start' => false,
    //                    'end' => false,
    //                ),
    //            )
    //        );
    //        $saveAsGmt = mvvwb_getConfig('settings.save_as_gmt', true);
    //
    //        $meta_keys = array();
    //        $query_where = array('WHERE 1=1', "p.post_type = '" . MVVWB_BOOKING_PT . "'");
    //        $filters['object_id'] = array_filter(wp_parse_id_list(is_array($filters['object_id']) ? $filters['object_id'] : array($filters['object_id'])));
    //
    //        if (!empty($filters['object_id'])) {
    //            switch ($filters['object_type']) {
    //                case 'product':
    //                    $meta_keys[] = '_mvvwb_product_id';
    //                    $query_where[] = "_mvvwb_product_id.meta_value IN ('" . implode("','", array_map('esc_sql', $filters['object_id'])) . "')";
    //                    break;
    //                case 'order':
    //                    $meta_keys[] = '_mvvwb_order_id';
    //                    $query_where[] = "_mvvwb_order_id.meta_value IN ('" . implode("','", array_map('esc_sql', $filters['object_id'])) . "')";
    //                    break;
    //                case 'resource':
    //                    $meta_keys[] = '_mvvwb_resource_id';
    //                    $query_where[] = "_mvvwb_resource_id.meta_value IN ('" . implode("','", array_map('esc_sql', $filters['object_id'])) . "')";
    //                    break;
    //
    //                case 'customer':
    //                    $meta_keys[] = '_mvvwb_booking_customer_id';
    //                    $query_where[] = "_mvvwb_booking_customer_id.meta_value IN ('" . implode("','", array_map('esc_sql', $filters['object_id'])) . "')";
    //                    break;
    //            }
    //        }
    //
    //        if (!empty($filters['status'])) {
    //            $query_where[] = "p.post_status IN ('" . implode("','", $filters['status']) . "')";
    //        }
    //
    //
    //        if (!empty($filters['date_between']['start']) && !empty($filters['date_between']['end'])) {
    //            $start = clone $filters['date_between']['start'];
    //            $end = clone $filters['date_between']['end'];
    //            if ($saveAsGmt) {
    //                $start->setTimezone(new DateTimeZone('GMT'));
    //                $end->setTimezone(new DateTimeZone('GMT'));
    //            }
    //
    //            $meta_keys[] = '_mvvwb_booking_start';
    //            $meta_keys[] = '_mvvwb_booking_end';
    //            $meta_keys[] = '_mvvwb_all_day';
    //            $query_where[] = "( (
    //				_mvvwb_booking_start.meta_value <= '" . esc_sql($end->format('YmdHis')) . "' AND
    //				_mvvwb_booking_end.meta_value >= '" . esc_sql($start->format('YmdHis')) . "' AND
    //				_mvvwb_all_day.meta_value = '0'
    //			) OR (
    //				_mvvwb_booking_start.meta_value <= '" . esc_sql($end->format('Ymd000000')) . "' AND
    //				_mvvwb_booking_end.meta_value >= '" . esc_sql($start->format('Ymd000000')) . "' AND
    //				_mvvwb_all_day.meta_value = '1'
    //			) )";
    //        }
    //
    //        if (!empty($filters['date_after'])) {
    //            $date_after = clone $filters['date_after'];
    //            if ($saveAsGmt) {
    //                $date_after->setTimezone(new DateTimeZone('GMT'));
    //
    //            }
    //            $meta_keys[] = '_mvvwb_booking_start';
    //            $query_where[] = "_mvvwb_booking_start.meta_value >= '" . esc_sql($date_after->format('YmdHis')) . "'";
    //        }
    //
    //        if (!empty($filters['date_before'])) {
    //            $date_before = clone $filters['date_before'];
    //            if ($saveAsGmt) {
    //                $date_before->setTimezone(new DateTimeZone('GMT'));
    //            }
    //            $meta_keys[] = '_mvvwb_booking_end';
    //            $query_where[] = "_mvvwb_booking_end.meta_value <= '" . esc_sql($date_before->format('YmdHis')) . "'";
    //        }
    //
    //        if (!empty($filters['order_by'])) {
    //            switch ($filters['order_by']) {
    //                case 'date_created':
    //                    $filters['order_by'] = 'p.post_date';
    //                    break;
    //                case 'start_date':
    //                    $meta_keys[] = '_mvvwb_booking_start';
    //                    $filters['order_by'] = '_mvvwb_booking_start.meta_value';
    //                    break;
    //            }
    //            $query_order = ' ORDER BY ' . esc_sql($filters['order_by']) . ' ' . esc_sql($filters['order']);
    //        } else {
    //            $query_order = '';
    //        }
    //
    //        if ($filters['limit'] > 0) {
    //            $query_limit = ' LIMIT ' . absint($filters['offset']) . ',' . absint($filters['limit']);
    //        } else {
    //            $query_limit = '';
    //        }
    //
    //        $query_select = "SELECT p.ID FROM {$wpdb->posts} p";
    //        $meta_keys = array_unique($meta_keys);
    //        $query_where = implode(' AND ', $query_where);
    //
    //        foreach ($meta_keys as $index => $meta_key) {
    //            $key = esc_sql($meta_key);
    //            $query_select .= " LEFT JOIN {$wpdb->postmeta} {$key} ON p.ID = {$key}.post_id AND {$key}.meta_key = '{$key}'";
    //        }
    //
    //        return array_filter(wp_parse_id_list($wpdb->get_col("{$query_select} {$query_where} {$query_order} {$query_limit};")));
    //    }
    public static function getBookingIdsByResource( $pId, $args = array() )
    {
        global  $wpdb ;
        $args = wp_parse_args( $args, array(
            'status'       => false,
            'limit'        => -1,
            'offset'       => 0,
            'order_by'     => 'date_created',
            'order'        => 'DESC',
            'date_before'  => false,
            'date_after'   => false,
            'date_between' => array(
            'start' => false,
            'end'   => false,
        ),
        ) );
        $query = self::generateSearchQuery( $pId, $args, 'resource' );
        return array_filter( wp_parse_id_list( $wpdb->get_col( $query ) ) );
    }
    
    public static function getBookingIdsByOrder( $Id, $args = array() )
    {
        global  $wpdb ;
        $args = wp_parse_args( $args, array(
            'status'       => false,
            'limit'        => -1,
            'offset'       => 0,
            'order_by'     => 'date_created',
            'order'        => 'DESC',
            'date_before'  => false,
            'date_after'   => false,
            'date_between' => array(
            'start' => false,
            'end'   => false,
        ),
        ) );
        $query = self::generateSearchQuery( $Id, $args, 'order' );
        return array_filter( wp_parse_id_list( $wpdb->get_col( $query ) ) );
    }
    
    public static function getBookingIdsByProduct( $pId, $args = array() )
    {
        global  $wpdb ;
        $args = wp_parse_args( $args, array(
            'status'       => false,
            'limit'        => -1,
            'offset'       => 0,
            'order_by'     => 'date_created',
            'order'        => 'DESC',
            'date_before'  => false,
            'date_after'   => false,
            'date_between' => array(
            'start' => false,
            'end'   => false,
        ),
        ) );
        $query = self::generateSearchQuery( $pId, $args, 'product' );
        return array_filter( wp_parse_id_list( $wpdb->get_col( $query ) ) );
    }
    
    public static function getBookingByItemIds( $order_item_id )
    {
        global  $wpdb ;
        return wp_parse_id_list( $wpdb->get_col( $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_mvvwb_order_item_id' AND meta_value = %d;", $order_item_id ) ) );
    }
    
    public static function getBookingByOrder( $order_id )
    {
        global  $wpdb ;
        $order_ids = wp_parse_id_list( ( is_array( $order_id ) ? $order_id : array( $order_id ) ) );
        return wp_parse_id_list( $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_type = '" . MVVWB_BOOKING_PT . "' AND post_parent IN (" . implode( ',', array_map( 'esc_sql', $order_ids ) ) . ');' ) );
    }
    
    public function getProduct()
    {
        $order_id = get_post_meta( $this->bookingId, '_mvvwb_product_id', true );
        return wc_get_product( $order_id );
    }
    
    public function get_end_date( $date_format = null, $time_format = null )
    {
        $data = $this->getBookingData();
        return $data['end'];
    }
    
    public function getBookingData( $formatted = true, $dateFormat = false )
    {
        $start = get_post_meta( $this->bookingId, '_mvvwb_booking_start', true );
        $end = get_post_meta( $this->bookingId, '_mvvwb_booking_end', true );
        
        if ( $dateFormat === false ) {
            $dateFormat = get_option( 'date_format' );
            if ( $formatted === false ) {
                $dateFormat = 'YmdHis';
            }
        }
        
        $saveAsGmt = mvvwb_getConfig( 'settings.save_as_gmt', true );
        try {
            $zoneOffSet = get_post_meta( $this->bookingId, '_mvvwb_time_zone', true );
            
            if ( $zoneOffSet == null || $zoneOffSet == '' ) {
                
                if ( $saveAsGmt ) {
                    $timeZoneName = 'GMT';
                } else {
                    $timeZoneName = $this->timeZoneName;
                }
            
            } else {
                $timeZoneName = mvvwb_timezone_string( $zoneOffSet );
            }
            
            $startDate = new DateTime( $start, new DateTimeZone( $timeZoneName ) );
            $endDate = new DateTime( $end, new DateTimeZone( $timeZoneName ) );
            $startDate->setTimezone( new DateTimeZone( $this->timeZoneName ) );
            $endDate->setTimezone( new DateTimeZone( $this->timeZoneName ) );
            $timeStart = $timeStartFormated = get_post_meta( $this->bookingId, '_mvvwb_booking_timeStart', true );
            
            if ( $timeStart != '' && $timeStart !== false ) {
                $dt = new DateTime();
                $dt->setTimeZone( mvvwb_getTimeZone() );
                $dt->setTime( 0, $timeStart, 0 );
                $timeStartFormated = mvvwb_date( get_option( 'time_format' ), $dt->getTimeStamp() );
            }
            
            $costs = get_post_meta( $this->bookingId, '_mvvwb_booking_costs', true );
            
            if ( $costs != '' && is_array( $costs ) ) {
                $costs['total'] = wc_price( $costs['total'] );
                foreach ( $costs['items'] as $k => $item ) {
                    $costs['items'][$k]['price'] = wc_price( $costs['items'][$k]['price'] );
                }
            }
            
            return [
                'start'      => mvvwb_date( $dateFormat, $startDate->getTimeStamp() ),
                'end'        => mvvwb_date( $dateFormat, $endDate->getTimeStamp() ),
                'duration'   => get_post_meta( $this->bookingId, '_mvvwb_booking_duration', true ),
                'timeStart'  => ( $formatted ? $timeStartFormated : $timeStart ),
                'persons'    => get_post_meta( $this->bookingId, '_mvvwb_booking_persons', true ),
                'children'   => get_post_meta( $this->bookingId, '_mvvwb_booking_children', true ),
                'adult'      => get_post_meta( $this->bookingId, '_mvvwb_booking_adult', true ),
                'item_data'  => get_post_meta( $this->bookingId, '_mvvwb_booking_item_data', true ),
                'quantity'   => get_post_meta( $this->bookingId, '_mvvwb_quantity', true ),
                'product_id' => get_post_meta( $this->bookingId, '_mvvwb_product_id', true ),
                'count'      => get_post_meta( $this->bookingId, '_mvvwb_booking_count', true ),
                'costs'      => $costs,
            ];
        } catch ( Exception $e ) {
            $costs = get_post_meta( $this->bookingId, '_mvvwb_booking_costs', true );
            
            if ( $costs != '' && is_array( $costs ) ) {
                $costs['total'] = wc_price( $costs['total'] );
                foreach ( $costs['items'] as $k => $item ) {
                    $costs['items'][$k]['price'] = wc_price( $costs['items'][$k]['price'] );
                }
            }
            
            return [
                'start'      => $start,
                'end'        => $end,
                'duration'   => get_post_meta( $this->bookingId, '_mvvwb_booking_duration', true ),
                'timeStart'  => get_post_meta( $this->bookingId, '_mvvwb_booking_timeStart', true ),
                'persons'    => get_post_meta( $this->bookingId, '_mvvwb_booking_persons', true ),
                'children'   => get_post_meta( $this->bookingId, '_mvvwb_booking_children', true ),
                'adult'      => get_post_meta( $this->bookingId, '_mvvwb_booking_adult', true ),
                'item_data'  => get_post_meta( $this->bookingId, '_mvvwb_booking_item_data', true ),
                'count'      => get_post_meta( $this->bookingId, '_mvvwb_booking_count', true ),
                'quantity'   => get_post_meta( $this->bookingId, '_mvvwb_quantity', true ),
                'product_id' => get_post_meta( $this->bookingId, '_mvvwb_product_id', true ),
                'costs'      => $costs,
            ];
        }
    }
    
    public function getDetails( $isAdmin = false, $formatted = true, $dateFormat = false )
    {
        $post = get_post( $this->bookingId );
        if ( !$post ) {
            return false;
        }
        $postData['title'] = $post->post_title;
        $postData['id'] = $post->ID;
        $product_id = get_post_meta( $post->ID, '_mvvwb_product_id', true );
        $product = get_post( $product_id );
        $postData['status'] = get_post_status( $post );
        $postData['createdOn'] = get_the_date( 'Y-m-d H:m', $post );
        $customer_id = get_post_meta( $post->ID, '_mvvwb_booking_customer_id', true );
        $customer = get_userdata( $customer_id );
        
        if ( $customer ) {
            $postData['customer'] = [
                'name' => $customer->first_name,
                'link' => ( $isAdmin ? get_edit_user_link( $customer_id ) : '' ),
            ];
        } else {
            $postData['customer'] = [
                'name' => 'Guest',
                'link' => '',
            ];
        }
        
        
        if ( $product ) {
            $postData['product'] = [
                'title' => $product->post_title,
                'link'  => ( $isAdmin ? get_edit_post_link( $product, 'edit' ) : get_permalink( $product ) ),
            ];
        } else {
            $postData['product'] = [
                'title' => '',
                'link'  => '',
            ];
        }
        
        $order_id = get_post_meta( $post->ID, '_mvvwb_order_id', true );
        $order = wc_get_order( $order_id );
        $postData['currency_code'] = get_woocommerce_currency();
        
        if ( $order ) {
            $currency_code = $order->get_currency();
            $postData['currency_code'] = $currency_code;
            $postData['order'] = [
                'title' => sprintf( __( 'Order #%d', 'booking-for-woocommerce' ), $order_id ),
                'id'    => $order_id,
                'link'  => ( $isAdmin ? get_edit_post_link( $order_id, 'edit' ) : $order->get_view_order_url() ),
            ];
        } else {
            $postData['order'] = [
                'title' => '',
                'link'  => '',
            ];
        }
        
        $postData['booking'] = $this->getBookingData( $formatted, $dateFormat );
        $postData['resources'] = $this->getResources();
        return $postData;
    }
    
    public function getResources()
    {
        $resources = get_post_meta( $this->bookingId, '_mvvwb_resources', true );
        return $resources;
    }
    
    public function getBookingCount()
    {
        $q = get_post_meta( $this->bookingId, '_mvvwb_booking_count', true );
        
        if ( $q ) {
            return $q;
        } else {
            return 1;
        }
    
    }
    
    public function getResourcesCount( $res_id )
    {
        $q = get_post_meta( $this->bookingId, '_mvvwb_booking_count', true );
        if ( !$q ) {
            $q = 1;
        }
        $resources = get_post_meta( $this->bookingId, '_mvvwb_resources', true );
        if ( $resources && is_array( $resources ) ) {
            foreach ( $resources as $res ) {
                if ( $res['term_id'] == $res_id ) {
                    return $q * $res['quantity'];
                }
            }
        }
        return $q;
    }
    
    public function paid()
    {
        $this->update_status( 'paid' );
    }
    
    public function update_status( $status, $disableActions = false )
    {
        $currentStatus = $this->get_status();
        $newStatus = $status;
        wp_update_post( array(
            'ID'          => $this->bookingId,
            'post_status' => $status,
        ) );
        $this->statusTransition( $currentStatus, $newStatus, $disableActions );
        $this->clearTrans();
        $this->scheduleEvents();
    }
    
    public function get_status()
    {
        return get_post_status( $this->bookingId );
    }
    
    public function statusTransition( $from, $to, $disableActions = false )
    {
        if ( $from === $to ) {
            return;
        }
        $validStatus = [
            'unpaid'               => __( 'Unpaid', 'booking-for-woocommerce' ),
            'pending-confirmation' => __( 'Pending Confirmation', 'booking-for-woocommerce' ),
            'confirmed'            => __( 'Confirmed', 'booking-for-woocommerce' ),
            'paid'                 => __( 'Paid', 'booking-for-woocommerce' ),
            'cancelled'            => __( 'Cancelled', 'booking-for-woocommerce' ),
            'complete'             => __( 'Complete', 'booking-for-woocommerce' ),
        ];
        $from = ( !empty($validStatus[$from]) ? $from : false );
        $to = ( !empty($validStatus[$to]) ? $to : false );
        
        if ( $from && $to ) {
            $order = $this->getOrder();
            if ( $order ) {
                $order->add_order_note( sprintf(
                    __( 'Booking #%1$d status changed from "%2$s" to "%3$s"', 'booking-for-woocommerce' ),
                    $this->getId(),
                    $validStatus[$from],
                    $validStatus[$to]
                ) );
            }
            
            if ( !$disableActions ) {
                do_action(
                    'mvv_booking_status_changed_to_' . $to,
                    $this->getId(),
                    $this,
                    $order
                );
                do_action(
                    'mvv_booking_status_changed_' . $from . '_to_' . $to,
                    $this->getId(),
                    $this,
                    $order
                );
                do_action(
                    'mvv_booking_status_changed',
                    $from,
                    $to,
                    $this->getId(),
                    $this,
                    $order
                );
            }
        
        }
    
    }
    
    public function syncOrderStatusWithBooking( $bookingId )
    {
        $enableThisFeature = apply_filters( 'mvv_booking_sync_order_with_booking', false );
        if ( $enableThisFeature === false ) {
            return;
        }
        $booking = new MVVWB_Booking( $bookingId );
        $status = $booking->get_status();
        $order = $booking->getOrder();
        $orderStatus = $order->get_status();
        $bookings = $this->getBookingIdsByOrder( $order->get_id() );
        foreach ( $bookings as $bk_Id ) {
            $bk = new MVVWB_Booking( $bk_Id );
            $_status = $bk->get_status();
            
            if ( $status !== $_status ) {
                return;
                // dont update order status if all the bookings under that oreder has the same status
            }
        
        }
        switch ( $status ) {
            case 'unpaid':
            case 'pending-confirmation':
                if ( !in_array( $orderStatus, [ 'wc-on-hold' ] ) ) {
                    $order->update_status( 'wc-on-hold', sprintf( __( 'Updated status when Booking #%1$d status changed "', 'booking-for-woocommerce' ), $bookingId ) );
                }
                break;
            case 'confirmed':
                if ( !in_array( $orderStatus, [ 'wc-processing' ] ) ) {
                    $order->update_status( 'wc-processing', sprintf( __( 'Updated status when Booking #%1$d status changed "', 'booking-for-woocommerce' ), $bookingId ) );
                }
            case 'paid':
            case 'confirmed':
                if ( !in_array( $orderStatus, [ 'wc-processing' ] ) ) {
                    $order->update_status( 'wc-processing', sprintf( __( 'Updated status when Booking #%1$d status changed "', 'booking-for-woocommerce' ), $bookingId ) );
                }
                break;
            case 'complete':
                if ( !in_array( $orderStatus, [ 'wc-completed' ] ) ) {
                    $order->update_status( 'wc-completed', sprintf( __( 'Updated status when Booking #%1$d status changed "', 'booking-for-woocommerce' ), $bookingId ) );
                }
                break;
            case 'cancelled':
                if ( !in_array( $orderStatus, [ 'cancelled' ] ) ) {
                    $order->update_status( 'cancelled', sprintf( __( 'Updated status when Booking #%1$d status changed "', 'booking-for-woocommerce' ), $bookingId ) );
                }
        }
    }
    
    public function getOrder()
    {
        $order_id = get_post_meta( $this->bookingId, '_mvvwb_order_id', true );
        return wc_get_order( $order_id );
    }
    
    public function getId()
    {
        return $this->bookingId;
    }
    
    public function clearTrans()
    {
        $product_id = get_post_meta( $this->bookingId, '_mvvwb_product_id', true );
        MVVWB_Transient::clearTransByProduct( $product_id );
    }
    
    public function scheduleEvents()
    {
        $order = $this->getOrder();
        
        if ( in_array( $this->get_status(), [ 'confirmed', 'paid' ] ) ) {
            $bookingData = $this->getBookingData( false );
            $end = new DateTime( $bookingData['end'], new DateTimeZone( $this->timeZoneName ) );
            $order_status = ( $order ? $order->get_status() : null );
            
            if ( !in_array( $order_status, array(
                'cancelled',
                'refunded',
                'pending',
                'on-hold'
            ) ) ) {
                $start = new DateTime( $bookingData['start'], new DateTimeZone( $this->timeZoneName ) );
                $start->modify( '-1 day' );
                wp_clear_scheduled_hook( 'mvvwb_booking_reminder', [ $this->getId() ] );
                wp_schedule_single_event( $start->getTimestamp(), 'mvvwb_booking_reminder', [ $this->getId() ] );
            }
            
            wp_clear_scheduled_hook( 'mvvwb_check_booking_completed', [ $this->getId() ] );
            wp_schedule_single_event( $end->getTimestamp() + 600, 'mvvwb_check_booking_completed', [ $this->getId() ] );
        } else {
            wp_clear_scheduled_hook( 'mvvwb_booking_reminder', array( $this->getId() ) );
            wp_clear_scheduled_hook( 'mvvwb_check_booking_completed', array( $this->getId() ) );
        }
    
    }
    
    public function passed_cancel_day()
    {
    }
    
    public function getBookingItem()
    {
        $product_id = get_post_meta( $this->bookingId, '_mvvwb_product_id', true );
        $product = new MVVWB_Booking_Item( $product_id );
        if ( $product->isValid === false ) {
            return false;
        }
        return $product;
    }
    
    /**
     * Returns booking start date.
     *
     */
    public function get_start_date( $date_format = false, $time_format = null )
    {
        $data = $this->getBookingData( $date_format !== false, $date_format );
        return $data['start'];
    }
    
    public function getProductId()
    {
        $product_id = get_post_meta( $this->bookingId, '_mvvwb_product_id', true );
        return $product_id;
    }
    
    public function get_cancel_url( $redirect = '' )
    {
    }

}