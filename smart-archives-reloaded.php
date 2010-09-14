<?php
/*
Plugin Name: Smart Archives Reloaded
Version: 2.0.3
Description: An elegant and easy way to present your posts, grouped by year and month.
Author: scribu
Author URI: http://scribu.net
Plugin URI: http://scribu.net/wordpress/smart-archives-reloaded
Text Domain: smart-archives-reloaded
Domain Path: /lang


Copyright (C) 2010 Cristi Burcă (scribu@gmail.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

require dirname( __FILE__ ) . '/scb/load.php';

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

