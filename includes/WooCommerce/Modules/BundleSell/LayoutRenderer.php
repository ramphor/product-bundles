<?php
namespace Ramphor\ProductBundles\WooCommerce\Modules\BundleSell;

class LayoutRenderer
{
    protected static $render_hook;

    public function set_render_hook($hook_name)
    {
        static::$render_hook = $hook_name;
    }

    public function hook_to_template()
    {
        if (static::$render_hook) {
            add_action(static::$render_hook, array( $this, 'render' ));
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
            /*
             * This is not a Bundle-type product.
             * But if it was, then we could re-use the PB templates... without writing new code.
             * Let's "fake" it.
             */
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

            wp_register_style('wc-pb-bs-single', WC_PB()->plugin_url() . '/includes/modules/bundle-sells/assets/css/single-product.css', false, WC_PB()->version, 'all');
            wp_enqueue_style('wc-pb-bs-single');

            /*
             * Show Bundle-Sells section title.
             */
            $bundle_sells_title = Product::get_bundle_sells_title($product);

            if ($bundle_sells_title) {
                wc_get_template('single-product/bundle-sells-section-title.php', array(
                    'title' => wpautop(do_shortcode(wp_kses($bundle_sells_title, ramphor_product_bundles_get_allowed_html('inline'))))
                ), false, WC_PB()->plugin_path() . '/includes/modules/bundle-sells/templates/');
            }

            /*
             * Show Bundle-Sells.
             */
            ?>
            <div class="bundle_form bundle_sells_form"><?php

            foreach ($bundle->get_bundled_items() as $bundled_item) {
                // Neat, isn't it?
                self::apply_bundled_item_template_overrides();
                do_action('woocommerce_bundled_item_details', $bundled_item, $bundle);
                self::reset_bundled_item_template_overrides();
            }

            ?>
                <div class="bundle_data bundle_data_<?php echo $bundle->get_id(); ?>" data-bundle_price_data="<?php echo esc_attr(json_encode($bundle->get_bundle_price_data())); ?>" data-bundle_id="<?php echo $bundle->get_id(); ?>">
                    <div class="bundle_wrap">
                        <div class="bundle_error" style="display:none">
                            <div class="woocommerce-info">
                                <ul class="msg"></ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php

            do_action('woocommerce_after_bundled_items', $bundle);
        }
    }
}
