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
		$src = $this->plugin_url . '/inc/admin.js';
		wp_enqueue_script('sar-admin', $src, array('jquery'), '1.5');
	}

	function form_handler()
	{
		if ( __('Save Changes', 'smart-archives-reloaded') !== $_POST['action'] )
			return false;

		check_admin_referer($this->nonce);

		$old_options = $this->options->get();

		foreach ( $old_options as $name => $value )
			$new_options[$name] = $_POST[$name];

		// Validate numeric
		if ( $new_options['format'] == 'list' )
			$new_options['block_numeric'] = false;

		// Validate anchors
		if ( $new_options['format'] != 'both' )
			$new_options['anchors'] = false;

		// Validate catIDs
		foreach ( explode(' ', $new_options['catID']) as $id )
			if ( is_numeric($id) )
				$ids[] = intval($id);
		$new_options['catID'] = @implode(' ', array_unique($ids));

		$this->options->update($new_options);

		$this->formdata = $new_options;

		// Rebuild the cache with the new settings
		if ( $new_options != $old_options )
			$this->update_cache();

		$this->admin_msg(__('Settings <strong>saved</strong>.'));
	}

	function page_content()
	{
		$rows = array(
			array(
				'title' => __('Format'),
				'type' => 'radio',
				'name' => 'format',
				'value' => array( 'list', 'block', 'both'),
				'desc' => array(
					__('list', 'smart-archives-reloaded'),
					__('block', 'smart-archives-reloaded'),
					__('both', 'smart-archives-reloaded'),
				)
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

			array(
				'title' => __('Exclude Categories by ID', 'smart-archives-reloaded'),
				'desc' => __('(space separated)', 'smart-archives-reloaded'),
				'type' => 'text',
				'name' => 'catID'
			),

			array(
				'title' => __('Use wp-cron', 'smart-archives-reloaded'),
				'desc' => __("(Uncheck this if your archive isn't being updated)", 'smart-archives-reloaded'),
				'type' => 'checkbox',
				'name' => 'cron'
			)
		);

		echo $this->form_table($rows, NULL, __('Save Changes', 'smart-archives-reloaded'));
	}
}

