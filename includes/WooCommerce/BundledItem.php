<?php
namespace Ramphor\ProductBundles\WooCommerce;

use Ramphor\ProductBundles\WooCommerce\DB;
use Ramphor\ProductBundles\WooCommerce\Data\BundledItemData;

class BundledItem
{
    public $data;
    public $product;
    private $title;
    private $description;

    public function __construct($bundled_item, $parent = false)
    {

        if (is_numeric($bundled_item)) {
            $this->data = DB::get_bundled_item(absint($bundled_item));
        } elseif ($bundled_item instanceof BundledItemData) {
            $this->data = $bundled_item;
        }

        if (is_object($this->data)) {
            if (is_object($parent) && $this->get_bundle_id() === $parent->get_id()) {
                $this->bundle = $parent;
            }

            $this->load_data();

            do_action('woocommerce_before_init_bundled_item', $this);

            $bundled_product = wc_get_product($this->get_product_id());

            // if not present, item cannot be purchased.
            if ($bundled_product && $bundled_product->is_type(array( 'simple', 'variable', 'subscription', 'variable-subscription' ))) {
                $this->product     = $bundled_product;
                $this->title       = 'yes' === $this->override_title ? $this->title : $bundled_product->get_title();
                $this->description = 'yes' === $this->override_description ? $this->description : $bundled_product->get_short_description();

                if (false !== $parent && $this->is_purchasable() && $this->is_priced_individually()) {
                    $this->sync_prices();
                }
            }

            do_action('woocommerce_after_init_bundled_item', $this);
        }
    }

    public function get_bundle_id()
    {
        return is_object($this->data) ? $this->data->get_bundle_id() : null;
    }

    public function exists()
    {

        $exists = true;

        if (empty($this->product)) {
            $exists = false;
        }

        if (! is_object($this->product)) {
            $exists = false;
        }

        if ($exists) {
            if ('trash' === $this->product->get_status()) {
                $exists = false;
            } elseif (! in_array($this->product->get_type(), array( 'simple', 'variable', 'subscription', 'variable-subscription' ))) {
                $exists = false;
            }
        }

        return $exists;
    }

    public function get_product_id()
    {
        return is_object($this->data) ? $this->data->get_product_id() : null;
    }

    private function load_data()
    {

        // Defaults.
        $defaults = array(
            'quantity_min'                          => 1,
            'quantity_max'                          => 1,
            'priced_individually'                   => 'no',
            'shipped_individually'                  => 'no',
            'override_title'                        => 'no',
            'title'                                 => '',
            'override_description'                  => 'no',
            'description'                           => '',
            'optional'                              => 'no',
            'hide_thumbnail'                        => 'no',
            'discount'                              => '',
            'override_variations'                   => 'no',
            'override_default_variation_attributes' => 'no',
            'allowed_variations'                    => false,
            'default_variation_attributes'          => false,
            'single_product_visibility'             => 'visible',
            'cart_visibility'                       => 'visible',
            'order_visibility'                      => 'visible',
            'single_product_price_visibility'       => 'visible',
            'cart_price_visibility'                 => 'visible',
            'order_price_visibility'                => 'visible',
            'stock_status'                          => null,
            'max_stock'                             => null
        );

        // Set meta and properties.
        $this->item_data = wp_parse_args($this->data->get_meta_data(), $defaults);
        // Added for back-compat.
        $this->item_data[ 'product_id' ] = $this->data->get_product_id();

        foreach ($defaults as $key => $value) {
            $this->$key = $this->item_data[ $key ];
        }

        $this->default_variation_attributes = 'yes' === $this->override_default_variation_attributes && is_array($this->default_variation_attributes) && ! empty($this->default_variation_attributes) ? $this->default_variation_attributes : false;
        $this->allowed_variations           = 'yes' === $this->override_variations && is_array($this->allowed_variations) && ! empty($this->allowed_variations) ? $this->allowed_variations : false;
        $this->visibility                   = array(
            'product' => $this->single_product_visibility,
            'cart'    => $this->cart_visibility,
            'order'   => $this->order_visibility
        );
        $this->price_visibility             = array(
            'product' => $this->single_product_price_visibility,
            'cart'    => $this->cart_price_visibility,
            'order'   => $this->order_price_visibility
        );
    }

    public function is_purchasable()
    {
        if (! isset($this->purchasable)) {
            $this->purchasable = $this->exists() && $this->product->is_purchasable();
        }
        return $this->purchasable;
    }

    public function is_priced_individually()
    {
        $is_priced_individually = 'yes' === $this->priced_individually;

        /**
         * 'woocommerce_bundled_item_is_priced_individually' filter.
         *
         * @param  boolean          $is_priced_individually
         * @param  WC_Bundled_Item  $this
         */
        return apply_filters('woocommerce_bundled_item_is_priced_individually', $is_priced_individually, $this);
    }
}
