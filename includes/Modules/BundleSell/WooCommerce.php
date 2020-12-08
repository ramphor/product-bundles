<?php
namespace Ramphor\ProductBundles\Modules\BundleSell;

use Ramphor\ProductBundles\Abstracts\Module;
use Ramphor\ProductBundles\Modules\BundleSell\Admin\WooCommerce\BundleSell as BundleSellAdmin;

class WooCommerce extends Module
{
    public function load_module()
    {
        if (is_admin()) {
            BundleSellAdmin::init();
        }
    }

    public function init()
    {
    }
}
