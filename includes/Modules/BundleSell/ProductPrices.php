<?php
namespace Ramphor\ProductBundles\Modules\BundleSell;

// Exit if accessed directly.
if (! defined('ABSPATH')) {
    exit;
}

class ProductPrices
{
    public static function get_bundled_cart_item_discount_method()
    {
        $discount_method = apply_filters('woocommerce_bundled_cart_item_discount_method', 'filters');

        return in_array($discount_method, array( 'filters', 'props' )) ? $discount_method : 'filters';
    }
}
