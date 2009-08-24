<?php

class settingsSAR extends scbAdminPage 
{
	function __construct($file, $options)
	{
		// Load translations
		$plugin_dir = basename(dirname($file));
		load_plugin_textdomain('smart-archives-reloaded', "wp-content/plugins/$plugin_dir/lang", "$plugin_dir/lang");

		$this->args = array(
			'page_title' => __('Smart Archives Settings', 'smart-archives-reloaded'),
			'menu_title' => __('Smart Archives', 'smart-archives-reloaded'),
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
			wp_clear_scheduled_hook('smart_archives_update');
			wp_schedule_single_event(time(), 'smart_archives_update');
		}
		else
			do_action('smart_archives_update');
	}

	// Page methods
	function page_head()
	{
		wp_enqueue_script('sar-admin', $this->plugin_url . 'inc/admin.js', array('jquery'), '1.6', true);
		echo $this->css_wrap('h3 {margin-bottom: 0 !important}');
	}

	function validate($new_options)
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
		if ( $new_options != $this->options->get() )
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
		$output = $this->_subsection(__('General settings', 'smart-archives-reloaded'), 'general', array(
			array(
				'title' => __('Exclude Categories by ID', 'smart-archives-reloaded'),
				'desc' => __('(space separated)', 'smart-archives-reloaded'),
				'type' => 'text',
				'name' => 'catID'
			),

			array(
				'title' => __('Use wp-cron', 'smart-archives-reloaded'),
				'desc' => __("Uncheck this if your archive isn't being updated", 'smart-archives-reloaded'),
				'type' => 'checkbox',
				'name' => 'cron'
			),

			array(
				'title' => __('Format', 'smart-archives-reloaded'),
				'type' => 'radio',
				'name' => 'format',
				'value' => array( 'list', 'block', 'both'),
				'desc' => array(
					__('list', 'smart-archives-reloaded'),
					__('block', 'smart-archives-reloaded'),
					__('both', 'smart-archives-reloaded'),
				)
			),
		))

		. $this->_subsection(__('Specific settings', 'smart-archives-reloaded'), 'specific', array(
			array(
				'title' => __('List format', 'smart-archives-reloaded'),
				'desc' => '<p>' . __('Available substitution tags', 'smart-archives-reloaded') . ':</p>
					<ul>
						<li><em>%post_link%</em></li>
						<li><em>%author_link%</em></li>
						<li><em>%author%</em></li>
					</ul>',
				'type' => 'text',
				'name' => 'list_format',
			),

			array(
				'title' => __('Numeric months in block', 'smart-archives-reloaded'),
				'desc' => __('The month links in the block will be shown as numbers', 'smart-archives-reloaded'),
				'type' => 'checkbox',
				'name' => 'block_numeric',
			),

			array(
				'title' => __('Use anchor links in block', 'smart-archives-reloaded'),
				'desc' => __('The month links in the block will point to the month links in the list', 'smart-archives-reloaded'),
				'type' => 'checkbox',
				'name' => 'anchors',
			),
		));

		echo $this->form_wrap($output, __('Save Changes', 'smart-archives-reloaded'));
	}
}

