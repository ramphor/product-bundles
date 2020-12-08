<?php
namespace Ramphor\ProductBundles\Modules\BundleSell;

// Exit if accessed directly.
if (! defined('ABSPATH')) {
    exit;
}

class ProductPrices
{
    /**
     * Method to use for calculating cart item discounts. Values: 'filters' | 'props'
     *
     * @since  6.0.0
     *
     * @return string  $method
     */
    public static function get_bundled_cart_item_discount_method()
    {
        /**
         * 'woocommerce_bundled_cart_item_discount_method' filter.
         *
         * @since  6.0.0
         *
         * @param  string  $method  Method to use for calculating cart item discounts. Values: 'filters' | 'props'.
         */
        $discount_method = apply_filters('woocommerce_bundled_cart_item_discount_method', 'filters');

        return in_array($discount_method, array( 'filters', 'props' )) ? $discount_method : 'filters';
    }
}
