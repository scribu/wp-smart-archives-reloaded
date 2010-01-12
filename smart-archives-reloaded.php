<?php
/*
Plugin Name: Smart Archives Reloaded
Version: 1.9
Description: An elegant and easy way to present your archives. (With help from <a href="http://www.conceptfusion.co.nz/">Simon Pritchard</a>)
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
	echo SAR_Core::load($args);
}

/* If you want to extend the SAR_Generator class, call this function first */
function smart_archives_load_default_generator() {
	SAR_Core::load_default_generator();
}


_sar_init();
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
		'cron' => true
	));

	SAR_Core::init($options);

	if ( is_admin() ) {
		require_once dirname(__FILE__) . '/admin.php';
		new SAR_Settings(__FILE__, $options);
	}
}

class SAR_Core {
	const hook = 'smart_archives_update';
	static $override_cron = false;

	private static $options;

	private static $fancy = false;
	private static $css = false;

	private static $cache_dir;

	// Substitution tags
	static function get_available_tags() {
		return array('%post_link%', '%author_link%', '%author%', '%comment_count%', '%category_link%', '%category%', '%date%');
	}

	static function init($options) {
		self::$options = $options;

		// Set cron hook
		add_action(self::hook, array(__CLASS__, 'clear_cache'));

		// Set shortcode
		add_shortcode('smart_archives', array(__CLASS__, 'load'));

		// Set fancy archive
		add_action('wp_footer', array(__CLASS__, 'init_fancy'), 20);

		// Cache invalidation
		add_action('transition_post_status', array(__CLASS__, 'update_cache'), 10, 2);
		add_action('deleted_post', array(__CLASS__, 'update_cache'), 10, 0);
		add_action('wp_update_comment_count', array(__CLASS__, 'update_cache'), 10, 0);

		// Install / uninstall
		register_activation_hook(__FILE__, array(__CLASS__, 'upgrade'));
		register_uninstall_hook(__FILE__, array(__CLASS__, 'clear_cache'));
	}

	static function update_cache($new_status = '', $old_status = '') {
		$cond =
			( 'publish' == $new_status || 'publish' == $old_status ) ||		// publish or unpublish
			( func_num_args() == 0 );

		if ( !$cond )
			return;

		if ( self::$options->cron && ! self::$override_cron ) {
			wp_clear_scheduled_hook(self::hook);
			wp_schedule_single_event(time(), self::hook);
		} else {
			do_action(self::hook);
		}
	}

	static function upgrade() {
		$wud = wp_upload_dir();
		@unlink($wud['basedir'] . '/sar_cache.txt');
		
		$options = self::$options->get();

		if ( isset($options['catID']) && empty($options['exclude_cat']) )
			$options['exclude_cat'] = explode(' ', $options['catID']);

		unset($options['catID']);

		self::$options->update($options);
	}

	static function init_fancy() {
		$add_css = apply_filters('smart_archives_load_default_styles', self::$css);

		if ( !self::$fancy && !$add_css )
			return;
	
		$css_dev = defined('STYLE_DEBUG') && STYLE_DEBUG ? '.dev' : '';

		$plugin_url = plugin_dir_url(__FILE__) . 'inc/';

		$css_url = add_query_arg('ver', '1.8', $plugin_url . "styles$css_dev.css");

		wp_register_script('tools-tabs', $plugin_url . 'tools.tabs.min.js', array('jquery'), '1.0.4', true);

		scbUtil::do_scripts('jquery');
		if ( self::$fancy )
			scbUtil::do_scripts('tools-tabs');
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

	static function load($args = '') {
		$args = self::validate_args($args);

		$generator = '';
		if ( isset($args['generator']) ) {
			$generator = $args['generator'];
			unset($args['generator']);
		}
		
		if ( 'fancy' == $args['format'] )
			self::$fancy = true;

		if ( in_array($args['format'], array('menu', 'fancy')) )
			self::$css = true;

		if ( 'menu' == $args['format'] )
			return self::generate($args, $generator);

		$file = self::get_cache_path(md5(@implode('', $args)));

		$cache = @file_get_contents($file);

		if ( empty($cache) ) {
			$cache = self::generate($args, $generator);
			@file_put_contents($file, $cache);
		}

		return $cache;
	}

	private function generate($args, $generator = '') {
		if ( !empty($generator) )
			return call_user_func(array($generator, 'generate'), $args);

		self::load_default_generator();
		return SAR_Generator::generate($args);
	}

	public function load_default_generator() {
		require_once dirname(__FILE__) . '/generator.php';	
	}

	static function clear_cache() {
		$cache_dir = self::get_cache_path('', false);
		$dir_handle = @opendir($cache_dir);

		if ( FALSE == $dir_handle )
			return;

		while ( $file = readdir($dir_handle) )
			if ( $file != "." && $file != ".." )
				unlink(self::get_cache_path($file));

		@closedir($dir_handle);
		@rmdir($cache_dir);
	}

	static function get_cache_path($file = '', $create = true) {
		// Set cache dir
		if ( empty(self::$cache_dir) ) {
			$wud = wp_upload_dir();
			self::$cache_dir = $wud['basedir'] . '/sar_cache/';
			if ( $create && !is_dir(self::$cache_dir) )
				@mkdir(self::$cache_dir);
		}

		return self::$cache_dir . $file;
	}

	private static function validate_args($args) {
		$args = wp_parse_args($args, self::$options->get());

		$args = self::sanitize_args($args);

		unset($args['cron']);

		ksort($args);

		return $args;
	}

	public static function sanitize_args($args) {
		$args = wp_parse_args($args, self::$options->get_defaults());

		// Category IDs
		if ( isset($args['include_cat']) && !empty($args['include_cat']) ) {
			$args['include_cat'] = self::parse_id_list($args['include_cat']);
			$args['exclude_cat'] = array();
		}
		else {
			$args['exclude_cat'] = self::parse_id_list($args['exclude_cat']);
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

		return $args;
	}

	private function parse_id_list($list) {
		$ids = array();

		if ( !is_array($list) )
			$list = preg_split('/[\s,]+/', $list);

		foreach ( $list as $id )
			if ( $id = absint($id) )
				$ids[] = $id;

		return array_unique($ids);
	}
}

