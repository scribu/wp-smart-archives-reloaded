<?php

if ( !class_exists('scbOptionsPage_07') )
	require_once('inc/scbOptionsPage.php');

class settingsSAR extends scbOptionsPage_07 {
	protected function setup() {
		$this->options = $GLOBALS['SAR_options'];

		$this->defaults = array(
			'format' => 'both',
			'catID' => '',
			'anchors' => ''
		);

		$this->args = array(
			'page_title' => 'Smart Archives Settings',
			'short_title' => 'Smart Archives',
			'page_slug' => 'smart-archives'
		);

		$this->nonce = 'sar-settings';

		add_action('transition_post_status', array($this, 'update_cache'), 10, 2);
		add_action('deleted_post', array($this, 'update_cache'), 10, 0);

		add_action('admin_print_scripts', array($this, 'add_js'));
	}

	public function update_cache($new_status = '', $old_status = '') {
		$cond =
			( 'publish' == $new_status || 'publish' == $old_status ) ||		// publish or unpublish
			( empty($new_status) && empty($old_status) );					// delete

		if ( !$cond )
			return;

		wp_clear_scheduled_hook('smart_archives_update');
		wp_schedule_single_event(time() + 5, 'smart_archives_update');
#		do_action('smart_archives_update');
	}

	// Page methods
	public function add_js() {
		if ( $_GET['page'] != $this->args['page_slug'] )
			return;

		$src = $this->get_plugin_url() . '/inc/admin.js';
		wp_enqueue_script('sar-admin', $src, array('jquery'));
	}

	private function get_plugin_url() {
		if ( function_exists('plugins_url') )

			return plugins_url(plugin_basename(dirname(__FILE__)));
		else
			// < WP 2.6
			return get_option('siteurl') . '/wp-content/plugins/' . plugin_basename(dirname(__FILE__));
	}

	protected function form_handler() {
		if ( 'Save Changes' !== $_POST['action'] )
			return false;

		check_admin_referer($this->nonce);

		$old_options = $this->options->get();

		foreach ( $old_options as $name => $value )
			$new_options[$name] = $_POST[$name];

		// Validate anchors
		if ( $new_options['format'] != 'both' )
			$new_options['anchors'] = false;

		// Validate catIDs
		foreach ( explode(' ', $new_options['catID']) as $id )
			if ( is_numeric($id) )
				$ids[] = intval($id);
		$new_options['catID'] = @implode(' ', array_unique($ids));

		$this->options->update($new_options);

		// Rebuild the cache with the new settings
		if ( $new_options != $old_options )
			$this->update_cache();

		echo '<div class="updated fade"><p>Settings <strong>saved</strong>.</p></div>';
	}

	public function page_content() {
		echo $this->page_header();
		$rows = array(
			array(
				'title' => 'Format',
				'type' => 'radio',
				'names' => 'format',
				'values' => array('block', 'list', 'both')
			),

			array(
				'title' => 'Use anchor links in block',
				'desc' => 'The month links in the block will point to the month links in the list',
				'type' => 'checkbox',
				'names' => 'anchors',
				'values' => true
			),

			array(
				'title' => 'Exclude Categories by ID',
				'desc' => '(space separated)',
				'type' => 'text',
				'names' => 'catID'
			)
		);
		echo $this->form_table($rows);
		echo $this->page_footer();
	}
}

