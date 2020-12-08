<?php
namespace Ramphor\ProductBundles\WooCommerce\Modules\BundleSell;

use Ramphor\ProductBundles\ProductBundleTemplate;

class LayoutRenderer
{
    protected static $render_hook;
    protected static $render_priority = 10;

    public function set_render_hook($hook_name)
    {
        if (is_string($hook_name)) {
            static::$render_hook = $hook_name;
        } elseif(is_array($hook_name)) {
            if (isset($hook_name['hook'])) {
                static::$render_hook = $hook_name['hook'];
            }
            if (isset($hook_name['priority'])) {
                static::$render_priority = $hook_name['priority'];
            }
        } else {
            static::$hook_name = 'woocommerce_before_add_to_cart_form';
        }
    }

    public function hook_to_template()
    {
        if (static::$render_hook) {
            add_action(static::$render_hook, array( $this, 'render' ), static::$render_priority);
        }
    }

    public function render()
    {
        global $product;

        if ($product->is_type('variable')) {
            add_action('woocommerce_single_variation', array( __CLASS__, 'display_bundle_sells' ), 19);
        } else {
            add_action('woocommerce_before_add_to_cart_button', array( __CLASS__, 'display_bundle_sells' ), 1000);
        }
    }

    /**
     * Displays Bundle-Sells above the add-to-cart button.
     *
     * @return void
     */
    public static function display_bundle_sells()
    {

        global $product;

        $bundle_sell_ids = Product::get_bundle_sell_ids($product);

        if (! empty($bundle_sell_ids)) {
            $bundle = Product::get_bundle($bundle_sell_ids, $product);

            if (! $bundle->get_bundled_items()) {
                return;
            }

            do_action('woocommerce_before_bundled_items', $bundle);

            if (false === wp_style_is('wc-bundle-css', 'enqueued')) {
                wp_enqueue_style('wc-bundle-css');
            }

            if (false === wp_script_is('wc-add-to-cart-bundle', 'enqueued')) {
                wp_enqueue_script('wc-add-to-cart-bundle');
            }

            /*
             * Show Bundle-Sells section title.
             */
            $bundle_sells_title = Product::get_bundle_sells_title($product);

            ProductBundleTemplate::render('bundle-sells-section-title', array(
                'title' => &$bundle_sells_title,
            ));

            foreach ($bundle->get_bundled_items() as $bundled_item) {
                do_action( 'woocommerce_bundled_item_details', $bundled_item, $bundle );
            }

            do_action('woocommerce_after_bundled_items', $bundle);
        }
    }
}
