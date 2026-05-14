<?php

/*
Plugin Name: Category Combination Restrictions for WooCommerce
Plugin URI: https://wordpress.org/plugins/category-combination-restrictions-for-woocommerce
Requires Plugins: woocommerce
Description: Prevent customers from adding products from certain categories when conflicting category products are already in the cart.
Author: Senff
Author URI: http://www.senff.com
Version: 1.0
Requires at least: 6.0
Requires PHP: 7.4
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html
Text Domain: category-combination-restrictions-for-woocommerce
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CATEGORY_COMBINATION_RESTRICTIONS_VERSION',  '1.0' );
define( 'CATEGORY_COMBINATION_RESTRICTIONS_PLUGIN_DIR',  plugin_dir_path( __FILE__ ) );
define( 'CATEGORY_COMBINATION_RESTRICTIONS_PLUGIN_URL',  plugin_dir_url( __FILE__ ) );
define( 'CATEGORY_COMBINATION_RESTRICTIONS_BASENAME', plugin_basename( __FILE__ ) );

function category_combination_restrictions_init() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', function () {
			echo '<div class="error"><p>'
				. esc_html__( 'Category Combination Restrictions requires WooCommerce to be installed and active.', 'category-combination-restrictions-for-woocommerce' )
				. '</p></div>';
		} );
		return;
	}

	require_once CATEGORY_COMBINATION_RESTRICTIONS_PLUGIN_DIR . 'includes/class-category-combination-restrictions-admin.php';
	require_once CATEGORY_COMBINATION_RESTRICTIONS_PLUGIN_DIR . 'includes/class-category-combination-restrictions.php';

	new Category_Combination_Restrictions_Admin();
	new Category_Combination_Restrictions_Restrictions();
}
add_action( 'plugins_loaded', 'category_combination_restrictions_init' );
