<?php
namespace Ramphor\ProductBundles\WooCommerce\Modules\BundleSell\Admin;

use WC_AJAX;
use Ramphor\ProductBundles\WooCommerce\Modules\BundleSell\Product as BundleSellProduct;

class BundleSell
{
    public static function init()
    {
        add_action('woocommerce_product_options_related', array( __CLASS__, 'bundle_sells_options' ));

        add_action('woocommerce_admin_process_product_object', array( __CLASS__, 'process_bundle_sells_options' ));

        add_action('wp_ajax_woocommerce_json_search_bundle_sells', array( __CLASS__, 'ajax_search_bundle_sells' ));
    }

    public static function bundle_sells_options()
    {
        global $product_object;
        ?>
        <!-- hide_if_grouped hide_if_external hide_if_bundle -->
        <div class="options_group hide_if_grouped hide_if_external hide_if_bundle">
            <p class="form-field ">
                <label for="crosssell_ids"><?php _e('Bundle-sells', 'woocommerce-product-bundles'); ?></label>
                <select class="wc-product-search" multiple="multiple" style="width: 50%;" id="bundle_sell_ids" name="bundle_sell_ids[]" data-placeholder="<?php esc_attr_e('Search for a product&hellip;', 'woocommerce'); ?>" data-action="woocommerce_json_search_bundle_sells" data-exclude="<?php echo intval($product_object->get_id()); ?>" data-limit="100" data-sortable="true">
                    <?php
                    $product_ids = BundleSellProduct::get_bundle_sell_ids($product_object, 'edit');

                    if (! empty($product_ids)) {
                        foreach ($product_ids as $product_id) {
                            $product = wc_get_product($product_id);

                            if (is_object($product)) {
                                echo '<option value="' . esc_attr($product_id) . '"' . selected(true, true, false) . '>' . wp_kses_post($product->get_formatted_name()) . '</option>';
                            }
                        }
                    }
                    ?>
                </select> <?php echo wc_help_tip(__('Bundle-sells are optional products that can be selected and added to the cart along with this product.', 'woocommerce-product-bundles')); ?>
            </p>
            <?php

                woocommerce_wp_textarea_input(array(
                    'id'            => 'wc_pb_bundle_sells_title',
                    'value'         => BundleSellProduct::get_bundle_sells_title($product_object, 'edit'),
                    'label'         => __('Bundle-sells title', 'woocommerce-product-bundles'),
                    'description'   => __('Text to display above the bundle-sells section.', 'woocommerce-product-bundles'),
                    'placeholder'   => __('e.g. "Frequently Bought Together"', 'woocommerce-product-bundles'),
                    'desc_tip'      => true
                ));

                woocommerce_wp_text_input(array(
                    'id'            => 'wc_pb_bundle_sells_discount',
                    'value'         => BundleSellProduct::get_bundle_sells_discount($product_object, 'edit'),
                    'type'          => 'text',
                    'class'         => 'input-text wc_input_decimal',
                    'label'         => __('Bundle-sells discount', 'woocommerce-product-bundles'),
                    'description'   => __('Discount to apply to bundle-sells (%). Accepts values from 0 to 100.', 'woocommerce-product-bundles'),
                    'desc_tip'      => true
                ));

            ?>
        </div>
        <?php
    }

    public static function ajax_search_bundle_sells()
    {
        add_filter('woocommerce_json_search_found_products', array( __CLASS__, 'filter_ajax_search_results' ));
        WC_AJAX::json_search_products('', false);
        remove_filter('woocommerce_json_search_found_products', array( __CLASS__, 'filter_ajax_search_results' ));
    }

    public static function filter_ajax_search_results($search_results)
    {

        if (! empty($search_results)) {
            $search_results_filtered = array();

            foreach ($search_results as $product_id => $product_title) {
                $product = wc_get_product($product_id);

                if (is_object($product) && $product->is_type(array( 'simple', 'subscription' ))) {
                    $search_results_filtered[ $product_id ] = $product_title;
                }
            }

            $search_results = $search_results_filtered;
        }

        return $search_results;
    }

    public static function process_bundle_sells_options($product)
    {

        /*
         * Process bundle-sell IDs.
         */

        $bundle_sell_ids = ! empty($_POST[ 'bundle_sell_ids' ]) && is_array($_POST[ 'bundle_sell_ids' ]) ? array_map('intval', (array) $_POST[ 'bundle_sell_ids' ]) : array();

        if (! empty($bundle_sell_ids)) {
            $product->update_meta_data('_wc_pb_bundle_sell_ids', $bundle_sell_ids);
        } else {
            $product->delete_meta_data('_wc_pb_bundle_sell_ids');
        }

        /*
         * Process bundle-sells title.
         */

        $title = ! empty($_POST[ 'wc_pb_bundle_sells_title' ]) ? wp_kses(wp_unslash($_POST[ 'wc_pb_bundle_sells_title' ]), ramphor_product_bundles_get_allowed_html('inline')) : false; // @phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

        if ($title) {
            $product->update_meta_data('_wc_pb_bundle_sells_title', $title);
        } else {
            $product->delete_meta_data('_wc_pb_bundle_sells_title');
        }

        /*
         * Process bundle-sells discount.
         */

        $discount = ! empty($_POST[ 'wc_pb_bundle_sells_discount' ]) ? sanitize_text_field($_POST[ 'wc_pb_bundle_sells_discount' ]) : false;

        if (! empty($discount)) {
            if (is_numeric($discount)) {
                $discount = wc_format_decimal($discount);
            } else {
                $discount = -1;
            }

            if ($discount < 0 || $discount > 100) {
                $discount = false;
                WC_PB_Meta_Box_Product_Data::add_admin_error(__('Invalid bundle-sells discount value. Please enter a positive number between 0-100.', 'woocommerce-product-bundles'));
            }
        }

        if ($discount) {
            $product->update_meta_data('_wc_pb_bundle_sells_discount', $discount);
        } else {
            $product->delete_meta_data('_wc_pb_bundle_sells_discount');
        }
    }
}