<?php

class SAR_Generator {
	private static $yearsWithPosts;
	private static $monthsWithPosts;

	public static function generate($args) {
		require dirname(__FILE__) . '/inc/html.php';

		global $wpdb;

		extract($args, EXTR_SKIP);

		$where = "
			WHERE post_type = 'post'
			AND post_status = 'publish'		
		";

		if ( ! empty($exclude_cat) ) {
			$where .= "AND ID NOT IN (";
			$ids = $exclude_cat;
		} elseif ( ! empty($include_cat) ) {
			$where .= "AND ID IN (\n";
			$ids = $include_cat;
		}

		if ( ! empty($ids) ) {
			if ( ! is_array($ids) )
				$ids = explode(',', $ids);

			$ids = scbUtil::array_to_sql($ids);

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
					{$where}
					AND year(post_date) = {$current}
					AND month(post_date) = {$i}
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
			case 'block': return self::generate_block();
			case 'list': return self::generate_list();
			case 'both': return self::generate_block() . self::generate_list();
			case 'fancy': return self::generate_fancy();
		}
	}

	// The "fancy" archive
	private static function generate_fancy() {
		$months_long = self::get_months();

		$years = '';
		foreach ( self::$yearsWithPosts as $current )
			$years .= "\n\t" . html("li class='list-$current'", 
				html_link(get_year_link($current), $current)
			);
		$years = html("ul class='tabs years-list'", $years, "\n");

		$block = '';
		foreach ( self::$yearsWithPosts as $year ) {
			// Generate top panes
			$months = html("ul id='month-list-$year' class='tabs month-list'", 
				self::generate_month_list($year)
			, "\n\t");

			// Generate post lists
			$list = '';
			for ( $i = 1; $i <= 12; $i++ ) {
				if ( ! $current = self::$monthsWithPosts[$year][$i] )
					continue;

				// Append to list
				$list .= html('did class="pane"',
					"\n\t\t" . html('h2 class="month-heading"',
						$months_long[$i] . ' ' . $year . ' '
						.html('span class="month-archive-link"',
							'('. html_link($current['link'], __('View complete archive page', 'smart-archives-reloaded')) .')'
						)
					)
					.html('ul class="archive-list"', 
						self::generate_post_list($current['posts'], "\n\t\t\t")
					, "\n\t\t")
				, "\n\t");
			} // end month block

			$block .= html("div class='pane'", 
				$months . $list
			, "\n");
		} // end year block

		// Wrap it up
		return html('div id="smart-archives-fancy"', $years . $block);
	}

	private static function generate_month_list($year) {
		$months_short = self::get_months(true);

		$month_list = '';
		for ( $i = 1; $i <= 12; $i++ ) {
			if ( SAR_Core::$options->block_numeric )
				$month = sprintf('%02d', $i);
			else
				$month = $months_short[$i];

			$current = self::$monthsWithPosts[$year][$i];

			$month_list .= "\n\t\t";
			if ( $current['posts'] )
				$month_list .= html('li', html_link($current['link'], $month));
			else
				$month_list .= html('li', html("span class='emptymonth'", $month));
		}

		return $month_list;
	}

	// The list
	private static function generate_list() {
		$months_long = self::get_months();

		foreach ( self::$yearsWithPosts as $year ) {
			for ( $i = 12; $i >= 1; $i-- ) {
				if ( ! $current = self::$monthsWithPosts[$year][$i] )
					continue;

				// Get post links for current month
				$post_list = self::generate_post_list($current['posts'], "\n\t");

				// Set title format
				if ( SAR_Core::$options->anchors )
					$titlef = "h2 id='{$year}{$i}'"; 
				else
					$titlef = "h2";

				// Append to list
				$list .= "\n" . html($titlef, 
					html_link($current['link'], $months_long[$i] . ' ' . $current)
				);

				$list .= html('ul', $post_list, "\n");
			} // end month block
		} // end year block

		// Wrap it up
		$list = "\n<div id='smart-archives-list'>\n{$list}</div>\n";

		return $list;
	}

	private static function generate_post_list($posts, $indent) {
		$active_tags = self::get_active_tags();

		$post_list = '';
		foreach ( $posts as $post ) {
			$list_item = SAR_Core::$options->list_format;

			foreach ( $active_tags as $tag )
				$list_item = str_replace($tag, call_user_func(array(__CLASS__, 'substitute_' . substr($tag, 1, -1)), $post), $list_item);

			$post_list .= $indent . html('li', $list_item);
		}

		return $post_list;
	}

	// The block
	private static function generate_block() {
		$months_short = self::get_months(true);

		foreach ( self::$yearsWithPosts as $current ) {
			$block .= sprintf("\t<li><strong><a href='%s'>%s</a>:</strong> ", get_year_link($current), $current);

			for ( $i = 1; $i <= 12; $i++ ) {
				if ( SAR_Core::$options->block_numeric )
					$month = sprintf('%02d', $i);
				else
					$month = $months_short[$i];

				if ( self::$monthsWithPosts[$current][$i]['posts'] ) {
					if ( SAR_Core::$options->anchors )
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

	private static $active_tags;

	static function get_active_tags() {
		if ( is_array(self::$active_tags) )
			return self::$active_tags;

		self::$active_tags = array();
		foreach ( self::get_available_tags() as $tag )
			if ( FALSE !== strpos(SAR_Core::$options->list_format, $tag) )
				self::$active_tags[] = $tag;

		return self::$active_tags;
	}

	private static function get_columns() {
		$columns = array('ID', 'post_title');

		if ( 'block' == SAR_Core::$options->format )
			return implode(',', $columns);

		if ( count(array_intersect(array('%author%', '%author_link%'), self::get_active_tags())) )
			$columns[] = 'post_author';

		if ( count(array_intersect(array('%comment_count%'), self::get_active_tags())) )
			$columns[] = 'comment_count';

		if ( count(array_intersect(array('%date%'), self::get_active_tags())) )
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
		return sprintf("<span class='post_date'>%s</span>", mysql2date(SAR_Core::$options->date_format, $post->post_date));
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

