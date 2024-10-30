<?php


if (!defined('ABSPATH'))
    exit;

class MVVWB_Compatibility_MC
{


    function __construct()
    {


    }


    public function convert($price,  $to = false,$from = false)
    {


        if (defined('WOOMULTI_CURRENCY_F_VERSION')) {
            // https://wordpress.org/plugins/woo-multi-currency/
            return $this->WooMultiCurrency($price,$to, $from);
        }

        if (defined('WOOCS_VERSION')) {
            // https://wordpress.org/plugins/woocommerce-currency-switcher/
            return $this->WooCurrencySwitcher($price, $to, $from);
        }

        if (defined('WCPBC_PLUGIN_FILE')) {
            // https://wordpress.org/plugins/woocommerce-product-price-based-on-countries/
            return $this->WooPrdctPriceByCountry($price, $to, $from);
        }

        $price = apply_filters('mvvwb_currency_convert', $price);

        return $price;
    }
    public function cartConvert($price)
    {


        if (defined('WCPBC_PLUGIN_FILE')) {
            // https://wordpress.org/plugins/woocommerce-product-price-based-on-countries/
            return $this->WooPrdctPriceByCountry($price);
        }

        $price = apply_filters('mvvwb_currency_cart_convert', $price);

        return $price;
    }
    public function WooMultiCurrency($price,  $to = false,$from = false)
    {
        if (function_exists('wmc_get_price')) {
            return wmc_get_price($price,$to);
        }

        return $price;
    }

    public function WooCurrencySwitcher($price,  $to = false,$from = false)
    {
        // https://wordpress.org/plugins/woocommerce-currency-switcher/
        global $WOOCS;
        if (method_exists($WOOCS, 'woocs_exchange_value')) {
            return $WOOCS->woocs_exchange_value($price);
        }

        return $price;
    }

    public function WooPrdctPriceByCountry($price,  $to = false,$from = false)
    {
        // https://wordpress.org/plugins/woocommerce-product-price-based-on-countries/
        if (function_exists('wcpbc_the_zone')) {
            $wcpbcObject = wcpbc_the_zone();
            if (method_exists($wcpbcObject, 'get_exchange_rate_price')) {
                return $wcpbcObject->get_exchange_rate_price($price);
            }
        }
        return $price;
    }


}