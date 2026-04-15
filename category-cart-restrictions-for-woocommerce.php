<?php

/*
Plugin Name: Category Cart Restrictions for WooCommerce
Plugin URI: https://wordpress.org/plugins/category-cart-restrictions-for-woocommerce
Requires Plugins: woocommerce
Description: Prevent customers from adding products from certain categories when conflicting category products are already in the cart.
Author: Senff
Author URI: http://www.senff.com
Version: 1.0
Requires at least: 6.0
Requires PHP: 7.4
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html
Text Domain: category-cart-restrictions-for-woocommerce
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CATEGORY_CART_RESTRICTIONS_VERSION',  '1.0' );
define( 'CATEGORY_CART_RESTRICTIONS_PLUGIN_DIR',  plugin_dir_path( __FILE__ ) );
define( 'CATEGORY_CART_RESTRICTIONS_PLUGIN_URL',  plugin_dir_url( __FILE__ ) );
define( 'CATEGORY_CART_RESTRICTIONS_BASENAME', plugin_basename( __FILE__ ) );

function category_cart_restrictions_init() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', function () {
			echo '<div class="error"><p>'
				. esc_html__( 'Category Cart Restrictions requires WooCommerce to be installed and active.', 'category-cart-restrictions-for-woocommerce' )
				. '</p></div>';
		} );
		return;
	}

	require_once CATEGORY_CART_RESTRICTIONS_PLUGIN_DIR . 'includes/class-category-cart-restrictions-admin.php';
	require_once CATEGORY_CART_RESTRICTIONS_PLUGIN_DIR . 'includes/class-category-cart-restrictions-restrictions.php';

	new Category_Cart_Restrictions_Admin();
	new Category_Cart_Restrictions_Restrictions();
}
add_action( 'plugins_loaded', 'category_cart_restrictions_init' );
