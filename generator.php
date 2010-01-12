<?php

class SAR_Generator {
	protected static $args;
	protected static $active_tags;

	protected static $yearsWithPosts;
	protected static $monthsWithPosts;

	protected static $current_year;

	public static function generate($args) {
		self::$args = (object) $args;

		extract($args, EXTR_SKIP);

		global $wpdb;

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

		$order = ( in_array($format, array('menu', 'fancy')) ) ? 'ASC' : 'DESC';

		$limit = '';
		if ( $posts_per_month = absint(@$posts_per_month) )
			$limit = 'LIMIT ' . $posts_per_month;

		// Get non-empty years
		self::$yearsWithPosts = $wpdb->get_col("
			SELECT YEAR(post_date) AS year
			FROM {$wpdb->posts}
			{$where}
			GROUP BY YEAR(post_date)
			HAVING COUNT(YEAR(post_date)) > 0
			ORDER BY YEAR(post_date) $order
		");

		if ( ! self::$yearsWithPosts )
			return false;

		self::set_current_year();

		if ( $columns = self::get_columns() ) {
			// Get post list for each month
			foreach ( self::$yearsWithPosts as $year ) {
				for ( $i = 1; $i <= 12; $i++ ) {
					$query = $wpdb->prepare("
						SELECT {$columns}
						FROM {$wpdb->posts}
						{$where}
						AND YEAR(post_date) = {$year}
						AND MONTH(post_date) = {$i}
						ORDER BY post_date DESC
						{$limit}
					");

					if ( $posts = $wpdb->get_results($query) ) {
						$month = array(
							'posts' => $posts,
							'link' => get_month_link($year, $i)
						);

						self::$monthsWithPosts[$year][$i] = $month;
					}
				}
			}
		}
		else {
			// Get months with posts
			$months = $wpdb->get_col($wpdb->prepare("
				SELECT MONTH(post_date)
				FROM {$wpdb->posts}
				{$where}
				AND YEAR(post_date) = %d
				GROUP BY MONTH(post_date)
				ORDER BY MONTH(post_date) ASC
			", self::$current_year));

			foreach ( $months as $i )
				self::$monthsWithPosts[self::$current_year][$i] = array(
					'posts' => true,
					'link' => get_month_link(self::$current_year, $i)
				);
		}

		return call_user_func(array(__CLASS__, 'generate_' . $format));
	}
	
	protected static function set_current_year() {
		if ( ! $year = get_query_var('year') )
			$year = self::get_last_item(self::$yearsWithPosts);

		self::$current_year = $year;
	}

	protected static function get_columns() {
		if ( 'menu' == self::$args->format )
			return false;

		$columns = array('ID', 'post_title');

		if ( 'block' == self::$args->format )
			return implode(',', $columns);

		$column_map = array(
			'post_author' => array('%author%', '%author_link%'),
			'post_date' => array('%date%'),
			'comment_count' => array('%comment_count%'),
		);

		$active_tags = self::get_active_tags();
		foreach ( $column_map as $column => $tags )
			if ( count(array_intersect($tags, $active_tags)) )
				$columns[] = $column;

		return implode(',', $columns);
	}

	static function get_active_tags() {
		if ( self::$active_tags )
			return self::$active_tags;

		self::$active_tags = array();
		foreach ( SAR_Core::get_available_tags() as $tag )
			if ( FALSE !== strpos(self::$args->list_format, $tag) )
				self::$active_tags[] = $tag;

		return self::$active_tags;
	}

// ____ MAIN TEMPLATES ____

	// The "menu"
	protected static function generate_menu() {
		$year_list = html('ul class="year-list"', 
			self::generate_year_list(get_query_var('year'))
		, "\n");

		$month_list = html('ul class="month-list"',
			self::generate_month_list(get_query_var('year'), get_query_var('monthnum'))
		, "\n");

		return html('div id="smart-archives-menu"', $year_list . $month_list, "\n");
	}

	// The "fancy" archive
	protected static function generate_fancy() {
		$months_long = self::get_months();

		$year_list = html("ul class='tabs year-list'",
			self::generate_year_list()
		, "\n");

		$block = '';
		foreach ( self::$yearsWithPosts as $year ) {
			// Generate top panes
			$months = html("ul id='month-list-$year' class='tabs month-list'", 
				self::generate_month_list($year)
			, "\n\t");

			// Generate post lists
			$list = '';
			for ( $i = 1; $i <= 12; $i++ ) {
				if ( ! $current = @self::$monthsWithPosts[$year][$i] )
					continue;

				// Append to list
				$list .= html('div class="pane"',
					"\n\t\t" . html('h2 class="month-heading"',
						"$months_long[$i] $year "
						.html('span class="month-archive-link"',
							'('. html_link($current['link'], __('View complete archive page', 'smart-archives-reloaded')) .')'
						)
					)
					.html('ul class="archive-list"', 
						self::generate_post_list($current['posts'], "\n\t\t\t")
					, "\n\t\t")
				, "\n\t");
			} // end month block

			$block .= html('div class="pane"', $months . $list, "\n");
		} // end year block

		// Wrap it up
		return html('div id="smart-archives-fancy"', $year_list . $block);
	}

	// Both
	protected static function generate_both() {
		return self::generate_block() . self::generate_list();
	}

	// The list
	protected static function generate_list() {
		$months_long = self::get_months();

		$list = '';
		foreach ( self::$yearsWithPosts as $year ) {
			for ( $i = 12; $i >= 1; $i-- ) {
				if ( ! $current = @self::$monthsWithPosts[$year][$i] )
					continue;

				// Get post links for current month
				$post_list = self::generate_post_list($current['posts'], "\n\t\t");

				// Set title format
				if ( self::$args->anchors )
					$el = "h2 id='{$year}{$i}'"; 
				else
					$el = "h2";

				// Append to list
				$list .= "\n\t" . html($el,
					html_link($current['link'], $months_long[$i] . ' ' . $year)
				);

				$list .= html('ul', $post_list, "\n\t");
			} // end month block
		} // end year block

		// Wrap it up
		return html('div id="smart-archives-list"', $list, "\n");
	}

	// The block
	protected static function generate_block() {
		$block = '';
		foreach ( self::$yearsWithPosts as $year ) {
			$year_link = html('strong', html_link(get_year_link($year), $year) . ':');

			$month_list = self::generate_month_list($year, 0, true);

			$block .= "\n\t" . html('li', $year_link . $month_list);
		}

		// Wrap it up
		return html("ul id='smart-archives-block'", $block, "\n");
	}

// ____ HELPER TEMPLATES ____

	protected static function generate_year_list($current_year = 0) {
		$year_list = '';
		foreach ( self::$yearsWithPosts as $year ) {
			$year_list .= "\n\t" . html('li',
				self::a_link(get_year_link($year), $year, $year == $current_year)
			);
		}

		return $year_list;
	}

	protected static function generate_month_list($year, $current_month = 0, $inline = false) {
		$month_names = self::get_months(self::$args->month_format);

		$month_list = '';
		for ( $i = 1; $i <= 12; $i++ ) {
			$month = $month_names[$i];

			$current = @self::$monthsWithPosts[$year][$i];

			if ( $current['posts'] ) {
				$url = self::$args->anchors ? "#{$year}{$i}" : $current['link'];
				$tmp = self::a_link($url, $month, $i == $current_month);
			}
			else {
				$tmp = html('span class="empty-month"', $month);
			}

			if ( $inline )
				$month_list .= " $tmp";
			else
				$month_list .= "\n\t\t" . html('li', $tmp);
		}

		return $month_list;
	}

	protected static function generate_post_list($posts, $indent) {
		$active_tags = self::get_active_tags();

		$post_list = '';
		foreach ( $posts as $post ) {
			$list_item = self::$args->list_format;

			foreach ( $active_tags as $tag )
				$list_item = str_replace($tag, call_user_func(array(__CLASS__, 'substitute_' . substr($tag, 1, -1)), $post), $list_item);

			$post_list .= $indent . html('li', $list_item);
		}

		return $post_list;
	}


	protected static function get_months($format = 'long') {
		global $wp_locale;

		$months = array();
		for ( $i = 1; $i <= 12; $i++ ) {
			if ( 'numeric' == $format ) {
				$months[$i] = zeroise($i, 2);
				continue;
			}

			$month = $wp_locale->get_month($i);

			if ( 'short' == $format )
				$month = $wp_locale->get_month_abbrev($month);

			$months[$i] = esc_html($month);
		}

		return $months;
	}

	protected static function a_link($link, $title, $current) {
		$el = $current ? 'a class="current"' : 'a';

		return html($el . ' href="' . $link . '"', $title);
	}

	protected static function get_last_item($array) {
		$keys = array_keys($array);
		return $array[$keys[count($keys)-1]];
	}

// ____ SUBSTITUTION TAGS ____

	protected static function substitute_post_link($post) {
		return html_link(
			get_permalink($post->ID),
			apply_filters('smart_archives_title', $post->post_title, $post->ID)
		);
	}

	protected static function substitute_author_link($post) {
		return html_link(
			get_author_posts_url($post->post_author),
			get_user_option('display_name', $post->post_author)
		);
	}

	protected static function substitute_author($post) {
		return get_user_option('display_name', $post->post_author);
	}

	protected static function substitute_comment_count($post) {
		return $post->comment_count;
	}

	protected static function substitute_date($post) {
		return html('span class="post_date"', mysql2date(self::$args->date_format, $post->post_date));
	}

	protected static function substitute_category_link($post) {
		$categorylist = array();
		foreach ( get_the_category($post->ID) as $category )
			$categorylist[] = html_link(get_category_link($category->cat_ID), $category->cat_name);

		return implode(', ', $categorylist);
	}

	protected static function substitute_category($post) {
		$categorylist = array();
		foreach ( get_the_category($post->ID) as $category )
			$categorylist[] = $category->cat_name;

		return implode(', ', $categorylist);
	}
}

