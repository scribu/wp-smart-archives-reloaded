<?php
/*
Plugin Name: Smart Archives Reloaded
Version: 2.0a4
Description: An elegant and easy way to present your posts, grouped by month.
Author: scribu
Author URI: http://scribu.net
Plugin URI: http://scribu.net/wordpress/smart-archives-reloaded
Text Domain: smart-archives-reloaded
Domain Path: /lang

Copyright (C) 2009 scribu.net (scribu AT gmail DOT com)

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
 * Available args:
 * format: string, one of these: block | list | both | fancy
 * exclude_cat: array of category ids to exclude
 * include_cat: array of category ids to include
 * anchors: boolean
 * block_numeric: boolean
 * list_format: string
 * date_format: string
 */
function smart_archives($args = '') {
	echo SAR_Core::generate($args);
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

	function get_active_tags($format) {
		$active_tags = array();
		foreach ( self::get_available_tags() as $tag )
			if ( FALSE !== strpos($format, $tag) )
				$active_tags[] = $tag;

		return $active_tags;
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

		list($args, $qv) = self::validate_args($args, $qv);

		// scripts
		if ( 'fancy' == $args['format'] )
			self::$fancy = true;

		if ( in_array($args['format'], array('menu', 'fancy')) )
			self::$css = true;

		// query vars
		$exclude_cat = array_pop_key($args, 'exclude_cat');
		$include_cat = array_pop_key($args, 'include_cat');

		if ( !empty($exclude_cat) )
			$qv['category__not_in'] = $exclude_cat;
		elseif ( !empty($include_cat) )
			$qv['category__in'] = $include_cat;

		// generator
		$generator = array_pop_key($args, 'generator');

		if ( empty($generator) )
			$generator = new SAR_Generator();
		elseif ( is_string($generator) )
			$generator = new $generator;

		return $generator->generate($args, $qv);
	}

	function validate_args($args = '', $qv = '') {
		$args = wp_parse_args($args, self::$options->get_defaults());

		if ( empty($qv) )
			$qv = array();

		// Category IDs
		if ( isset($args['include_cat']) && !empty($args['include_cat']) ) {
			$args['include_cat'] = wp_parse_id_list($args['include_cat']);
			$args['exclude_cat'] = array();
		}
		else {
			$args['exclude_cat'] = wp_parse_id_list($args['exclude_cat']);
		}

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

		return array($args, $qv);
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
		'date_format' => 'F j, Y',
		'posts_per_month' => false,
		'include_cat' => array(),
		'exclude_cat' => array(),
		'anchors' => false,
		'month_format' => 'short',
	));

	require_once dirname(__FILE__) . '/query.php';
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

