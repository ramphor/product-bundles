<?php
/**
 * Plugin Name: Ramphor Product Bundles
 * Plugin URI: https://github.com/ramphor/product-bundles
 * Author: Puleeno Nguyen
 * Author URI: https://puleeno.com
 * Version: 1.0.0
 * Description: Offer product bundles
 */

use Ramphor\ProductBundles\ProductBundles;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define('RAMPHOR_PRODUCT_BUNDLES_PLUGIN_FILE', __FILE__);

$composer_autoload = sprintf( '%s/vendor/autoload.php', dirname( __FILE__ ) );
if ( file_exists( $composer_autoload ) ) {
	require_once $composer_autoload;
}

if ( class_exists( ProductBundles::class ) ) {
	$GLOBALS['ramphor_product_bundles'] = ProductBundles::getInstance();
}
