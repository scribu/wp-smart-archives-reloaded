<?php
/*
Plugin Name: Smart Archives Reloaded
Version: 2.1a
Description: An elegant and easy way to present your posts, grouped by year and month.
Author: scribu
Author URI: http://scribu.net
Plugin URI: http://scribu.net/wordpress/smart-archives-reloaded
Text Domain: smart-archives-reloaded
Domain Path: /lang

Copyright (C) 2010 scribu (scribu AT gmail DOT com)

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


/**
 * Display the archives
 *
 * $args:
 * format: string, one of these: block | list | both | fancy
 * anchors: boolean
 * block_numeric: boolean
 * list_format: string
 * date_format: string
 *
 * $qv: see query_posts()
 */
function smart_archives($args = '', $qv = '') {
	echo SAR_Core::generate($args, $qv);
}

class SAR_Core {
	private static $options;

	private static $fancy = false;
	private static $css = false;

	static function get_available_tags() {
		return array(
			'%post_link%', 
			'%date%',
			'%excerpt%',
			'%author_link%', '%author%',
			'%comment_count%',
			'%category_link%', '%category%',
		);
	}

	static function init($options) {
		self::$options = $options;

		add_shortcode('smart_archives', array(__CLASS__, 'generate'));

		add_action('wp_footer', array(__CLASS__, 'add_scripts'), 20);

		register_activation_hook(__FILE__, array(__CLASS__, 'upgrade'));
	}

	static function upgrade() {
		$options = self::$options->get();

		$catID = array_pop_key($options, 'catID');

		if ( !empty($catID) && empty($options['exclude_cat']) )
			$options['exclude_cat'] = explode(' ', $catID);

		self::$options->update($options);
	}


	static function generate($args = '', $qv = '') {
		$args = wp_parse_args($args, self::$options->get());

		$args = self::validate_args($args);

		// scripts
		if ( 'fancy' == $args['format'] )
			self::$fancy = true;

		if ( in_array($args['format'], array('menu', 'fancy')) )
			self::$css = true;

		// query vars
		$map = array(
			'category__not_in' => 'exclude_cat',
			'category__in' => 'include_cat',
		);

		$tmp = array();
		foreach ( $map as $qv_key => $key )
			if ( isset($args[$key]) )
				$tmp[$qv_key] = array_pop_key($args, $key);

		$qv = wp_parse_args($qv, $tmp);

		// generator
		$generator = array_pop_key($args, 'generator');

		if ( empty($generator) )
			$generator = new SAR_Generator();
		elseif ( is_string($generator) )
			$generator = new $generator;

		return $generator->generate($args, $qv);
	}

	function validate_args($args) {
		$args = wp_parse_args($args, self::$options->get_defaults());

		// Category IDs
		foreach ( array('exclude_cat', 'include_cat') as $key )
			if ( !empty($args[$key]) )
				$args[$key] = wp_parse_id_list($args[$key]);

		// Anchors
		if ( 'both' != $args['format'] )
			$args['anchors'] = false;

		// Block numeric
		if ( array_key_exists('block_numeric', $args) ) {
			if ( 'block' == $args['format'] && ! array_key_exists('month_format', $args) )
				$args['month_format'] = $args['block_numeric'] ? 'numeric' : 'short';

			unset($args['block_numeric']);
		}

		// List format
		$args['list_format'] = trim($args['list_format']);

		return $args;
	}

	static function add_scripts() {
		$add_css = apply_filters('smart_archives_load_default_styles', self::$css);

		if ( !self::$fancy && !$add_css )
			return;

		$css_dev = defined('STYLE_DEBUG') && STYLE_DEBUG ? '.dev' : '';

		$plugin_url = plugin_dir_url(__FILE__) . 'inc/';

		$css_url = add_query_arg('ver', '1.8', $plugin_url . "styles$css_dev.css");

		wp_print_scripts('jquery');

		if ( self::$fancy ) {
			wp_register_script('tools-tabs', $plugin_url . 'tools.tabs.min.js', array('jquery'), '1.0.4', true);
			wp_print_scripts('tools-tabs');
		}
?>
<script type="text/javascript">
jQuery(document).ready(function($) {
<?php if ( $add_css ) : ?>
	$('head').prepend($('<link>').attr({
		rel: 'stylesheet',
		type: 'text/css',
		media: 'screen',
		href: '<?php echo $css_url; ?>'
	}));
<?php endif; ?>
<?php if ( self::$fancy ) : ?>
	$('.tabs').tabs('> .pane');
	$('#smart-archives-fancy .year-list')
		.find('a').click(function(ev) {
			$('.pane .tabs:visible a:last').click();
		}).end()
		.find('a:last').click();
<?php endif; ?>
});
</script>
<?php
	}
}

function _sar_init() {
	// Load scbFramework
	require_once dirname(__FILE__) . '/scb/load.php';

	// Load translations
	load_plugin_textdomain('smart-archives-reloaded', '', basename(dirname(__FILE__)) . '/lang');

	$options = new scbOptions('smart-archives', __FILE__, array(
		'format' => 'both',
		'list_format' => '%post_link%',
		'date_format' => get_option('date_format'),
		'anchors' => false,
		'month_format' => 'short',

		'posts_per_month' => -1,
		'include_cat' => array(),
		'exclude_cat' => array(),
	));

	require_once dirname(__FILE__) . '/generator.php';

	SAR_Core::init($options);

	if ( is_admin() ) {
		require_once dirname(__FILE__) . '/admin/admin.php';
		scbAdminPage::register('SAR_Settings', __FILE__, $options);
	}
}
_sar_init();


// WP < 3.0
if ( !function_exists('wp_parse_id_list') ) :
function wp_parse_id_list( $list ) {
	if ( !is_array($list) )
		$list = preg_split('/[\s,]+/', $list);

	return array_unique(array_map('absint', $list));
}
endif;


if ( !function_exists('array_pop_key') ) :
function array_pop_key($array, $key) {
	if ( !isset($array[$key]) )
		return null;

	$value = $array[$key];
	unset($array[$key]);

	return $value;
}
endif;

