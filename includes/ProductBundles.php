<?php
namespace Ramphor\ProductBundles;

use Ramphor\ProductBundles\Interfaces\ModuleInterface;

class ProductBundles
{
    protected static $instance;

    protected $shop_plugins = array();
    protected $modules = array();

    public static function getInstance()
    {
        if (is_null(static::$instance)) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    protected function __construct()
    {
        $this->bootstrap();
        $this->initHooks();
    }

    public function __get($name)
    {
        if (property_exists(__CLASS__, $name)) {
            return $this->$name;
        }
    }

    protected function bootstrap()
    {
        define('RPPB_ABSPATH', dirname(RAMPHOR_PRODUCT_BUNDLES_PLUGIN_FILE));

        // Require helpers
        require_once RPPB_ABSPATH . '/functions.php';

        if ($this->is_request('frontend')) {
            require_once RPPB_ABSPATH . '/template-functions.php';
            require_once RPPB_ABSPATH . '/template-hooks.php';
        }
    }

    protected function initHooks()
    {
        add_action('plugins_loaded', array($this, 'detectShopPlugins'));

        add_action('after_setup_theme', array($this, 'loadModules'));
        add_action('after_setup_theme', array($this, 'callModules'), 15);
    }

    public function detectShopPlugins()
    {
        $active_plugins = get_option('active_plugins', array());
        $shop_plugins = array();
        if (in_array('woocommerce/woocommerce.php', $active_plugins)) {
            array_push($shop_plugins, 'woocommerce');
        }
        $this->shop_plugins = apply_filters(
            'ramphor_product_bundles_plugin_supports',
            $shop_plugins
        );
    }


    public function loadModules()
    {
        // Load builtin modules
        $loaded_modules = ModuleManager::get_modules();

        $this->modules = apply_filters(
            'ramphor_product_bundles_modules',
            $loaded_modules
        );
    }

    public function callModules()
    {
        // Ramphor Product Bundles plugin only workings with another ecommerce plugin
        if (empty($this->shop_plugins)) {
            return;
        }

        foreach ($this->shop_plugins as $shop_plugin) {
            if (empty($this->modules[$shop_plugin]) || !is_array($this->modules[$shop_plugin])) {
                continue;
            }
            $modules = $this->modules[$shop_plugin];
            foreach ($modules as $module) {
                $this->callModule($module);
            }
        }
    }

    protected function callModule($clsModule)
    {
        $module = $clsModule;
        if (class_exists($clsModule)) {
            $module = new $clsModule();
        }

        if (!is_a($module, ModuleInterface::class)) {
            return;
        }

        $module->load_module();

        // Auto require files via includes method
        $include_callable = array($module, 'includes');
        if (is_callable($include_callable)) {
            call_user_func($include_callable);
        }

        // Auto call init method when it is defined
        $init_callable = array($module, 'init');
        if (is_callable($init_callable)) {
            add_action('init', $init_callable);
        }
    }

    protected function is_request($request) {
        switch($request) {
            case 'frontend':
                return !is_admin() && !defined('DOING_AJAX') && !defined('DOING_CRON');
        }
        return false;
    }
}
