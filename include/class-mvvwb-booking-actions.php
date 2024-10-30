<?php

if ( !defined( 'ABSPATH' ) ) {
    exit;
}
class MVVWB_Booking_Actions
{
    public function __construct()
    {
        add_action(
            'woocommerce_order_status_processing',
            array( $this, 'confirmBookings' ),
            10,
            1
        );
        add_action(
            'woocommerce_order_status_completed',
            array( $this, 'confirmBookings' ),
            10,
            1
        );
        add_action( 'before_delete_post', array( $this, 'delete_post' ) );
        add_action( 'wp_trash_post', array( $this, 'trash_post' ) );
        add_action( 'untrash_post', array( $this, 'untrash_post' ) );
        add_action(
            'woocommerce_order_status_cancelled',
            array( $this, 'cancelBookings' ),
            10,
            1
        );
        add_action(
            'woocommerce_order_status_refunded',
            array( $this, 'cancelBookings' ),
            10,
            1
        );
        add_action(
            'woocommerce_order_partially_refunded',
            array( $this, 'cancelBookingsPartialRefunds' ),
            10,
            1
        );
    }
    
    public function preventCancel( $return, $order )
    {
    }
    
    public function myOrdersActions( $actions, $order )
    {
    }
    
    public function confirmBookings( $order_id )
    {
        $order = wc_get_order( $order_id );
        $payment_method = $order->get_payment_method();
        $bookings = MVVWB_Booking::getBookingByOrder( $order_id );
        $publish = true;
        if ( $order->get_status() === 'processing' && $payment_method === 'cod' ) {
            $publish = false;
        }
        foreach ( $bookings as $booking_id ) {
            $booking = new MVVWB_Booking( $booking_id );
            if ( $publish ) {
                $booking->paid();
            }
            //            else {
            //                // $booking->maybe_schedule_event('reminder');
            //                //  $booking->maybe_schedule_event('complete');
            //            }
        }
    }
    
    public function cancelBookings( $order_id )
    {
        $order = wc_get_order( $order_id );
        $bookings = MVVWB_Booking::getBookingByOrder( $order_id );
        foreach ( $bookings as $booking_id ) {
            $booking = new MVVWB_Booking( $booking_id );
            $booking->update_status( 'cancelled' );
        }
    }
    
    public function cancelBookingsPartialRefunds( $order_id )
    {
        $order = wc_get_order( $order_id );
        foreach ( $order->get_items() as $order_item_id => $item ) {
            $refunded_qty = $order->get_qty_refunded_for_item( $order_item_id );
            
            if ( 'line_item' === $item['type'] && 0 !== $refunded_qty ) {
                $booking_ids = MVVWB_Booking::getBookingByItemIds( $order_item_id );
                foreach ( $booking_ids as $booking_id ) {
                    $booking = new MVVWB_Booking( $booking_id );
                    $booking->update_status( 'cancelled' );
                }
            }
        
        }
    }
    
    public function delete_post( $post_id )
    {
        if ( !current_user_can( 'delete_posts' ) || !$post_id ) {
            return;
        }
        
        if ( 'shop_order' === get_post_type( $post_id ) ) {
            $ids = MVVWB_Booking::getBookingIdsByOrder( $post_id );
            if ( $ids ) {
                foreach ( $ids as $id ) {
                    wp_delete_post( $id, true );
                    $this->clear_cron_hooks( (int) $id );
                }
            }
        }
    
    }
    
    function clear_cron_hooks( $post_id )
    {
        wp_clear_scheduled_hook( 'mvvwb_booking_reminder', array( $post_id ) );
        wp_clear_scheduled_hook( 'mvvwb_check_booking_completed', array( $post_id ) );
    }
    
    public function trash_post( $post_id )
    {
        if ( !$post_id ) {
            return;
        }
        
        if ( 'shop_order' === get_post_type( $post_id ) ) {
            $bookings = MVVWB_Booking::getBookingIdsByOrder( $post_id );
            foreach ( $bookings as $booking_id ) {
                wp_trash_post( $booking_id );
            }
        }
    
    }
    
    public function untrash_post( $post_id )
    {
        if ( !$post_id ) {
            return;
        }
        
        if ( 'shop_order' === get_post_type( $post_id ) ) {
            $bookings = MVVWB_Booking::getBookingIdsByOrder( $post_id );
            foreach ( $bookings as $booking_id ) {
                wp_untrash_post( $booking_id );
            }
        }
    
    }

}