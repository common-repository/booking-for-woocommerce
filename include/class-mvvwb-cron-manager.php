<?php

/**
 * Cron job handler.
 */
class MVVWB_Cron_Manager
{

    public function __construct()
    {
        add_action('mvvwb_booking_reminder', array($this, 'send_booking_reminder'));

        add_action('mvvwb_check_booking_completed', array($this, 'mvvwb_check_booking_completed'), 10, 1);
    }

    public function send_booking_reminder($booking_id)
    {
        $mailer = WC()->mailer();
        $reminder = $mailer->emails['MVVWB_Email_Booking_Reminder'];
        $reminder->trigger($booking_id);
    }


    function mvvwb_check_booking_completed($id)
    {
        $booking = new MVVWB_Booking($id);
        $details = $booking->getBookingData(true, 'Y-m-d H:i:s');

        $now = new DateTime('now', mvvwb_getTimeZone());
        if ($details['end'] < $now && $booking->get_status() !== 'cancelled') {
            $booking->update_status('complete');
        }

    }

}
