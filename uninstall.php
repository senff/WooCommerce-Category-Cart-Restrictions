<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'category_combination_restrictions_rules' );
delete_option( 'category_combination_restrictions_display_style' );
