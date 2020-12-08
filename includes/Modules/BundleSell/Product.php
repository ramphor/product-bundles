<?php
namespace Ramphor\ProductBundles\Modules\BundleSell;

use WC_Product;
use Ramphor\ProductBundles\BundleCache;

// Exit if accessed directly.
if (! defined('ABSPATH')) {
    exit;
}

class Product
{
    public static function get_bundle_sell_ids($product, $context = 'view')
    {

        $bundle_sell_ids = array();

        if (! ( $product instanceof WC_Product )) {
            $product = wc_get_product($product);
        }

        if (( $product instanceof WC_Product ) && false === $product->is_type('bundle')) {
            $bundle_sell_ids = $product->get_meta('_wc_pb_bundle_sell_ids', true);

            if (! empty($bundle_sell_ids) && is_array($bundle_sell_ids)) {
                $bundle_sell_ids = array_map('intval', $bundle_sell_ids);
            }

            $bundle_sell_ids = 'view' === $context ? apply_filters('wc_pb_bundle_sell_ids', $bundle_sell_ids, $product) : $bundle_sell_ids;
        }

        return $bundle_sell_ids;
    }

    public static function get_bundle_sells_title($product, $context = 'view')
    {

        $title = '';

        if (! ( $product instanceof WC_Product )) {
            $product = wc_get_product($product);
        }

        if (( $product instanceof WC_Product ) && false === $product->is_type('bundle')) {
            $title = $product->get_meta('_wc_pb_bundle_sells_title', true);

            $title = 'view' === $context ? apply_filters('wc_pb_bundle_sells_title', $title, $product) : $title;
        }

        return $title;
    }

    public static function get_bundle_sells_discount($product, $context = 'view')
    {

        $discount = '';

        if ('filters' !== ProductPrices::get_bundled_cart_item_discount_method()) {
            return $discount;
        }

        if (! ( $product instanceof WC_Product )) {
            $product = wc_get_product($product);
        }

        if (( $product instanceof WC_Product ) && false === $product->is_type('bundle')) {
            $discount = BundleCache::cache_get('bundle_sells_discount_' . $product->get_id());

            if (null === $discount) {
                $discount = $product->get_meta('_wc_pb_bundle_sells_discount', true, 'edit');
                BundleCache::cache_get('bundle_sells_discount_' . $product->get_id(), $discount);
            }

            $discount = 'view' === $context ? apply_filters('wc_pb_bundle_sells_discount', $discount, $product) : $discount;
        }

        return $discount;
    }
}
