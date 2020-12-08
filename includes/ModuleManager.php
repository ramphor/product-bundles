<?php
namespace Ramphor\ProductBundles;

use Ramphor\ProductBundles\WooCommerce\Modules\BundleSell as WooCommerceBundleSell;

class ModuleManager
{
    public static function get_modules()
    {
        $woocommerce_modules = array(
            WooCommerceBundleSell::class,
        );

        return array(
            'woocommerce' => $woocommerce_modules,
        );
    }
}
