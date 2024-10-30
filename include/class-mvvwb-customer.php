<?php
if (!defined('ABSPATH'))
exit;

class MVVWB_Customer {

    public function __construct() {


    }

    static function get_session() {
        if(WC() && WC()->session){
            $cookie =  WC()->session->get_session_cookie();
           $customer = WC()->session->get_customer_id();

            return [ $customer,isset($cookie[3]) ? $cookie[3] : false];
        }else{
            return false;
        }

    }

    }
