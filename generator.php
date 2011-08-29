<?php

class SAR_Generator {
	protected $args;
	protected $active_tags;

	protected $months_with_posts;
	protected $columns;

	public function generate( $args, $qv ) {
		if ( !$this->load_data( $qv ) )
			return '';

		$this->args = (object) $args;
		$this->query_vars = $qv;

		return call_user_func( array( $this, 'generate_' . $this->args->format ) );
	}

	protected function load_data( $qv ) {
		$qv = array_merge( $qv, array(
			'ignore_sticky_posts' => true,
			'nopaging' => true,
			'cache_results' => false,
			'suppress_filters' => false,
			'months_with_posts' => true
		) );

		unset( $qv['year'], $qv['monthnum'] );

		$rows = get_posts( $qv );

		if ( empty( $rows ) )
			return false;

		$months = array();
		foreach ( $rows as $row )
			$months[$row->year][] = $row->month;

		$this->months_with_posts = $months;

		return true;
	}

	function query_manipulation( $bits, $wp_query ) {
		if ( $wp_query->get( 'months_with_posts' ) ) {
			$bits['fields'] = 'DISTINCT YEAR( post_date ) AS year, MONTH( post_date ) AS month';
			$bits['orderby'] = 'year DESC, month ASC';
		}

		return $bits;
	}

	protected function get_current_year() {
		if ( !$year = get_query_var( 'year' ) )
			$year = reset( $this->get_years_with_posts( 'desc' ) );

		return $year;
	}

	protected function get_months_with_posts( $year = 0 ) {
		if ( $year )
			return (array) @$this->months_with_posts[$year];

		return $this->months_with_posts;
	}

	protected function get_years_with_posts( $order ) {
		$years = array_keys( $this->months_with_posts );

		if ( 'desc' != $order )
			$years = array_reverse( $years );

		return $years;
	}

	protected function get_posts( $year, $month ) {
		if ( !in_array( $month, $this->get_months_with_posts( $year ) ) )
			return array();

		$qv = array_merge( $this->query_vars, array(
			'year' => $year,
			'monthnum' => $month,
			'ignore_sticky_posts' => true,
			'cache_results' => false,
			'no_found_rows' => true,
		) );

		if ( $this->args->posts_per_month )
			$qv['posts_per_page'] = $this->args->posts_per_month;
		else
			$qv['nopaging'] = true;

		return get_posts( $qv );
	}


//_____MAIN TEMPLATES_____


	// The "menu"
	protected function generate_menu() {
		$data = array(
			'year-list' => $this->generate_year_list( get_query_var( 'year' ) ),
			'month-list' => $this->generate_month_list( $this->get_current_year(), get_query_var( 'monthnum' ) )
		);

		return self::mustache_render( 'menu.html', $data );
	}

	// The "fancy" archive
	protected function generate_fancy() {
		$months_long = $this->get_months();

		$data = array(
			'year-list' => $this->generate_year_list(),
		);

		foreach ( $this->get_years_with_posts( 'asc' ) as $year ) {
			$pane = array(
				'year' => $year,
				'month-list' => $this->generate_month_list( $year )
			);

			foreach ( range( 1, 12 ) as $i ) {
				$post_list = $this->generate_post_list( $year, $i, "\n\t\t" );

				if ( !$post_list )
					continue;

				$pane['post-lists'][] = array(
					'heading' => "$months_long[$i] $year",
					'archive-link' => get_month_link( $year, $i ),
					'archive-text' => __( 'View complete archive page', 'smart-archives-reloaded' ),
					'post-list' => $post_list,
				);
			}

			$data['year-panes'][] = $pane;
		}

		return self::mustache_render( 'fancy.html', $data );
	}

	// Both
	protected function generate_both() {
		return $this->generate_block() . $this->generate_list();
	}

	// The list
	protected function generate_list() {
		$months_long = $this->get_months();

		$data = array();

		foreach ( $this->get_years_with_posts( 'desc' ) as $year ) {
			foreach ( range( 12, 1 ) as $i ) {
				// Get post links for current month
				$post_list = $this->generate_post_list( $year, $i, "\n\t\t" );

				if ( !$post_list )
					continue;

				$data['post-lists'][] = array(
					'id' => "{$year}{$i}",
					'archive-link' => get_month_link( $year, $i ),
					'archive-text' => $months_long[$i] . ' ' . $year,
					'post-list' => $post_list,
				);
			}
		}

		return self::mustache_render( 'list.html', $data );
	}

	// The block
	protected function generate_block() {
		$data = array();

		foreach ( $this->get_years_with_posts( 'desc' ) as $year ) {
			$data['month-lists'][] = array(
				'year' => $year,
				'year-link' => get_year_link( $year ),
				'month-list' => $this->generate_month_list( $year, get_query_var( 'monthnum' ), true )
			);
		}

		return self::mustache_render( 'block.html', $data );
	}


//_____HELPER TEMPLATES_____


	protected function generate_year_list( $current_year = 0 ) {
		$data = array();

		foreach ( $this->get_years_with_posts( 'asc' ) as $year ) {
			$data['years'][] = array(
				'year-link' => get_year_link( $year ),
				'year' => $year,
				'is-current' => ( $year == $current_year ) ? array(true) : false
			);
		}

		return self::mustache_render( 'year-list.html', $data );
	}

	protected function generate_month_list( $year, $current_month = 0, $inline = false ) {
		$month_names = $this->get_months( $this->args->month_format );

		$in_current_year = ( get_query_var( 'year' ) == $year );

		$data = array(
			'inline' => $inline ? array(true) : false
		);

		foreach ( range( 1, 12 ) as $i ) {
			$data['months'][] = array(
				'month' => $month_names[$i],
				'month-link' => $this->args->anchors ? "#{$year}{$i}" : get_month_link( $year, $i ),
				'is-current' => ( $in_current_year && $i == $current_month ) ? array(true) : false,
				'is-empty' => ( !in_array( $i, $this->get_months_with_posts( $year ) ) ) ? array(true) : false
			);
		}

		return self::mustache_render( 'month-list.html', $data );
	}

	protected function generate_post_list( $year, $i ) {
		$posts = $this->get_posts( $year, $i );

		if ( empty( $posts ) )
			return false;

		$active_tags = array();
		foreach ( SAR_Core::get_available_tags() as $tag ) {
			if ( false !== strpos( $this->args->list_format, $tag ) ) {
				$active_tags[] = $tag;
			}
		}

		$data = array();

		foreach ( $posts as $post ) {
			$list_item = $this->args->list_format;

			foreach ( $active_tags as $tag ) {
				$list_item = str_replace( $tag, call_user_func( array( $this, 'substitute_' . substr( $tag, 1, -1 ) ), $post ), $list_item );
			}

			$data['posts'][] = array( 'item' => $list_item );
		}

		return self::mustache_render( 'post-list.html', $data );
	}

	protected function get_months( $format = 'long' ) {
		global $wp_locale;

		$months = array();
		foreach ( range( 1, 12 ) as $i ) {
			if ( 'numeric' == $format ) {
				$months[$i] = zeroise( $i, 2 );
				continue;
			}

			$month = $wp_locale->get_month( $i );

			if ( 'short' == $format )
				$month = $wp_locale->get_month_abbrev( $month );

			$months[$i] = esc_html( $month );
		}

		return $months;
	}


//_____SUBSTITUTION TAGS_____


	protected function substitute_post_link( $post ) {
		unset( $post->filter );

		return html_link(
			get_permalink( $post ),
			apply_filters( 'smart_archives_title', $post->post_title, $post->ID )
		);
	}

	protected function substitute_author_link( $post ) {
		return html_link(
			get_author_posts_url( $post->post_author ),
			get_user_option( 'display_name', $post->post_author )
		);
	}

	protected function substitute_author( $post ) {
		return get_user_option( 'display_name', $post->post_author );
	}

	protected function substitute_comment_count( $post ) {
		return $post->comment_count;
	}

	protected function substitute_excerpt( $post ) {
		return apply_filters( 'get_the_excerpt', $post->post_excerpt );
	}

	protected function substitute_date( $post ) {
		return mysql2date( $this->args->date_format, $post->post_date );
	}

	protected function substitute_category_link( $post ) {
		$categorylist = array();
		foreach ( get_the_category( $post->ID ) as $category )
			$categorylist[] = html_link( get_category_link( $category->cat_ID ), $category->cat_name );

		return implode( ', ', $categorylist );
	}

	protected function substitute_category( $post ) {
		$categorylist = array();
		foreach ( get_the_category( $post->ID ) as $category )
			$categorylist[] = $category->cat_name;

		return implode( ', ', $categorylist );
	}

	static function mustache_render( $file, $data ) {
		$template_path = locate_template( 'sar-templates/' . $file );
		if ( !$template_path )
			$template_path = dirname(__FILE__) . '/templates/' . $file;

		return SAR_Core::mustache_render( $template_path, $data );
	}
}

add_filter( 'posts_clauses', array( 'SAR_Generator', 'query_manipulation' ), 10, 2 );

