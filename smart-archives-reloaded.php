<?php
/*
Plugin Name: Smart Archives Reloaded
Version: 1.0
Description: (<a href="options-general.php?page=smart-archives"><strong>Settings</strong></a>) A simple, clean and future-proof way to present your archives.
Author: scribu
Author URI: http://scribu.net
Plugin URI: http://scribu.net/projects/smart-archives-reloaded.html
*/

class smartArchives {
	var $cachefile;

	function __construct() {
		$this->cachefile = dirname(__FILE__) . '/cache.txt';

		add_action('smart_archives', array(&$this, 'display')); // add custom action for displaying the archives
		add_action('publish_post', array(&$this, 'generate')); // generate archives after a new post
	}

	function display() {
		if ( !file_exists($this->cachefile) )
			$this->generate();

		echo file_get_contents($this->cachefile);
	}

	function generate() {
		$fh = fopen($this->cachefile, 'w') or die('Could not open file!');

		global $wpdb;
		setlocale(LC_ALL, WPLANG); // set localization language; please see instructions

		extract(get_option('smart-archives')); // load options

		$now = gmdate("Y-m-d H:i:s",(time()+((get_settings('gmt_offset'))*3600))); // get the current GMT date
		$bogusDate = "/01/2001"; // used for the strtotime() function below

		$yearsWithPosts = $wpdb->get_results("
			SELECT DISTINCT year(post_date) AS `year`, count(ID) as posts
			FROM $wpdb->posts
			WHERE post_type = 'post'
			AND post_status = 'publish'
			GROUP BY year(post_date)
			ORDER BY post_date DESC");

		foreach ($yearsWithPosts as $currentYear)
			for ($currentMonth = 1; $currentMonth <= 12; $currentMonth++)
				$monthsWithPosts[$currentYear->year][$currentMonth] = $wpdb->get_results("
					SELECT ID, post_title FROM $wpdb->posts
					WHERE post_type = 'post'
					AND post_status = 'publish'
					AND year(post_date) = '$currentYear->year'
					AND month(post_date) = '$currentMonth'
					ORDER BY post_date DESC");

		if (($format == 'both') || ($format == 'block')) { // check to see if we are supposed to display the block

			// get the shortened month name; strftime() should localize
			for($currentMonth = 1; $currentMonth <= 12; $currentMonth++)
				$shortMonths[$currentMonth] = ucfirst(strftime("%b", strtotime("$currentMonth"."$bogusDate")));

			if ($yearsWithPosts) {
				foreach ($yearsWithPosts as $currentYear) {
					$archives .= '<strong><a href="'.get_year_link($currentYear->year, $currentYear->year).'">'.$currentYear->year.'</a>:</strong> ';

					for ($currentMonth = 1; $currentMonth <= 12; $currentMonth++)
						if ($monthsWithPosts[$currentYear->year][$currentMonth]) $archives .= '<a href="'.get_month_link($currentYear->year, $currentMonth).'">'.$shortMonths[$currentMonth].'</a> ';
						else $archives .= '<span class="emptymonth">'.$shortMonths[$currentMonth].'</span> ';

					$archives .= '<br />';
				}
				$archives .= '<br /><br />';
			}

			if ($format == 'block')
				fwrite($fh, $archives); // write archives to file if not displaying list format
		}

	if (($format == 'both') || ($format == 'list')) { //check to see if we are supposed to display the list
			// get the month name; strftime() should localize
			for($currentMonth = 1; $currentMonth <= 12; $currentMonth++)
				$monthNames[$currentMonth] = ucfirst(strftime("%B", strtotime("$currentMonth"."$bogusDate")));

		if ($yearsWithPosts) {
			if ($catID != '') { // at least one category was specified to be excluded
				$catIDs = explode(" ", $catID); // put the category(ies) into an array
				foreach($yearsWithPosts as $currentYear) {
					for ($currentMonth = 12; $currentMonth >= 1; $currentMonth--) {
						if ($monthsWithPosts[$currentYear->year][$currentMonth]) {
							$archives .= '<h2><a href="'.get_month_link($currentYear->year, $currentMonth).'">'.$monthNames[$currentMonth].' '.$currentYear->year.'</a></h2>';
							$archives .= '<ul>';
							foreach ($monthsWithPosts[$currentYear->year][$currentMonth] as $post) {
								if ($post->post_date <= $now) {
									$cats = wp_get_post_categories($post->ID);
									$found = false;
									foreach ($cats as $cat) if (in_array($cat, $catIDs)) $found = true;
									if (!$found)
										$archives .= '<li><a href="'.get_permalink($post->ID).'">'.$post->post_title.'</a></li>';
								}
							}
							$archives .= '</ul>';
						}
					}
				}
			} else { // we don't need to exclude any categories
				foreach($yearsWithPosts as $currentYear) {
					for ($currentMonth = 12; $currentMonth >= 1; $currentMonth--) {
						if ($monthsWithPosts[$currentYear->year][$currentMonth]) {
							$archives .= '<h2><a href="'.get_month_link($currentYear->year, $currentMonth).'">'.$monthNames[$currentMonth].' '.$currentYear->year.'</a></h2>';
							$archives .= '<ul>';
							foreach ($monthsWithPosts[$currentYear->year][$currentMonth] as $post)
								$archives .= '<li><a href="'.get_permalink($post->ID).'">'.$post->post_title.'</a></li>';
							$archives .= '</ul>';
						}
					}
				}
			}
		}
		fwrite($fh, $archives); // write archives to file
	}
	fclose($fh); // close archives file
	}
}

// Init
if ( is_admin() )
	require_once('inc/admin.php');
else
	$smartArchives = new smartArchives();

?>
