<?php
namespace Ramphor\ProductBundles;

use Jankx\Template\Template;

class ProductBundleTemplate
{
    protected static $loader;

    public static function getLoader()
    {
        if (is_null(static::$loader)) {
            $productBundleRemplateDir = sprintf(
                '%s/templates',
                dirname(RAMPHOR_PRODUCT_BUNDLES_PLUGIN_FILE)
            );

            static::$loader = Template::getLoader(
                $productBundleRemplateDir,
                apply_filters(
                    'ramphor_product_bundles_template_dirname',
                    'templates/bundles'
                ),
                'wordpress'
            );
        }
        return static::$loader;
    }

    public static function render()
    {
        $args = func_get_args();

        return call_user_func_array(
            array(static::getLoader(), 'render'),
            $args
        );
    }
}
