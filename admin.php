<?php

class settingsSAR extends scbAdminPage 
{
	function __construct($file, $options)
	{
		$this->textdomain = 'smart-archives-reloaded';

		// Load translations
		$plugin_dir = basename(dirname($file));
		load_plugin_textdomain($this->textdomain, "wp-content/plugins/$plugin_dir/lang", "$plugin_dir/lang");

		$this->args = array(
			'page_title' => __('Smart Archives Settings', $this->textdomain),
			'menu_title' => __('Smart Archives', $this->textdomain),
			'page_slug' => 'smart-archives'
		);

		add_action('transition_post_status', array($this, 'update_cache'), 10, 2);
		add_action('deleted_post', array($this, 'update_cache'), 10, 0);

		parent::__construct($file, $options);
	}

	function update_cache($new_status = '', $old_status = '')
	{
		$cond =
			( 'publish' == $new_status || 'publish' == $old_status ) ||		// publish or unpublish
			( empty($new_status) && empty($old_status) );					// delete post or update options

		if ( !$cond )
			return;

		if ( $this->options->cron )
		{
			wp_clear_scheduled_hook(displaySAR::hook);
			wp_schedule_single_event(time(), displaySAR::hook);
		}
		else
			do_action(displaySAR::hook);
	}

	// Page methods
	function page_head()
	{
		wp_enqueue_script('sar-admin', $this->plugin_url . 'inc/admin.js', array('jquery'), '1.6', true);
		echo $this->css_wrap('h3 {margin-bottom: 0 !important}');
	}

	function validate($new_options, $old_options)
	{
		// Validate numeric
		if ( $new_options['format'] == 'list' )
			$new_options['block_numeric'] = false;

		// Validate anchors
		if ( $new_options['format'] != 'both' )
			$new_options['anchors'] = false;

		// Validate catIDs
		foreach ( @explode(' ', $new_options['catID']) as $id )
			if ( is_numeric($id) )
				$ids[] = intval($id);
		$new_options['catID'] = @implode(' ', array_unique($ids));

		// List format
		$new_options['list_format'] = trim($new_options['list_format']);
		if ( empty($new_options['list_format']) )
			$new_options['list_format'] = $this->options->defaults['list_format'];

		// Rebuild the cache with the new settings
		if ( $new_options != $old_options )
		{
			$this->options->update($new_options);
			$this->update_cache();
		}

		return $new_options;
	}

	function _subsection($title, $id, $rows)
	{
		return "<div id='$id'>\n" . "<h3>$title</h3>\n" . $this->table($rows) . "</div>\n";
	}

	function page_content()
	{
		foreach ( displaySAR::get_available_tags() as $tag )
			$tags .= "<li><em>$tag</em></li>\n";

		$output = $this->_subsection(__('General settings', $this->textdomain), 'general', array(
			array(
				'title' => __('Exclude Categories by ID', $this->textdomain),
				'desc' => __('(space separated)', $this->textdomain),
				'type' => 'text',
				'name' => 'catID'
			),

			array(
				'title' => __('Use wp-cron', $this->textdomain),
				'desc' => __("Uncheck this if your archive isn't being updated", $this->textdomain),
				'type' => 'checkbox',
				'name' => 'cron'
			),

			array(
				'title' => __('Format', $this->textdomain),
				'type' => 'radio',
				'name' => 'format',
				'value' => array( 'list', 'block', 'both'),
				'desc' => array(
					__('list', $this->textdomain),
					__('block', $this->textdomain),
					__('both', $this->textdomain),
				)
			),
		))

		. $this->_subsection(__('Specific settings', $this->textdomain), 'specific', array(
			array(
				'title' => __('List format', $this->textdomain),
				'desc' => '<p>' . __('Available substitution tags', $this->textdomain) . ':</p>
					<ul>
						' . $tags . '
					</ul>',
				'type' => 'text',
				'name' => 'list_format',
			),

			array(
				'title' => __('Numeric months in block', $this->textdomain),
				'desc' => __('The month links in the block will be shown as numbers', $this->textdomain),
				'type' => 'checkbox',
				'name' => 'block_numeric',
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
}

