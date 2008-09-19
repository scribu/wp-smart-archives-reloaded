<?php
/*
Plugin Name: Smart Archives Reloaded
Version: 1.2
Description: (<a href="options-general.php?page=smart-archives">Settings</a>) An elegant and easy way to present your archives.
Author: scribu
Author URI: http://scribu.net
Plugin URI: http://scribu.net/projects/smart-archives-reloaded.html

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

define('SAR_CACHE', dirname(__FILE__) . '/cache.txt');

class smartArchives {
	function __construct() {
		add_shortcode('smart_archives', array(&$this, 'load'));			// shortcode for displaying the archives
		add_action('smart_archives_update', array(&$this, 'generate'));	// hook for wp_cron
	}

	function display() {
		echo $this->load();
	}

	function load() {
		$output = @file_get_contents(SAR_CACHE);

		if ( $output )
			return $output;
		else {
			$this->generate();
			return file_get_contents(SAR_CACHE);
		}
	}

	function generate() {
		$fh = fopen(SAR_CACHE, 'w') or die("Can't open cache file!");

		global $wpdb;

		setlocale(LC_ALL, WPLANG);	// set localization language; please see instructions

		extract(get_option('smart-archives'));	// load options

		$bogusDate = "/01/2001";	// used for the strtotime() function below
		
		$query = $wpdb->prepare("
			SELECT DISTINCT year(post_date) AS year
			FROM $wpdb->posts
			WHERE post_type = 'post'
			AND post_status = 'publish'
			GROUP BY year(post_date)
			HAVING count(ID) > 0
			ORDER BY post_date DESC
		");

		$yearsWithPosts = $wpdb->get_results($query);

		if ( !$yearsWithPosts )
			return;

		foreach ( $yearsWithPosts as $current)
			for ( $i = 1; $i <= 12; $i++ ) {
				$query = $wpdb->prepare("
					SELECT ID, post_title
					FROM $wpdb->posts
					WHERE post_type = 'post'
					AND post_status = 'publish'
					AND year(post_date) = %d
					AND month(post_date) = %d
					AND post_date < CURRENT_TIMESTAMP
					ORDER BY post_date DESC
				", $current->year, $i);

				$monthsWithPosts[$current->year][$i] = $wpdb->get_results($query);
			}

		if ( $format == 'both' || $format == 'block' ) {
			// get the shortened month name; strftime() should localize
			for($i = 1; $i <= 12; $i++)
				$shortMonths[$i] = ucfirst(strftime("%b", strtotime($i.$bogusDate)));

			$archives .= "<ul id=\"smart-archives-block\">\n";

			foreach ( $yearsWithPosts as $current ) {
				$archives .= "\t".'<li><strong><a href="' . get_year_link($current->year) . '">' . $current->year . '</a>:</strong> ';

				for ( $i = 1; $i <= 12; $i++ )
					if ( $monthsWithPosts[$current->year][$i] )
						$archives .= '<a href="'.get_month_link($current->year, $i).'">'.$shortMonths[$i].'</a> ';
					else
						$archives .= '<span class="emptymonth">'.$shortMonths[$i].'</span> ';

				$archives .= "</li>\n";
			}

			$archives .= "</ul>\n";

			if ( $format == 'block' )
				fwrite($fh, $archives);	// write archives to file if not displaying list format
		}

		if ( $format == 'both' || $format == 'list' ) {
			// get the month name; strftime() should localize
			for ( $i = 1; $i <= 12; $i++ )
				$monthNames[$i] = ucfirst(strftime("%B", strtotime($i.$bogusDate)));

			$archives .= "\n<div id=\"smart-archives-list\">\n";

			$catIDs = explode(' ', $catID);	// put the category(ies) into an array

			foreach ( $yearsWithPosts as $current )
				for ( $i = 12; $i >= 1; $i-- ) {
					if ( !$monthsWithPosts[$current->year][$i] )
						continue;

					$tmp = '';

					foreach ( $monthsWithPosts[$current->year][$i] as $post ) {
						if ( !empty($catIDs) ) {
							$cats = wp_get_post_categories($post->ID);

							foreach ( $cats as $cat)
								if ( in_array($cat, $catIDs))
									continue 2;	// skip to next post
						}

						$tmp .= '<li><a href="'.get_permalink($post->ID).'">'.$post->post_title.'</a></li>'."\n";
					}

					if ( !$tmp )
						continue;

					$archives .= "\n" . '<h2><a href="' . get_month_link($current->year, $i) . '">' . $monthNames[$i] . ' ' . $current->year . '</a></h2>' . "\n";
					$archives .= "<ul>\n" . $tmp . "</ul>\n";
				}

			$archives .= "</div>\n";

			fwrite($fh, $archives);	// write archives to file
		}
		fclose($fh);	// close archives file
	}
}

// Init
if ( is_admin() ) {
	require_once('inc/admin.php');
	$smartArchivesAdmin	= new smartArchivesAdmin();

	register_activation_hook(__FILE__, array(&$smartArchivesAdmin, 'activate') );
	register_deactivation_hook(__FILE__, array(&$smartArchivesAdmin, 'deactivate') );
} else
	$smartArchives = new smartArchives();

// Template tag
function smart_archives() {
	global $smartArchives;
	$smartArchives->display();
}

