<?php
namespace Ramphor\ProductBundles\WooCommerce\Modules;

use Ramphor\ProductBundles\Abstracts\Module;
use Ramphor\ProductBundles\WooCommerce\Modules\BundleSell\LayoutRenderer;
use Ramphor\ProductBundles\WooCommerce\Modules\BundleSell\Admin\BundleSell as BundleSellAdmin;

class BundleSell extends Module
{
    public function load_module()
    {
        if (is_admin()) {
            BundleSellAdmin::init();
        }
    }

    public function init()
    {
        $layout = new LayoutRenderer();
        $layout->set_render_hook(apply_filters(
            'ramphor_product_bundles_module_bundle_sell_render_hook',
            'woocommerce_before_add_to_cart_form'
        ));
        $layout->hook_to_template();
    }
}
