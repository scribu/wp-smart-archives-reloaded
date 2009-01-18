<?php
if ( !class_exists('scbOptionsPage') )
	require_once('inc/scbOptionsPage.php');

class settingsSAR extends scbOptionsPage {
	public function __construct($file) {
		global $SAR_options;

		$this->options = $SAR_options;

		$this->args = array(
			'page_title' => 'Smart Archives Settings',
			'short_title' => 'Smart Archives',
			'page_slug' => 'smart-archives'
		);

		$this->nonce = 'sar-settings';
		$this->init();

		register_activation_hook($file, array($this, 'activate'));
		register_uninstall_hook($file, array($this, 'uninstall'));

		add_action('publish_post', array($this, 'update_cache'));
		add_action('private_to_published', array($this, 'update_cache'));
		add_action('delete_post', array($this, 'update_cache'));

		add_action('admin_print_scripts', array($this, 'add_js'));
	}

	public function activate() {
		$defaults = array(
			'format' => 'both',
			'catID' => '',
			'anchors' => ''
		);

		$this->options->update($defaults, false);
		$this->update_cache();
	}

	public function uninstall() {
		$this->options->delete();
	}

	public function update_cache() {
		wp_clear_scheduled_hook(SAR_HOOK);
		wp_schedule_single_event(time()+5, SAR_HOOK);
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

// < WP 2.7
if ( !function_exists('register_uninstall_hook') ) :
function register_uninstall_hook() {}
endif;
