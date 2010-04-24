<?php

class SAR_Generator {
	protected $args;
	protected $active_tags;

	protected $months_with_posts;
	protected $columns;

	public function generate($args, $qv) {
		if ( !$this->load_data($args, $qv) )
			return '';

		$this->args = (object) $args;
		$this->query_vars = $qv;

		return call_user_func(array($this, 'generate_' . $this->args->format));
	}

	protected function load_data($args, $qv) {
		$mvp = new SAR_Year_Query($qv);

		if ( empty($mvp->months_with_posts) )
			return false;

		$this->months_with_posts = $mvp->months_with_posts;

		return true;
	}

	protected function get_current_year() {
		if ( ! $year = get_query_var('year') )
			$year = end($this->get_years_with_posts());

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
		if ( !in_array($month, $this->get_months_with_posts($year)) )
			return array();

		$qv = array_merge($this->query_vars, array(
			'year' => $year,
			'monthnum' => $month,
			'caller_get_posts' => true,
			'nopaging' => true,
		));

		$query = new SAR_Posts_Query($this->args->posts_per_month, $qv);

		return $query->posts;
	}


//_____MAIN TEMPLATES_____


	// The "menu"
	protected function generate_menu() {
		$year_list = 
		html('ul class="year-list"', 
			$this->generate_year_list(get_query_var('year'))
		);

		$month_list = 
		html('ul class="month-list"',
			$this->generate_month_list($this->get_current_year(), get_query_var('monthnum'))
		);

		return html('div id="smart-archives-menu"', $year_list . $month_list, "\n");
	}

	// The "fancy" archive
	protected function generate_fancy() {
		$months_long = $this->get_months();

		$year_list = 
		html("ul class='tabs year-list'",
			$this->generate_year_list()
		);

		$block = '';

		foreach ( $this->get_years_with_posts() as $year ) {
			// Generate top panes
			$months =
			html("ul id='month-list-$year' class='tabs month-list'",
				$this->generate_month_list($year)
			);

			// Generate post lists
			$list = '';
			for ( $i = 1; $i <= 12; $i++ ) {
				if ( !$posts = $this->get_posts($year, $i) )
					continue;

				// Append to list
				$list .= 
				html('div class="pane"',
					 html('h2 class="month-heading"',
						"$months_long[$i] $year "
						.html('span class="month-archive-link"',
							'('. html_link(get_month_link($year, $i), __('View complete archive page', 'smart-archives-reloaded')) .')'
						)
					)
					.html('ul class="archive-list"', 
						$this->generate_post_list($posts, "\n\t\t\t")
					)
				);
			} // end month block

			$block .= html('div class="pane"', $months . $list);
		} // end year block

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
				$list .= 
				html($el,
					html_link(get_month_link($year, $i), $months_long[$i] . ' ' . $year)
				);

				$list .= html('ul', $post_list);
			} // end month block
		} // end year block

		return html('div id="smart-archives-list"', $list);
	}

	// The block
	protected function generate_block() {
		$block = '';
		foreach ( $this->get_years_with_posts() as $year ) {
			$year_link = html('strong', html_link(get_year_link($year), $year) . ':');

			$month_list = $this->generate_month_list($year, 0, true);

			$block .= html('li', $year_link . $month_list);
		}

		return html("ul id='smart-archives-block'", $block);
	}


//_____HELPER TEMPLATES_____


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
		$post_list = '';
		foreach ( $posts as $post ) {
			$list_item = $this->args->list_format;

			foreach ( SAR_Core::get_available_tags() as $tag )
				if ( false !== strpos($this->args->list_format, $tag) )
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


//_____SUBSTITUTION TAGS_____


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


class SAR_Posts_Query extends scbQuery {
	private $count;

	function __construct($count, $qv) {
		$this->count = (int) $count;

		parent::__construct($qv);
	}

	function post_limits() {
		if ( $this->count )
			return "LIMIT $this->count";

		return '';
	}

	final public function __get($key) {
		return $this->wp_query->$key;
	}
}

class SAR_Year_Query extends scbQuery {

	function __construct($qv) {
		$qv = array_merge($qv, array(
			'caller_get_posts' => true,
			'nopaging' => true,
			'cache_results' => false,
		));

		parent::__construct($qv);

		$months = array();
		foreach ( $this->wp_query->posts as $row )
			$months[$row->year][] = $row->month;

		$this->months_with_posts = $months;
	}

	function posts_fields() {
		return 'DISTINCT YEAR(post_date) AS year, MONTH(post_date) AS month';
	}

	function posts_orderby() {
		return 'year ASC, month ASC';
	}
}

