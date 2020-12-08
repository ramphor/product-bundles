<?php
namespace Ramphor\ProductBundles\WooCommerce;

use WC_Product;
use WC_Cache_Helper;
use Ramphor\ProductBundles\BundleCache;
use Ramphor\ProductBundles\WooCommerce\DB;
use Ramphor\ProductBundles\WooCommerce\BundledItem;
use Ramphor\ProductBundles\WooCommerce\Data\BundledItemData;

// Exit if accessed directly.
if (! defined('ABSPATH')) {
    exit;
}

class WC_ProductBundle extends WC_Product
{
    private $data_store_type = 'bundle';

    private $bundled_data_items = null;

    private $extended_data = array(
        'group_mode'                 => 'parent',
        'bundled_items_stock_status' => '',
        'layout'                     => 'default',
        'editable_in_cart'           => false,
        'aggregate_weight'           => false,
        'sold_individually_context'  => 'product',
        'add_to_cart_form_location'  => 'default',
        'min_raw_price'              => '',
        'min_raw_regular_price'      => '',
        'max_raw_price'              => '',
        'max_raw_regular_price'      => ''
    );

    public function __construct($product)
    {

        // Initialize the data store type. Yes, WC 3.0 decouples the data store from the product class.
        if (( $product instanceof WC_Product ) && false === $product->is_type('bundle')) {
            $this->data_store_type = $product->get_type();
        }

        // Initialize private properties.
        $this->load_defaults();

        // Define/load type-specific data.
        $this->load_extended_data();

        // Load product data.
        parent::__construct($product);
    }

    private function load_extended_data()
    {

        // Back-compat.
        $this->product_type = 'bundle';

        // Define type-specific fields and let WC use our data store to read the data.
        $this->data = array_merge($this->data, $this->extended_data);
    }

    public function set_bundled_data_items($data)
    {
        if (is_array($data)) {
            $existing_item_ids = array();
            $update_item_ids   = array();

            $bundled_data_items = $this->get_bundled_data_items('edit');

            // Get real IDs.
            if (! empty($bundled_data_items)) {
                if ($this->bundled_data_items_save_pending) {
                    foreach ($this->bundled_data_items as $bundled_data_item_key => $bundled_data_item) {
                        $existing_item_ids[] = $bundled_data_item->get_meta('real_id');
                    }
                } else {
                    foreach ($this->bundled_data_items as $bundled_data_item_key => $bundled_data_item) {
                        $existing_item_ids[] = $bundled_data_item->get_id();


                        $bundled_data_item->update_meta('real_id', $bundled_data_item->get_id());
                    }
                }
            }

            // Find existing IDs to update.
            if (! empty($data)) {
                foreach ($data as $item_key => $item_data) {
                    // Ignore items without a valid bundled product ID.
                    if (empty($item_data[ 'product_id' ])) {
                        unset($data[ $item_key ]);
                    // If an item with the same ID exists, modify it.
                    } elseif (isset($item_data[ 'bundled_item_id' ]) && $item_data[ 'bundled_item_id' ] > 0 && in_array($item_data[ 'bundled_item_id' ], $existing_item_ids)) {
                        $update_item_ids[] = $item_data[ 'bundled_item_id' ];
                    // Otherwise, add a new one that will be created after saving.
                    } else {
                        $data[ $item_key ][ 'bundled_item_id' ] = 0;
                    }
                }
            }

            // Find existing IDs to remove.
            $remove_item_ids = array_diff($existing_item_ids, $update_item_ids);

            // Remove items and delete them later.
            if (! empty($this->bundled_data_items)) {
                foreach ($this->bundled_data_items as $bundled_data_item_key => $bundled_data_item) {
                    $real_item_id = $this->bundled_data_items_save_pending ? $bundled_data_item->get_meta('real_id') : $bundled_data_item->get_id();

                    if (in_array($real_item_id, $remove_item_ids)) {
                        unset($this->bundled_data_items[ $bundled_data_item_key ]);
                        // Put item in the delete queue if saved in the DB.
                        if ($real_item_id > 0) {
                            // Put back real ID.
                            $bundled_data_item->set_id($real_item_id);
                            $this->bundled_data_items_delete_queue[] = $bundled_data_item;
                        }
                    }
                }
            }

            // Modify/add items.
            if (! empty($data)) {
                foreach ($data as $item_data) {
                    $item_data[ 'bundle_id' ] = $this->get_id();

                    // Modify existing item.
                    if (in_array($item_data[ 'bundled_item_id' ], $update_item_ids)) {
                        foreach ($this->bundled_data_items as $bundled_data_item_key => $bundled_data_item) {
                            $real_item_id = $this->bundled_data_items_save_pending ? $bundled_data_item->get_meta('real_id') : $bundled_data_item->get_id();

                            if ($item_data[ 'bundled_item_id' ] === $real_item_id) {
                                $bundled_data_item->set_all($item_data);
                            }
                        }

                    // Add new item.
                    } else {
                        $new_item = new BundledItemData($item_data);

                        $new_item->update_meta('real_id', 0);
                        $this->bundled_data_items[] = $new_item;
                    }
                }
            }

            // Modify all item IDs to temp values until saved.
            $temp_id = 0;
            if (! empty($this->bundled_data_items)) {
                foreach ($this->bundled_data_items as $bundled_data_item_key => $bundled_data_item) {
                    $temp_id++;
                    $bundled_data_item->set_id($temp_id);
                }
            }

            $this->bundled_data_items_save_pending = true;
            $this->load_defaults();
        }
    }

    public function get_bundled_data_items($context = 'view')
    {

        if (! is_array($this->bundled_data_items)) {
            $use_cache   = ! defined('WC_PB_DEBUG_OBJECT_CACHE') && $this->get_id();
            $cache_key   = WC_Cache_Helper::get_cache_prefix('bundled_data_items') . $this->get_id();
            $cached_data = $use_cache ? wp_cache_get($cache_key, 'bundled_data_items') : false;

            if (false !== $cached_data) {
                $this->bundled_data_items = $cached_data;
            }

            if (! is_array($this->bundled_data_items)) {
                $this->bundled_data_items = array();

                if ($id = $this->get_id()) {
                    $args = array(
                        'bundle_id' => $id,
                        'return'    => 'objects',
                        'order_by'  => array( 'menu_order' => 'ASC' )
                    );

                    $this->bundled_data_items = DB::query_bundled_items($args);

                    if ($use_cache) {
                        wp_cache_set($cache_key, $this->bundled_data_items, 'bundled_data_items');
                    }
                }
            }
        }

        if (has_filter('woocommerce_bundled_data_items')) {
            _deprecated_function('The "woocommerce_bundled_data_items" filter', '5.5.0', 'the "woocommerce_bundled_items" filter');
        }

        return 'view' === $context ? apply_filters('woocommerce_bundled_data_items', $this->bundled_data_items, $this) : $this->bundled_data_items;
    }

    public function load_defaults($reset_objects = false)
    {

        $this->contains = array(
            'priced_individually'               => null,
            'shipped_individually'              => null,
            'assembled'                         => null,
            'optional'                          => false,
            'mandatory'                         => false,
            'on_backorder'                      => false,
            'subscriptions'                     => false,
            'subscriptions_priced_individually' => false,
            'subscriptions_priced_variably'     => false,
            'multiple_subscriptions'            => false,
            'nyp'                               => false,
            'non_purchasable'                   => false,
            'options'                           => false,
            'out_of_stock'                      => false, // Not including optional and zero min qty items (bundle can still be purchased).
            'out_of_stock_strict'               => false, // Including optional and zero min qty items (admin needs to be aware).
            'sold_in_multiples'                 => false,
            'sold_individually'                 => false,
            'discounted'                        => false,
            'discounted_mandatory'              => false,
            'configurable_quantities'           => false,
            'hidden'                            => false,
            'visible'                           => false
        );

        $this->is_synced          = false;
        $this->bundle_price_data  = array();
        $this->bundle_price_cache = array();

        if ($reset_objects) {
            $this->bundled_data_items = null;
        }
    }

    public function get_bundled_items($context = 'view')
    {

        $bundled_items       = array();
        $bundled_data_items  = $this->get_bundled_data_items($context);


        $bundled_product_ids = array();

        foreach ($bundled_data_items as $bundled_data_item) {
            $bundled_product_ids[] = $bundled_data_item->get_product_id();
        }

        if ('bundle' === $this->get_data_store_type()) {
            $this->data_store->preload_bundled_product_data($bundled_product_ids);
        }

        foreach ($bundled_data_items as $bundled_data_item) {
            $bundled_item = $this->get_bundled_item($bundled_data_item, $context);

            if ($bundled_item && $bundled_item->exists()) {
                $bundled_items[ $bundled_data_item->get_id() ] = $bundled_item;
            }
        }

        /**
         * 'woocommerce_bundled_items' filter.
         *
         * @param  array              $bundled_items
         * @param  WC_Product_Bundle  $this
         */
        return 'view' === $context ? apply_filters('woocommerce_bundled_items', $bundled_items, $this) : $bundled_items;
    }

    public function get_data_store_type()
    {
        return $this->data_store_type;
    }

    public function get_bundled_item($bundled_data_item, $context = 'view', $hash = array())
    {

        if ($bundled_data_item instanceof BundledItemData) {
            $bundled_item_id = $bundled_data_item->get_id();
        } else {
            $bundled_item_id = $bundled_data_item = absint($bundled_data_item);
        }

        $bundled_item = false;

        if ($this->has_bundled_item($bundled_item_id, $context)) {
            $cache_group  = 'wc_bundled_item_' . $bundled_item_id . '_' . $this->get_id();
            $cache_key    = md5(json_encode(apply_filters('woocommerce_bundled_item_hash', $hash, $this)));

            $bundled_item = BundleCache::cache_get($cache_key, $cache_group);

            if ($this->bundled_data_items_save_pending || defined('WC_PB_DEBUG_RUNTIME_CACHE') || null === $bundled_item) {
                $bundled_item = new BundledItem($bundled_data_item, $this);

                BundleCache::cache_set($cache_key, $bundled_item, $cache_group);
            }
        }

        return $bundled_item;
    }

    public function has_bundled_item($bundled_item_id, $context = 'view')
    {

        $has_bundled_item = false;
        $bundled_item_ids = $this->get_bundled_item_ids($context);

        if (in_array($bundled_item_id, $bundled_item_ids)) {
            $has_bundled_item = true;
        }

        return $has_bundled_item;
    }

    public function get_bundled_item_ids($context = 'view')
    {

        $bundled_item_ids = array();

        foreach ($this->get_bundled_data_items($context) as $bundled_data_item) {
            $bundled_item_ids[] = $bundled_data_item->get_id();
        }

        /**
         * 'woocommerce_bundled_item_ids' filter.
         *
         * @param  array              $ids
         * @param  WC_Product_Bundle  $this
         */
        return 'view' === $context ? apply_filters('woocommerce_bundled_item_ids', $bundled_item_ids, $this) : $bundled_item_ids;
    }
}
