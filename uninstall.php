<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'category_cart_restrictions_rules' );
delete_option( 'category_cart_restrictions_display_style' );
