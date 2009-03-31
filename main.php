<?php
/*
Plugin Name: Smart Archives Reloaded
Version: 1.4.4
Description: An elegant and easy way to present your archives.
Author: scribu
Author URI: http://scribu.net
Plugin URI: http://scribu.net/wordpress/smart-archives-reloaded

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

class displaySAR {
	private $cache;

	public function __construct() {
		$this->cache = dirname(__FILE__) . '/cache.txt';

		// Set up cron hook
		add_action('smart_archives_update', array($this, 'generate'));

		// Set up shortcode
		add_shortcode('smart_archives', array($this, 'load'));
	}

	public function load() {
		$cache = @file_get_contents($this->cache);

		// Use cache if available
		return $cache ? $cache : $this->generate(false);
	}

	public function generate($require_cache = true) {
		global $SAR_options, $wpdb;

		if ( !$fh = @fopen($this->cache, 'w') ) {
			trigger_error("Can't open cache file: {$this->cache}", E_USER_WARNING);

			if ( $require_cache )
				return false; // exit if we can't write to file
		}

		// Extract options
		extract($SAR_options->get());

		if ( $catID )
			$exclude_cats_sql = sprintf("
				AND ID NOT IN (
					SELECT r.object_id
					FROM {$wpdb->term_relationships} r NATURAL JOIN {$wpdb->term_taxonomy} t
					WHERE t.taxonomy = 'category'
					AND t.term_id IN (%s)
				)
			", str_replace(' ', ',', $catID));

		// Get years with posts
		$query = $wpdb->prepare("
			SELECT DISTINCT year(post_date) AS year
			FROM {$wpdb->posts}
			WHERE post_type = 'post'
			AND post_status = 'publish'
			{$exclude_cats_sql}
			GROUP BY year(post_date)
			HAVING count(year(post_date)) > 0
			ORDER BY post_date DESC
		");
		$yearsWithPosts = $wpdb->get_col($query);

		if ( !$yearsWithPosts )
			return false;

		// Get months with posts
		foreach ( $yearsWithPosts as $current )
			for ( $i = 1; $i <= 12; $i++ ) {
				$query = $wpdb->prepare("
					SELECT ID, post_title
					FROM {$wpdb->posts}
					WHERE post_type = 'post'
					AND post_status = 'publish'
					AND year(post_date) = {$current}
					AND month(post_date) = {$i}
					{$exclude_cats_sql}
					ORDER BY post_date DESC
				");

				if ( $posts = $wpdb->get_results($query) ) {
					$monthsWithPosts[$current][$i]['posts'] = $posts;
					$monthsWithPosts[$current][$i]['link'] = get_month_link($current, $i);
				}
			}

		// The block
		if ( $format != 'list' ) {
			$months_short = $this->get_months(true);

			foreach ( $yearsWithPosts as $current ) {
				$block .= sprintf("\t<li><strong><a href='%s'>%s</a>:</strong> ", get_year_link($current), $current);

				for ( $i = 1; $i <= 12; $i++ )
					if ( $monthsWithPosts[$current][$i]['posts'] ) {
						$url = $anchors ? "#{$current}{$i}" : $monthsWithPosts[$current][$i]['link'];
						$block .= sprintf("\n\t\t<a href='%s'>%s</a>", $url, $months_short[$i]);
					} else
						$block .= sprintf("\n\t\t<span class='emptymonth'>%s</span>", $months_short[$i]);

				$block .= "\n</li>\n";
			}

			// Wrap it up
			$block = "<ul id='smart-archives-block'>\n{$block}</ul>\n";
		}

		// The list
		if ( $format != 'block' ) {
			$months_long = $this->get_months();

			foreach ( $yearsWithPosts as $current )
				for ( $i = 12; $i >= 1; $i-- ) {
					if ( !$monthsWithPosts[$current][$i] )
						continue;

					// Get post links for current month
					$post_list = '';
					foreach ( $monthsWithPosts[$current][$i]['posts'] as $post )
						$post_list .= sprintf("\t<li><a href='%s'>%s</a></li>\n", get_permalink($post->ID), $post->post_title);

					// Set title format
					if ( $anchors ) {
						$anchor = "{$current}{$i}";
						$titlef = "\n<h2 id='{$anchor}'><a href='%s'>%s</a></h2>\n";
					} else
						$titlef = "\n<h2><a href='%s'>%s</a></h2>\n";

					// Append to list
					$list .= sprintf($titlef, $monthsWithPosts[$current][$i]['link'], $months_long[$i].' '.$current);
					$list .= sprintf("<ul>\n%s</ul>\n", $post_list);
				}

			// Wrap it up
			$list = "\n<div id='smart-archives-list'>\n{$list}</div>\n";
		}

		// Update cache
		@fwrite($fh, $block.$list);
		@fclose($fh);

		return $block.$list;
	}

	private function get_months($abrev = false) {
		global $wp_locale;
	
		for($i = 1; $i <= 12; $i++) {
			$month = $wp_locale->get_month($i);

			if ( $abrev )
				$month = $wp_locale->get_month_abbrev($month);

			$months[$i] = htmlentities($month);
		}

		return $months;
	}
}

// Init
// Load options class if needed
if ( !class_exists('scbOptions_06') )
	require_once(dirname(__FILE__) . '/inc/scbOptions.php');

// Create an instance of each class
$GLOBALS['SAR_options'] = new scbOptions_06('smart-archives');
$GLOBALS['SAR_display'] = new displaySAR();

if ( is_admin() ) {
	require_once(dirname(__FILE__) . '/admin.php');
	new settingsSAR(__FILE__);
}

// Template tag
function smart_archives() {
	global $SAR_display;

	echo $SAR_display->load();
}

