<?php

class SAR_Settings extends scbAdminPage {

	function setup() {
		$this->textdomain = 'smart-archives-reloaded';

		$this->args = array(
			'page_title' => __('Smart Archives Settings', $this->textdomain),
			'menu_title' => __('Smart Archives', $this->textdomain),
			'page_slug' => 'smart-archives'
		);
	}

	// Page methods
	function page_head() {
		$js_dev = defined('STYLE_DEBUG') && SCRIPT_DEBUG ? '.dev' : '';
	
		wp_enqueue_script('sar-admin', $this->plugin_url . "admin/admin$js_dev.js", array('jquery'), '1.9', true);
		echo $this->css_wrap('h3 {margin-bottom: 0 !important}');
	}

	function validate($args) {
		$args = wp_parse_args($args, $this->options->get_defaults());

		// Category IDs
		if ( isset($args['include_cat']) && !empty($args['include_cat']) ) {
			$args['include_cat'] = self::parse_id_list($args['include_cat']);
			$args['exclude_cat'] = array();
		}
		else {
			$args['exclude_cat'] = self::parse_id_list($args['exclude_cat']);
		}

		// Anchors
		if ( 'both' != $args['format'] )
			$args['anchors'] = false;

		// Block numeric
		if ( array_key_exists('block_numeric', $args) ) {
			if ( 'block' == $args['format'] && ! array_key_exists('month_format', $args) )
				$args['month_format'] = $args['block_numeric'] ? 'numeric' : 'short';

			unset($args['block_numeric']);
		}

		// List format
		$args['list_format'] = trim($args['list_format']);

		return $args;
	}

	private function parse_id_list($list) {
		$ids = array();

		if ( !is_array($list) )
			$list = preg_split('/[\s,]+/', $list);

		foreach ( $list as $id )
			if ( $id = absint($id) )
				$ids[] = $id;

		return array_unique($ids);
	}

	function page_content() {
		$tags = '';
		foreach ( SAR_Core::get_available_tags() as $tag )
			$tags .= "\n\t" . html('li', html('em', $tag));

		$default_date = __('Default', $this->textdomain) . ': F j, Y (' . date_i18n("F j, Y") . ')';

		$output = $this->_subsection(__('General settings', $this->textdomain), 'general', array(
			array(
				'title' => __('Exclude Categories by ID', $this->textdomain),
				'desc' => __('(comma separated)', $this->textdomain),
				'type' => 'text',
				'name' => 'exclude_cat',
				'value' => implode(', ', (array) $this->options->exclude_cat)
			),

			array(
				'title' => __('Format', $this->textdomain),
				'type' => 'radio',
				'name' => 'format',
				'value' => array('block', 'list', 'both', 'fancy'),
				'desc' => array(
					__('block', $this->textdomain),
					__('list', $this->textdomain),
					__('both', $this->textdomain),
					__('fancy', $this->textdomain),
				)
			),
		))

		.$this->_subsection(__('Specific settings', $this->textdomain), 'specific', array(
			array(
				'title' => __('List format', $this->textdomain),
				'desc' => html('p', __('Available substitution tags', $this->textdomain))
					.html('ul', $tags),
				'type' => 'text',
				'name' => 'list_format',
			),

			array(
				'title' => sprintf(__('%s format', $this->textdomain), '%date%'),
				'desc' => html('p', $default_date)
					.html('p', 
						html('em', __('See available date formatting characters <a href="http://php.net/date" target="_blank">here</a>.'
						, $this->textdomain))
					),
				'type' => 'text',
				'name' => 'date_format',
			),

			array(
				'title' => __('Month names', $this->textdomain),
//				'after' => html('p', __('How should the month links be displayed', $this->textdomain)),
				'type' => 'radio',
				'name' => 'month_format',
				'value' => array('numeric', 'short', 'long'),
				'desc' => array(
					__('numeric', $this->textdomain),
					__('short', $this->textdomain),
					__('long', $this->textdomain),
				)
			),

			array(
				'title' => __('Use anchor links in block', $this->textdomain),
				'desc' => __('The month links in the block will point to the month links in the list', $this->textdomain),
				'type' => 'checkbox',
				'name' => 'anchors',
			),
		));

		echo $this->form_wrap($output);
	}

	function _subsection($title, $id, $rows) {
		return html("div id='$id'", 
			"\n" . html('h3', $title)
			."\n" . $this->table($rows)
		, "\n");
	}
}

