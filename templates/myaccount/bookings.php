<?php

if ( !defined( 'ABSPATH' ) ) {
    exit;
}
$count = 0;
$sections = [
    'upcoming' => esc_html__( 'Upcoming Bookings', 'booking-for-woocommerce' ),
    'past'     => esc_html__( 'Past Bookings', 'booking-for-woocommerce' ),
];

if ( !empty($bookings) ) {
    ?>

    <?php 
    foreach ( $sections as $key => $header ) {
        
        if ( isset( $bookings[$key] ) && !empty($bookings[$key]) ) {
            ?>
            <h2><?php 
            echo  $header ;
            ?></h2>
            <table class="shop_table my_account_bookings">
                <thead>
                <tr>
                    <th class="booking-id"><?php 
            esc_html_e( 'ID', 'booking-for-woocommerce' );
            ?></th>
                    <th class="booked-product"><?php 
            esc_html_e( 'Booked', 'booking-for-woocommerce' );
            ?></th>
                    <th class="order-number"><?php 
            esc_html_e( 'Order', 'booking-for-woocommerce' );
            ?></th>
                    <th class="booking-start-date"><?php 
            esc_html_e( 'Start Date', 'booking-for-woocommerce' );
            ?></th>
                    <th class="booking-end-date"><?php 
            esc_html_e( 'End Date', 'booking-for-woocommerce' );
            ?></th>
                    <th class="booking-status"><?php 
            esc_html_e( 'Status', 'booking-for-woocommerce' );
            ?></th>
                    <th class="booking-cancel"></th>
                </tr>
                </thead>
                <tbody>
                <?php 
            foreach ( $bookings[$key] as $booking ) {
                ?>
                    <?php 
                $details = $booking->getDetails();
                
                if ( $details['booking']['start'] === $details['booking']['end'] ) {
                    $booking_date = sprintf( '%1$s', $details['booking']['start'] );
                } else {
                    $booking_date = sprintf( '%1$s - %2$s', $details['booking']['start'], $details['booking']['end'] );
                }
                
                $count++;
                ?>
                    <tr>
                        <td class="booking-id"><?php 
                echo  esc_html( $booking->bookingId ) ;
                ?></td>
                        <td class="booked-product">
                            <?php 
                
                if ( $details['product'] ) {
                    ?>
                                <a href="<?php 
                    echo  esc_url( $details['product']['link'] ) ;
                    ?>">
                                    <?php 
                    echo  esc_html( $details['product']['title'] ) ;
                    ?>
                                </a>
                            <?php 
                }
                
                ?>
                        </td>
                        <td class="order-number">
                            <?php 
                
                if ( $details['order'] ) {
                    ?>
                                <a href="<?php 
                    echo  esc_url( $details['order']['link'] ) ;
                    ?>">
                                    <?php 
                    echo  esc_html_e( 'Order #' . $details['order']['id'] ) ;
                    ?>
                                </a>
                            <?php 
                }
                
                ?>
                        </td>
                        <td class="booking-start-date"><?php 
                echo  esc_html( sprintf( '%1$s', $details['booking']['start'] ) ) ;
                ?></td>
                        <td class="booking-end-date"><?php 
                echo  esc_html( sprintf( '%1$s', $details['booking']['end'] ) ) ;
                ?></td>
                        <td class="booking-status"><?php 
                echo  esc_html( mvvwb_bookings_get_status_label( $details['status'] ) ) ;
                ?></td>
                        <td class="booking-cancel">

                            <?php 
                ?>
                        </td>
                    </tr>
                <?php 
            }
            ?>
                </tbody>
            </table>
            <div class="woocommerce-pagination">
                <?php 
            
            if ( 1 !== $page ) {
                ?>
                    <a class="woocommerce-button woocommerce-button--previous button"
                       href="<?php 
                echo  esc_url( wc_get_endpoint_url( 'bookings', $page - 1 ) ) ;
                ?>"><?php 
                _e( 'Previous', 'booking-for-woocommerce' );
                ?></a>
                <?php 
            }
            
            ?>

                <?php 
            
            if ( $count >= $per_page ) {
                ?>
                    <a class="woocommerce-button woocommerce-button--next button"
                       href="<?php 
                echo  esc_url( wc_get_endpoint_url( 'bookings', $page + 1 ) ) ;
                ?>"><?php 
                _e( 'Next', 'booking-for-woocommerce' );
                ?></a>
                <?php 
            }
            
            ?>
            </div>
            <?php 
        }
    
    }
} else {
    ?>
    <div class="woocommerce-Message  woocommerce-info">
        <a class="woocommerce-Button button"
           href="<?php 
    echo  esc_url( apply_filters( 'woocommerce_return_to_shop_redirect', wc_get_page_permalink( 'shop' ) ) ) ;
    ?>">
            <?php 
    esc_html_e( 'Go Shop', 'booking-for-woocommerce' );
    ?>
        </a>
        <?php 
    esc_html_e( 'No bookings available yet.', 'booking-for-woocommerce' );
    ?>
    </div>
<?php 
}
