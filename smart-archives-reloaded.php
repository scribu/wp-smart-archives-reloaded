<?php
/*
Plugin Name: Smart Archives Reloaded
Version: 1.8.3
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


_sar_init();
function _sar_init() {
	// Load scbFramework
	require_once dirname(__FILE__) . '/scb/load.php';

	// Load translations
	load_plugin_textdomain('smart-archives-reloaded', '', basename(dirname(__FILE__)) . '/lang');

	// Create an instance of each class
	$options = new scbOptions('smart-archives', __FILE__, array(
		'format' => 'both',
		'exclude_cat' => '',
		'anchors' => '',
		'block_numeric' => '',
		'list_format' => '%post_link%',
		'date_format' => 'F j, Y',
		'cron' => true
	));

	SAR_Core::init($options);

	if ( is_admin() ) {
		require_once dirname(__FILE__) . '/admin.php';
		new SAR_Settings(__FILE__, $options);
	}
}

abstract class SAR_Core {
	const hook = 'smart_archives_update';
	static $override_cron = false;

	static $options;

	private static $fancy = false;

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
		if ( self::$options->format == 'fancy' )
			add_action('template_redirect', array(__CLASS__, 'register_scripts'));

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

	static function register_scripts() {
		$css_dev = defined('STYLE_DEBUG') && STYLE_DEBUG ? '.dev' : '';

		$plugin_url = plugin_dir_url(__FILE__) . 'inc/';

		wp_enqueue_style('fancy-archives', $plugin_url . "fancy-archives$css_dev.css", array(), '1.8');

		wp_register_script('tools-tabs', $plugin_url . 'tools.tabs.min.js', array('jquery'), '1.0.4', true);

		add_action('wp_footer', array(__CLASS__, 'init_fancy'), 20);
	}

	static function init_fancy() {
		if ( ! self::$fancy )
			return;

		scbUtil::do_scripts('tools-tabs');

?>
<script type="text/javascript">
jQuery(document).ready(function($) {
	$('.tabs').tabs('> .pane');
	$('#smart-archives-fancy .years-list')
		.find('a').click(function(ev) {
			$('.pane .tabs:visible a:last').click();
		}).end()
		.find('a:last').click();
});
</script>
<?php
	}

	static function load($args = '') {
		$args = self::validate_args($args);

		if ( 'fancy' == $args['format'] )
			self::$fancy = true;

		$file = self::get_cache_path(md5(join('', $args)));

#DEBUG
		$cache = @file_get_contents($file);
#DEBUG

		if ( empty($cache) ) {
			require dirname(__FILE__) . '/generator.php';
			$cache = SAR_Generator::generate($args);
			@file_put_contents($file, $cache);
		}

		return $cache;
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

	private static $cache_dir;

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

		if ( isset($args['include_cat']) )
			unset($args['exclude_cat']);

		$whitelist = array(
			'format',
			'include_cat',
			'exclude_cat',
			'anchors',
			'block_numeric',
			'list_format',
			'date_format'
		);

		$final_args = array();
		foreach ( $whitelist as $key )
			if ( isset($args[$key]) )
				$final_args[$key] = $args[$key];

		ksort($args);

		return $final_args;
	}
}

