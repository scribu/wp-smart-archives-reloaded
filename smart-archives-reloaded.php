<?php
/*
Plugin Name: Smart Archives Reloaded
Version: 1.3.1
Description: An elegant and easy way to present your archives.
Author: scribu
Author URI: http://scribu.net
Plugin URI: http://scribu.net/projects/smart-archives-reloaded

Copyright (C) 2008 scribu.net (scribu AT gmail DOT com)

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
	private $options;
	private $cache;

	public function __construct(scbOptions $options) {
		$this->cache = dirname(__FILE__) . '/cache.txt';

		$this->options = $options;

		add_shortcode('smart_archives', array($this, 'load'));			// shortcode for displaying the archives
		add_action('smart_archives_update', array($this, 'generate'));	// hook for wp_cron
	}

	public function load() {
		$output = @file_get_contents($this->cache);

		return $output ? $output : $this->generate(false);
	}

	public function generate($require_cache = true) {
		if ( !$fh = @fopen($this->cache, 'w') ) {
			trigger_error("Can't open cache file: ".$this->cache, E_USER_WARNING);
		
			if ( $require_cache )
				return false; // exit if we can't write to file
		}

		global $wpdb;

		setlocale(LC_ALL, WPLANG);	// set localization language; please see instructions
		$bogusDate = "/01/2001";	// used for the strtotime() function below

		extract($this->options->get());

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
			HAVING count(ID) > 0
			ORDER BY post_date DESC
		");

		$yearsWithPosts = $wpdb->get_results($query);

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
					AND year(post_date) = {$current->year}
					AND month(post_date) = {$i}
					AND post_date < CURRENT_TIMESTAMP
					{$exclude_cats_sql}
					ORDER BY post_date DESC
				");

				$monthsWithPosts[$current->year][$i] = $wpdb->get_results($query);
			}

		// The block
		if ( $format != 'list' ) {
			// get the shortened month name; strftime() should localize
			for($i = 1; $i <= 12; $i++)
				$shortMonths[$i] = ucfirst(strftime("%b", strtotime($i.$bogusDate)));

			foreach ( $yearsWithPosts as $current ) {
				$block .= sprintf("\t<li><strong><a href='%s'>%s</a>:</strong> ", get_year_link($current->year), $current->year);

				for ( $i = 1; $i <= 12; $i++ )
					if ( $monthsWithPosts[$current->year][$i] ) {
						$url = $anchors ? "#{$current->year}{$i}" : get_month_link($current->year, $i);
						$block .= sprintf("<a href='%s'>%s</a> ", $url, $shortMonths[$i]);
					} else
						$block .= sprintf("\n\t\t<span class='emptymonth'>%s</span> ", $shortMonths[$i]);

				$block .= "\n</li>\n";
			}

			// Wrap it up
			$block = "<ul id='smart-archives-block'>\n{$block}</ul>\n";
		}

		// The list
		if ( $format != 'block' ) {
			// get the month name; strftime() should localize
			for ( $i = 1; $i <= 12; $i++ )
				$monthNames[$i] = ucfirst(strftime("%B", strtotime($i.$bogusDate)));

			foreach ( $yearsWithPosts as $current )
				for ( $i = 12; $i >= 1; $i-- ) {
					if ( !$monthsWithPosts[$current->year][$i] )
						continue;

					$month_list = '';

					foreach ( $monthsWithPosts[$current->year][$i] as $post )
						$month_list .= sprintf("\t<li><a href='%s'>%s</a></li>\n", get_permalink($post->ID), $post->post_title);

					if ( $month_list ) {
						if ( $anchors ) {
							$anchor = "{$current->year}{$i}";
							$titlef = "\n<h2 id='{$anchor}'><a href='%s'>%s</a></h2>\n";
						} else
							$titlef = "\n<h2><a href='%s'>%s</a></h2>\n";

						$list .= sprintf($titlef, get_month_link($current->year, $i), $monthNames[$i].' '.$current->year);
						$list .= sprintf("<ul>\n%s</ul>\n", $month_list);
					}
				}

			// Wrap it up
			$list = "\n<div id='smart-archives-list'>\n{$list}</div>\n";
		}
		@fwrite($fh, $block.$list);
		@fclose($fh);

		return $block.$list;
	}
}

// Init
global $SAR_options, $SAR_display;

if ( !class_exists('scbOptions') )
	require_once('inc/scbOptions.php');

$SAR_options = new scbOptions('smart-archives');
$SAR_display = new displaySAR($SAR_options);

if ( is_admin() ) {
	require_once(dirname(__FILE__).'/admin.php');
	new adminSAR(__FILE__);
}

// Template tag
function smart_archives() {
	global $SAR_display;
	echo $SAR_display->load();
}

