<?php
/*
Plugin Name: Smart Archives Reloaded
Version: 1.8
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
	echo displaySAR::load($args);
}


_sar_init();
function _sar_init() {
	// Load scbFramework
	require_once dirname(__FILE__) . '/scb/load.php';

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

	displaySAR::init($options);

	if ( is_admin() ) {
		require_once dirname(__FILE__) . '/admin.php';
		new settingsSAR(__FILE__, $options);
	}

	// Load translations
	$plugin_dir = basename(dirname(__FILE__));
	load_plugin_textdomain('smart-archives-reloaded', "wp-content/plugins/$plugin_dir/lang", "$plugin_dir/lang");
}

abstract class displaySAR {
	const hook = 'smart_archives_update';

	private static $options;

	private static $yearsWithPosts;
	private static $monthsWithPosts;
	private static $cache_dir;

	static function init($options) {
		self::$options = $options;

		// Set cache dir
		$wud = wp_upload_dir();
		self::$cache_dir = $wud['basedir'] . '/sar_cache/';
		if ( !is_dir(self::$cache_dir) )
			@mkdir(self::$cache_dir);

		// Set cron hook
		add_action(self::hook, array(__CLASS__, 'clear_cache'));

		// Set shortcode
		add_shortcode('smart_archives', array(__CLASS__, 'load'));

		// Set fancy archive
		if ( self::$options->format == 'fancy' )
			add_action('template_redirect', array(__CLASS__, 'add_scripts'));
			
		// Install / uninstall
		register_activation_hook(__FILE__, array(__CLASS__, 'upgrade'));
		register_uninstall_hook(__FILE__, array(__CLASS__, 'clear_cache'));
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

	static function add_scripts() {
		// TODO: check static var in footer

		$plugin_url = plugin_dir_url(__FILE__);

		wp_enqueue_script('tools-tabs', $plugin_url . 'inc/tools.tabs.min.js', array('jquery'), '1.0.4', true);

		wp_enqueue_style('fancy-archives-css', $plugin_url . 'inc/fancy-archives.css', array(), '0.1');

		add_action('wp_footer', array(__CLASS__, 'init_fancy'), 20);
	}

	static function init_fancy() {
?>
<script type="text/javascript">
jQuery(document).ready(function($) {
	$('.tabs').tabs('> .pane');
	$('#smart-archives')
		.find('a').click(function(ev) {
			$('.pane .tabs:visible a:last').click();
		}).end()
		.find('a:last').click();
});
</script>
<?php
	}

	static function clear_cache() {
		$dir_handle = @opendir(self::$cache_dir);

		if ( FALSE == $dir_handle )
			return;

		while ( $file = readdir($dir_handle) )
			if ( $file != "." && $file != ".." )
				unlink(self::$cache_dir . DIRECTORY_SEPARATOR . $file);

		@closedir($dir_handle);
		@rmdir(self::$cache_dir);
	}

	static function load($args = '') {
		$args = self::validate_args($args);

		$file = self::$cache_dir . md5(join('', $args));

		$cache = @file_get_contents($file);

		return $cache ? $cache : self::generate($args, $file);
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

	private static function generate($args, $file) {
		global $wpdb;

		extract($args, EXTR_SKIP);

		if ( ! empty($exclude_cat) ) {
			$where = "AND ID NOT IN (";
			$ids = $exclude_cat;
		}

		if ( ! empty($include_cat) ) {
			$where = "AND ID IN (\n";
			$ids = $include_cat;
		}

		if ( ! empty($ids) ) {
			if ( ! is_array($ids) )
				$ids = explode(',', $ids);

			$ids = array_to_sql($ids);

			$where .= "
					SELECT r.object_id
					FROM {$wpdb->term_relationships} r NATURAL JOIN {$wpdb->term_taxonomy} t
					WHERE t.taxonomy = 'category'
					AND t.term_id IN ($ids)
				)
			";
		}

		$order = ( $format == 'fancy' ) ? 'ASC' : 'DESC';

		// Get non-empty years
		$query = "
			SELECT DISTINCT year(post_date) AS year
			FROM {$wpdb->posts}
			WHERE post_type = 'post'
			AND post_status = 'publish'
			{$where}
			GROUP BY year(post_date)
			HAVING count(year(post_date)) > 0
			ORDER BY post_date $order
		";

		self::$yearsWithPosts = $wpdb->get_col($query);

		if ( ! self::$yearsWithPosts )
			return false;

		$columns = self::get_columns();

		// Get months with posts
		foreach ( self::$yearsWithPosts as $current ) {
			for ( $i = 1; $i <= 12; $i++ ) {
				$query = $wpdb->prepare("
					SELECT {$columns}
					FROM {$wpdb->posts}
					WHERE post_type = 'post'
					AND post_status = 'publish'
					AND year(post_date) = {$current}
					AND month(post_date) = {$i}
					{$where}
					ORDER BY post_date DESC
				");

				if ( $posts = $wpdb->get_results($query) ) {
					$month = array(
						'posts' => $posts,
						'link' => get_month_link($current, $i)
					);

					self::$monthsWithPosts[$current][$i] = $month;
				}
			}
		}

		switch ( $format ) {
			case 'block': $output = self::generate_block(); break;
			case 'list': $output = self::generate_list(); break;
			case 'both': $output = self::generate_block() . self::generate_list(); break;
			case 'fancy': $output = self::generate_fancy(); break;
		}

		// Update cache
		@file_put_contents($file, $output);

		return $output;
	}

	// The "fancy" archive
	private static function generate_fancy() {
		$available_tags = self::get_available_tags();

		foreach ( $available_tags as $i => $tag )
			if ( FALSE === strpos(self::$options->list_format, $tag) )
				unset($available_tags[$i]);
		
		$months_long = self::get_months();
		$months_short = self::get_months(true);

		foreach ( self::$yearsWithPosts as $current )
			$years .= sprintf("\t<li class='list-%s'><a href='%s'>%s</a></li>", $current, get_year_link($current), $current);

		$years = "<ul id='smart-archives' class='tabs'>\n" . $years . "</ul>\n";

		$months = '';
		foreach ( self::$yearsWithPosts as $current ) {
			// Generate top panes
			$months .= sprintf("\n\t\t<div class='pane'>\n\t\t\t<ul id='month-list-%s' class='tabs month-list'>", $current);
			for ( $i = 1; $i <= 12; $i++ ) {
				if ( self::$options->block_numeric )
					$month = sprintf('%02d', $i);
				else
					$month = $months_short[$i];

				if ( self::$monthsWithPosts[$current][$i]['posts'] ) {
					$url = self::$monthsWithPosts[$current][$i]['link'];
					$months .= sprintf("\n\t\t<li><a href='%s'>%s</a></li>", $url, $month);
				} else {
					$months .= sprintf("\n\t\t<li><span class='emptymonth'>%s</span></li>", $month);
				}
			}
			$months .= "\n\t\t\t</ul>";

			// Generate post lists
			for ( $i = 1; $i <= 12; $i++ ) {
				if ( ! self::$monthsWithPosts[$current][$i] )
					continue;

				// Get post links for current month
				$post_list = '';
				foreach ( self::$monthsWithPosts[$current][$i]['posts'] as $post ) {
					// $post = self::$monthsWithPosts[$current][$i]['posts'];
					
					$list_item = self::$options->list_format;
							
					foreach ( $available_tags as $tag )
						$list_item = str_replace($tag, call_user_func(array(__CLASS__, 'substitute_' . substr($tag, 1, -1)), $post), $list_item);

					$post_list .= "\t<li>" . $list_item . "</li>\n";
				} // end post block

				$titlef = "\n<h2 class='month-heading'>%s <span class='month-archive-link'>(<a href='%s'>" . 
					__('View complete archive page for %s', 'smart-archives-reloaded') . 
					"</a>)</span></h2>\n";

				// Append to list
				$list .= "<div id='{$anchor}' class='pane'>";
				$list .= sprintf($titlef, $months_long[$i] . ' ' . $current, self::$monthsWithPosts[$current][$i]['link'], $months_long[$i] . ' ' . $current);
				$list .= sprintf("<ul class='archive-list'>\n%s</ul>\n", $post_list);
				$list .= "</div>";
			} // end month block

			$block .= $months . $list;
			$block .= "\n\t\t</div>";
			$list = "";
			$months = "";
		} // end year block

		// Wrap it up
		$block = $years . $block;

		return $block;
	}

	// The list
	private static function generate_list() {
		$available_tags = self::get_available_tags();

		foreach ( $available_tags as $i => $tag )
			if ( FALSE === strpos(self::$options->list_format, $tag) )
				unset($available_tags[$i]);

		$months_long = self::get_months();

		foreach ( self::$yearsWithPosts as $current ) {
			for ( $i = 12; $i >= 1; $i-- ) {
				if ( ! self::$monthsWithPosts[$current][$i] )
					continue;

				// Get post links for current month
				$post_list = '';
				foreach ( self::$monthsWithPosts[$current][$i]['posts'] as $post ) {
					$list_item = self::$options->list_format;

					foreach ( $available_tags as $tag ) {
						$method = 'substitute_' . substr($tag, 1, -1);
						$list_item = str_replace($tag, self::$method($post), $list_item);
					}

					$post_list .= "\t<li>" . $list_item . "</li>\n";
				} // end post block

				// Set title format
				if ( self::$options->anchors ) {
					$anchor = "{$current}{$i}";
					$titlef = "\n<h2 id='{$anchor}'><a href='%s'>%s</a></h2>\n";
				} else {
					$titlef = "\n<h2><a href='%s'>%s</a></h2>\n";
				}

				// Append to list
				$list .= sprintf($titlef, self::$monthsWithPosts[$current][$i]['link'], $months_long[$i] . ' ' . $current);
				$list .= sprintf("<ul>\n%s</ul>\n", $post_list);
			} // end month block
		} // end year block

		// Wrap it up
		$list = "\n<div id='smart-archives-list'>\n{$list}</div>\n";

		return $list;
	}

	// The block
	private static function generate_block() {
		$months_short = self::get_months(true);

		foreach ( self::$yearsWithPosts as $current ) {
			$block .= sprintf("\t<li><strong><a href='%s'>%s</a>:</strong> ", get_year_link($current), $current);

			for ( $i = 1; $i <= 12; $i++ )
			{
				if ( self::$options->block_numeric )
					$month = sprintf('%02d', $i);
				else
					$month = $months_short[$i];

				if ( self::$monthsWithPosts[$current][$i]['posts'] )
				{
					if ( self::$options->anchors )
						$url = "#{$current}{$i}";
					else
					 	$url = self::$monthsWithPosts[$current][$i]['link'];

					$block .= sprintf("\n\t\t<a href='%s'>%s</a>", $url, $month);
				}
				else
					$block .= sprintf("\n\t\t<span class='emptymonth'>%s</span>", $month);
			}

			$block .= "\n</li>\n";
		}

		// Wrap it up
		$block = "<ul id='smart-archives-block'>\n{$block}</ul>\n";

		return $block;
	}

	private static function get_months($abrev = false) {
		global $wp_locale;
	
		for($i = 1; $i <= 12; $i++) {
			$month = $wp_locale->get_month($i);

			if ( $abrev )
				$month = $wp_locale->get_month_abbrev($month);

			$months[$i] = esc_html($month);
		}

		return $months;
	}

	// Substitution tags
	function get_available_tags() {
		return array('%post_link%', '%author_link%', '%author%', '%comment_count%', '%category_link%', '%category%', '%date%');
	}

	private static function get_columns() {
		$columns = array('ID', 'post_title');

		if ( 'block' == self::$options->format )
			return implode(',', $columns);

		if ( FALSE !== strpos(self::$options->list_format, '%author') )
			$columns[] = 'post_author';

		if ( FALSE !== strpos(self::$options->list_format, '%comment') )
			$columns[] = 'comment_count';

		if ( FALSE !== strpos(self::$options->list_format, '%date') )
			$columns[] = 'post_date';

		return implode(',', $columns);
	}

	private static function substitute_post_link($post) {
		return sprintf("<a href='%s'>%s</a>", 
			get_permalink($post->ID),
			apply_filters('smart_archives_title', $post->post_title, $post->ID)
		);
	}

	private static function substitute_author_link($post) {
		return sprintf("<a href='%s'>%s</a>", 
			get_author_posts_url($post->post_author), 
			get_user_option('display_name', $post->post_author)
		);
	}

	private static function substitute_author($post) {
		return get_user_option('display_name', $post->post_author);
	}

	private static function substitute_comment_count($post) {
		return $post->comment_count;
	}

	private static function substitute_date($post) {
		return sprintf("<span class='post_date'>%s</span>", mysql2date(self::$options->date_format, $post->post_date));
	}

	private static function substitute_category_link($post) {
		$categorylist = array();
		foreach ( get_the_category($post->ID) as $category )
			$categorylist[] = sprintf("<a href='%s'>%s</a>", get_category_link($category->cat_ID), $category->cat_name);

		return implode(', ', $categorylist);
	}

	private static function substitute_category($post) {
		$categorylist = array();
		foreach ( get_the_category($post->ID) as $category )
			$categorylist[] = $category->cat_name;

		return implode(', ', $categorylist);
	}
}

// Utilities
if ( ! function_exists('array_to_sql') ) :
function array_to_sql($values) {
	foreach ( $values as &$val )
		$val = "'" . esc_sql(trim($val)) . "'";

	return implode(',', $values);
}
endif;

