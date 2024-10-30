<?php

if (!defined('ABSPATH'))
    exit;

class MVVWB_I18n
{


    public $_version;
    public $defaultLang;
    public $currentLang;
    private $_plugin = false;

    public function __construct()
    {
        if (class_exists('SitePress')) {
            $this->_plugin = 'wpml';
            $this->defaultLang = apply_filters('wpml_default_language', NULL);
            $this->currentLang = apply_filters('wpml_current_language', NULL);

        } else if (defined('POLYLANG_VERSION') && function_exists('pll_default_language')) {
            $this->_plugin = 'polylang';
            $this->defaultLang = pll_default_language();
            $this->currentLang = pll_current_language();

        }
    }

    public function isActive()
    {
        return $this->_plugin !== false;
    }

    public function isDefault()
    {
        return $this->defaultLang === $this->currentLang;
    }


}
