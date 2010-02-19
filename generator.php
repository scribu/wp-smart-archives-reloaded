<?php

class SAR_Generator {
	protected $args;
	protected $active_tags;

	protected $where;
	protected $months_with_posts;
	protected $columns;

	public function generate($args) {
		global $wpdb;
	
		$this->args = (object) $args;

		$this->set_where();
		$this->set_limit();
		$this->set_columns();

		if ( ! $this->set_months_with_posts() )
			return false;

		$this->load_post_list();

		return call_user_func(array($this, 'generate_' . $this->args->format));
	}

	protected function load_post_list() {
		global $wpdb;

		if ( !$this->columns ) {
			// fake it for generate_menu()
			$current_year = $this->get_current_year();
			foreach ( $this->get_months_with_posts($current_year) as $month )
				$this->sorted_posts[$current_year][$month] = array(
					'posts' => true,
					'link' => get_month_link($current_year, $month)
				);
		}
	}

	protected function set_where() {
		global $wpdb;

		$where = "
			WHERE post_type = 'post'
			AND post_status = 'publish'		
		";

		if ( ! empty($this->args->exclude_cat) ) {
			$where .= "AND ID NOT IN (";
			$ids = $this->args->exclude_cat;
		} elseif ( ! empty($this->args->include_cat) ) {
			$where .= "AND ID IN (\n";
			$ids = $this->args->include_cat;
		}

		if ( ! empty($ids) ) {
			if ( ! is_array($ids) )
				$ids = explode(',', $ids);

			$ids = scbUtil::array_to_sql(array_map('absint', $ids));

			$where .= "
					SELECT r.object_id
					FROM {$wpdb->term_relationships} r NATURAL JOIN {$wpdb->term_taxonomy} t
					WHERE t.taxonomy = 'category'
					AND t.term_id IN ($ids)
				)
			";
		}

		$this->where = $where;
	}

	protected function set_limit() {
		$limit = '';
		if ( $posts_per_month = absint(@$this->args->posts_per_month) )
			$limit = 'LIMIT ' . $posts_per_month;

		$this->limit = $limit;
	}

	protected function set_months_with_posts() {
		global $wpdb;

		$rows = $wpdb->get_results("
			SELECT DISTINCT YEAR(post_date) AS year, MONTH(post_date) AS month
			FROM {$wpdb->posts}
			{$this->where}
			ORDER BY year ASC, month ASC
		");

		if ( empty($rows) )
			return false;

		$months = array();
		foreach ( $rows as $row )
			$months[$row->year][] = $row->month;

		$this->months_with_posts = $months;

		return true;
	}

	protected function set_columns() {
		if ( 'menu' == $this->args->format ) {
			$this->columns = false;
			return;
		}

		$columns = array('ID', 'post_title');

		if ( 'block' == $this->args->format ) {
			$this->columns = implode(',', $columns);
			return;	
		}

		$column_map = array(
			'post_excerpt' => array('%excerpt%'),
			'post_author' => array('%author%', '%author_link%'),
			'post_date' => array('%date%'),
			'comment_count' => array('%comment_count%'),
		);

		$active_tags = $this->get_active_tags();
		foreach ( $column_map as $column => $tags )
			if ( count(array_intersect($tags, $active_tags)) )
				$columns[] = $column;

		$this->columns = implode(',', $columns);
	}

	function get_active_tags() {
		if ( $this->active_tags )
			return $this->active_tags;

		$this->active_tags = array();
		foreach ( SAR_Core::get_available_tags() as $tag )
			if ( FALSE !== strpos($this->args->list_format, $tag) )
				$this->active_tags[] = $tag;

		return $this->active_tags;
	}

// Data access

	protected function get_current_year() {
		if ( ! $year = get_query_var('year') )
			$year = $this->get_last_item($this->get_years_with_posts());

		return $year;
	}

	protected function get_months_with_posts($year = 0) {
		if ( $year )
			return (array) @$this->months_with_posts[$year];

		return $this->months_with_posts;
	}

	protected function get_years_with_posts() {
		return array_keys($this->months_with_posts);
	}

	protected function get_posts($year, $month) {
		global $wpdb;

		$query = $wpdb->prepare("
			SELECT {$this->columns}
			FROM {$wpdb->posts}
			{$this->where}
			AND YEAR(post_date) = {$year}
			AND MONTH(post_date) = {$month}
			ORDER BY post_date DESC
			{$this->limit}
		");

		return $wpdb->get_results($query);
	}


// ____ MAIN TEMPLATES ____

	// The "menu"
	protected function generate_menu() {
		$year_list = html('ul class="year-list"', 
			$this->generate_year_list(get_query_var('year'))
		, "\n");

		$month_list = html('ul class="month-list"',
			$this->generate_month_list($this->get_current_year(), get_query_var('monthnum'))
		, "\n");

		return html('div id="smart-archives-menu"', $year_list . $month_list, "\n");
	}

	// The "fancy" archive
	protected function generate_fancy() {
		$months_long = $this->get_months();

		$year_list = html("ul class='tabs year-list'",
			$this->generate_year_list()
		, "\n");

		$block = '';

		foreach ( $this->get_years_with_posts() as $year ) {
			// Generate top panes
			$months = html("ul id='month-list-$year' class='tabs month-list'", 
				$this->generate_month_list($year)
			, "\n\t");

			// Generate post lists
			$list = '';
			for ( $i = 1; $i <= 12; $i++ ) {
				if ( ! $posts = $this->get_posts($year, $i) )
					continue;

				// Append to list
				$list .= html('div class="pane"',
					"\n\t\t" . html('h2 class="month-heading"',
						"$months_long[$i] $year "
						.html('span class="month-archive-link"',
							'('. html_link(get_month_link($year, $i), __('View complete archive page', 'smart-archives-reloaded')) .')'
						)
					)
					.html('ul class="archive-list"', 
						$this->generate_post_list($posts, "\n\t\t\t")
					, "\n\t\t")
				, "\n\t");
			} // end month block

			$block .= html('div class="pane"', $months . $list, "\n");
		} // end year block

		// Wrap it up
		return html('div id="smart-archives-fancy"', $year_list . $block);
	}

	// Both
	protected function generate_both() {
		return $this->generate_block() . $this->generate_list();
	}

	// The list
	protected function generate_list() {
		$months_long = $this->get_months();

		$list = '';
		foreach ( array_reverse($this->get_years_with_posts(), true) as $year ) {
			for ( $i = 12; $i >= 1; $i-- ) {
				if ( ! $posts = $this->get_posts($year, $i) )
					continue;

				// Get post links for current month
				$post_list = $this->generate_post_list($posts, "\n\t\t");

				// Set title format
				if ( $this->args->anchors )
					$el = "h2 id='{$year}{$i}'"; 
				else
					$el = "h2";

				// Append to list
				$list .= "\n\t" . html($el,
					html_link(get_month_link($year, $i), $months_long[$i] . ' ' . $year)
				);

				$list .= html('ul', $post_list, "\n\t");
			} // end month block
		} // end year block

		// Wrap it up
		return html('div id="smart-archives-list"', $list, "\n");
	}

	// The block
	protected function generate_block() {
		$block = '';
		foreach ( $this->get_years_with_posts() as $year ) {
			$year_link = html('strong', html_link(get_year_link($year), $year) . ':');

			$month_list = $this->generate_month_list($year, 0, true);

			$block .= "\n\t" . html('li', $year_link . $month_list);
		}

		// Wrap it up
		return html("ul id='smart-archives-block'", $block, "\n");
	}

// ____ HELPER TEMPLATES ____

	protected function generate_year_list($current_year = 0) {
		$year_list = '';
		foreach ( $this->get_years_with_posts() as $year ) {
			$year_list .= "\n\t" . html('li',
				$this->a_link(get_year_link($year), $year, $year == $current_year)
			);
		}

		return $year_list;
	}

	protected function generate_month_list($year, $current_month = 0, $inline = false, $in_current_year = false) {
		$month_names = $this->get_months($this->args->month_format);

		$in_current_year = $year == get_query_var('year');

		$month_list = '';
		for ( $i = 1; $i <= 12; $i++ ) {
			$month = $month_names[$i];

			if ( in_array($i, $this->get_months_with_posts($year)) ) {
				$url = $this->args->anchors ? "#{$year}{$i}" : get_month_link($year, $i);
				$tmp = $this->a_link($url, $month, $in_current_year && $i == $current_month);
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

	protected function generate_post_list($posts, $indent) {
		$active_tags = $this->get_active_tags();

		$post_list = '';
		foreach ( $posts as $post ) {
			$list_item = $this->args->list_format;

			foreach ( $active_tags as $tag )
				$list_item = str_replace($tag, call_user_func(array($this, 'substitute_' . substr($tag, 1, -1)), $post), $list_item);

			$post_list .= $indent . html('li', $list_item);
		}

		return $post_list;
	}


	protected function get_months($format = 'long') {
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

	protected function a_link($link, $title, $current) {
		$el = $current ? 'a class="current"' : 'a';

		return html($el . ' href="' . $link . '"', $title);
	}

	protected function get_last_item($array) {
		$keys = array_keys($array);
		return $array[$keys[count($keys)-1]];
	}

// ____ SUBSTITUTION TAGS ____

	protected function substitute_post_link($post) {
		return html_link(
			get_permalink($post->ID),
			apply_filters('smart_archives_title', $post->post_title, $post->ID)
		);
	}

	protected function substitute_author_link($post) {
		return html_link(
			get_author_posts_url($post->post_author),
			get_user_option('display_name', $post->post_author)
		);
	}

	protected function substitute_author($post) {
		return get_user_option('display_name', $post->post_author);
	}

	protected function substitute_comment_count($post) {
		return $post->comment_count;
	}

	protected function substitute_excerpt($post) {
		return $post->post_excerpt;
	}

	protected function substitute_date($post) {
		return mysql2date($this->args->date_format, $post->post_date);
	}

	protected function substitute_category_link($post) {
		$categorylist = array();
		foreach ( get_the_category($post->ID) as $category )
			$categorylist[] = html_link(get_category_link($category->cat_ID), $category->cat_name);

		return implode(', ', $categorylist);
	}

	protected function substitute_category($post) {
		$categorylist = array();
		foreach ( get_the_category($post->ID) as $category )
			$categorylist[] = $category->cat_name;

		return implode(', ', $categorylist);
	}
}

