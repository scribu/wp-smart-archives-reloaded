<?php
/*
Plugin Name: Smart Archives Reloaded
Version: 1.6.2
Description: An elegant and easy way to present your archives.
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

// Init
_sar_init();
function _sar_init()
{
	// Load scbFramework
	require_once dirname(__FILE__) . '/scb/load.php';

	// Create an instance of each class
	$options = new scbOptions('smart-archives', __FILE__, array(
		'format' => 'both',
		'catID' => '',
		'anchors' => '',
		'block_numeric' => '',
		'list_format' => '%post_link%',
		'cron' => true
	));

	displaySAR::init($options);

	if ( is_admin() )
	{
		require_once dirname(__FILE__) . '/admin.php';
		new settingsSAR(__FILE__, $options);
	}
}

abstract class displaySAR
{
	const hook = 'smart_archives_update';

	private static $options;

	private static $yearsWithPosts;
	private static $monthsWithPosts;
	private static $cache;

	static function init($options)
	{
		self::$options = $options;

		// Set cache path
		$wud = wp_upload_dir();
		self::$cache = $wud['basedir'] . '/sar_cache.txt';

		// Set up cron hook
		add_action(self::hook, array(__CLASS__, 'generate'));

		// Set up shortcode
		add_shortcode('smart_archives', array(__CLASS__, 'load'));
	}

	static function load()
	{
		$cache = @file_get_contents(self::$cache);

		return $cache ? $cache : self::generate(true);
	}

	static function generate($display_anyway = false)
	{
		if ( ! $display_anyway && !$fh = @fopen(self::$cache, 'w') )
		{
			trigger_error("Can't open cache file: " . self::$cache, E_USER_WARNING);

			return false;
		}

		global $wpdb;

		extract(self::$options->get());

		$catID = @explode(' ', trim($catID));
		$catID = (array) apply_filters('smart_archives_exclude_categories', $catID);
		array_map('esc_sql', $catID);
		$catID = @implode(',', $catID);

		if ( ! empty($catID) )
			$exclude_cats_sql = "
				AND ID NOT IN (
					SELECT r.object_id
					FROM {$wpdb->term_relationships} r NATURAL JOIN {$wpdb->term_taxonomy} t
					WHERE t.taxonomy = 'category'
					AND t.term_id IN ($catID)
				)
			";

		// Get non-empty years
		$query = "
			SELECT DISTINCT year(post_date) AS year
			FROM {$wpdb->posts}
			WHERE post_type = 'post'
			AND post_status = 'publish'
			{$exclude_cats_sql}
			GROUP BY year(post_date)
			HAVING count(year(post_date)) > 0
			ORDER BY post_date DESC
		";

		self::$yearsWithPosts = $wpdb->get_col($query);

		if ( ! self::$yearsWithPosts )
			return false;

		$columns = self::get_columns();

		// Get months with posts
		foreach ( self::$yearsWithPosts as $current )
			for ( $i = 1; $i <= 12; $i++ )
			{
				$query = $wpdb->prepare("
					SELECT {$columns}
					FROM {$wpdb->posts}
					WHERE post_type = 'post'
					AND post_status = 'publish'
					AND year(post_date) = {$current}
					AND month(post_date) = {$i}
					{$exclude_cats_sql}
					ORDER BY post_date DESC
				");

				if ( $posts = $wpdb->get_results($query) )
				{
					self::$monthsWithPosts[$current][$i]['posts'] = $posts;
					self::$monthsWithPosts[$current][$i]['link'] = get_month_link($current, $i);
				}
			}

		$output = '';

		if ( $format != 'list' )
			$output .= self::generate_block();

		if ( $format != 'block' )
			$output .= self::generate_list();

		// Update cache
		@fwrite($fh, $output);
		@fclose($fh);

		return $output;
	}

	private function get_columns()
	{
		$columns = array('ID', 'post_title');

		if ( 'block' == self::$options->format )
			return implode(',', $columns);

		if ( FALSE !== strpos(self::$options->list_format, '%author') )
			$columns[] = 'post_author';

		if ( FALSE !== strpos(self::$options->list_format, '%comment') )
			$columns[] = 'comment_count';

		return implode(',', $columns);
	}

	// The block
	private function generate_block()
	{
		$months_short = self::get_months(true);

		foreach ( self::$yearsWithPosts as $current )
		{
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

	// Substitution tags
	private function substitute_post_link($post)
	{
		return sprintf("<a href='%s'>%s</a>", 
			get_permalink($post->ID),
			apply_filters('smart_archives_title', $post->post_title, $post->ID)
		);
	}

	private function substitute_author_link($post)
	{
		return sprintf("<a href='%s'>%s</a>", 
			get_author_posts_url($post->post_author), 
			get_user_option('display_name', $post->post_author)
		);
	}

	private function substitute_author($post)
	{
		return get_user_option('display_name', $post->post_author);
	}

	private function substitute_comment_count($post)
	{
		return $post->comment_count;
	}

	function get_available_tags()
	{
		return array('%post_link%', '%author_link%', '%author%', '%comment_count%');
	}

	// The list
	private function generate_list()
	{
		$available_tags = self::get_available_tags();

		foreach ( $available_tags as $i => $tag )
			if ( FALSE === strpos(self::$options->list_format, $tag) )
				unset($available_tags[$i]);

		$months_long = self::get_months();

		foreach ( self::$yearsWithPosts as $current )
			for ( $i = 12; $i >= 1; $i-- )
			{
				if ( ! self::$monthsWithPosts[$current][$i] )
					continue;

				// Get post links for current month
				$post_list = '';
				foreach ( self::$monthsWithPosts[$current][$i]['posts'] as $post )
				{
					$list_item = self::$options->list_format;

					foreach ( $available_tags as $tag )
					{
						$method = 'substitute_' . substr($tag, 1, -1);
						$list_item = str_replace($tag, self::$method($post), $list_item);
					}

					$post_list .= "\t<li>" . $list_item . "</li>\n";
				}

				// Set title format
				if ( self::$options->anchors )
				{
					$anchor = "{$current}{$i}";
					$titlef = "\n<h2 id='{$anchor}'><a href='%s'>%s</a></h2>\n";
				} else
					$titlef = "\n<h2><a href='%s'>%s</a></h2>\n";

				// Append to list
				$list .= sprintf($titlef, self::$monthsWithPosts[$current][$i]['link'], $months_long[$i] . ' ' . $current);
				$list .= sprintf("<ul>\n%s</ul>\n", $post_list);
			}

		// Wrap it up
		$list = "\n<div id='smart-archives-list'>\n{$list}</div>\n";

		return $list;
	}

	private function get_months($abrev = false)
	{
		global $wp_locale;
	
		for($i = 1; $i <= 12; $i++)
		{
			$month = $wp_locale->get_month($i);

			if ( $abrev )
				$month = $wp_locale->get_month_abbrev($month);

			$months[$i] = esc_html($month);
		}

		return $months;
	}
}

// Template tag
function smart_archives()
{
	echo displaySAR::load();
}

