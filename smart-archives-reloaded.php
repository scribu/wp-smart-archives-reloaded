<?php
/*
Plugin Name: Smart Archives Reloaded
Version: 2.0.5
Description: An elegant and easy way to present your posts, grouped by year and month.
Author: scribu
Author URI: http://scribu.net
Plugin URI: http://scribu.net/wordpress/smart-archives-reloaded
Text Domain: smart-archives-reloaded
Domain Path: /lang
*/

require dirname( __FILE__ ) . '/scb/load.php';

define( 'SAR_VERSION', '2.0' );

function _sar_init() {
	load_plugin_textdomain( 'smart-archives-reloaded', '', dirname( plugin_basename( __FILE__ ) ) . '/lang' );

	require_once dirname( __FILE__ ) . '/core.php';
	require_once dirname( __FILE__ ) . '/generator.php';

	$options = new scbOptions( 'smart-archives', __FILE__, array(
		'format' => 'both',
		'list_format' => '%post_link%',
		'date_format' => get_option( 'date_format' ),
		'anchors' => false,
		'month_format' => 'short',

		'posts_per_month' => -1,
		'include_cat' => array(),
		'exclude_cat' => array(),
	) );

	SAR_Core::init( $options );

	if ( is_admin() ) {
		require_once dirname( __FILE__ ) . '/admin/admin.php';
		scbAdminPage::register( 'SAR_Settings', __FILE__, $options );
	}
}
scb_init( '_sar_init' );

