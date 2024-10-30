<?php


if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

if ($booking_ids) {
    $labels = mvvwb_getAllConfig('labels');
    $resourceLabel = mvvwb_getAllConfig('resource');
    foreach ($booking_ids as $booking_id) {
        $booking = new MVVWB_Booking($booking_id);
        $details = $booking->getDetails();

        if ($details['booking']['start'] === $details['booking']['end']) {
            $booking_date = sprintf('%1$s', $details['booking']['start']);
        } else {
            $booking_date = sprintf('%1$s - %2$s', $details['booking']['start'], $details['booking']['end']);
        }
        ?>
        <div class="mvvwb-booking-summary">
            <strong class="mvvwb-booking-summary-number">
                <?php

                echo esc_html(sprintf(__('Booking #%s', 'booking-for-woocommerce'), (string)$booking_id));
                ?>
                <span class="mvvwb-status-<?php echo esc_attr($details['status']); ?>">
                    <?php echo esc_html(mvvwb_bookings_get_status_label($details['status'])); ?>
                </span>
            </strong>
            <ul class="wc-booking-summary-list">
                <li>
                    <?php echo $booking_date; ?>
                </li>
                <?php
                if (isset($details['booking']['timeStart']) && $details['booking']['timeStart']) {
                    echo '<li>' . $details['booking']['timeStart'] . '</li>';
                }

                if (isset($details['booking']['persons']) && $details['booking']['persons']) {
                    echo '<li>' . $labels['persons'] . ': ' . $details['booking']['persons'] . '</li>';
                }
                if (isset($details['booking']['adult']) && $details['booking']['adult']) {
                    echo '<li>' . $labels['adult'] . ': ' . $details['booking']['adult'] . '</li>';
                }
                if (isset($details['booking']['children']) && $details['booking']['children']) {
                    echo '<li>' . $labels['children'] . ': ' . $details['booking']['children'] . '</li>';
                }
                ?>
            </ul>
            <?php
            if (isset($details['resources']) && is_array($details['resources'])) {
                echo '<h4 class="mvvwb_res_title">' . $resourceLabel['resourcesTitle'] . '</h4><ul class="mvvwb_res_list">';
                foreach ($details['resources'] as $resource) {
                    echo '<li>' . $resource['name'] . ((isset($resource['quantity']) && $resource['quantity']) ? 'x' . $resource['quantity'] : '');
                    if (isset($resource['price']) && $resource['price'] > 0) {
                        echo ' (' . wc_price($resource['price']) . ')';
                    }
                    echo '</li>';
                }
                echo '</ul>';

            }
            ?>

            <div class="mvvwb-summary-actions">

                <a target="_blank" href="<?php echo admin_url('admin.php?page=mvvwb-dashboard#/booking/' . $booking_id); ?>">
                    <?php echo sprintf(esc_html__('Booking # %d', 'booking-for-woocommerce'),$booking_id); ?></a>
            </div>
        </div>
        <?php
    }
}
