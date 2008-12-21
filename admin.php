<?php
if ( !class_exists('scbOptionsPage') )
	require_once('inc/scbOptionsPage.php');

class settingsSAR extends scbOptionsPage {
	var $display;

	public function __construct(scbOptions $options, displaySAR $display) {
		$this->options = $options;
		$this->display = $display;

		$this->args = array(
			'page_title' => 'Smart Archives Settings',
			'short_title' => 'Smart Archives',
			'page_slug' => 'smart-archives'
		);

		$this->nonce = 'sar-settings';
		$this->init();

		add_action('admin_print_scripts', array($this, 'add_js'));
	}

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

		$diff = array_diff_assoc($new_options, $old_options);

		if ( isset($diff['interval'])) {
			wp_clear_scheduled_hook('smart_archives_update');
			wp_schedule_event(time(), $new_options['interval'], 'smart_archives_update');
		}

		if ( count($diff) > 1 || (count($diff) > 0 && !isset($diff['interval'])) )
			$this->display->generate(); // rebuild the cache with the new settings

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
			),

			array(
				'title' => 'Cache Update',
				'type' => 'radio',
				'names' => 'interval',
				'values' => array('hourly', 'twicedaily', 'daily')
			)
		);
		echo str_replace('twicedaily</label>', 'twice daily</label>', $this->form_table($rows));
		echo $this->page_footer();
	}
}

class adminSAR {
	var $options;

	public function __construct($file) {
		global $SAR_options, $SAR_display;

		$this->options = $SAR_options;

		new settingsSAR($SAR_options, $SAR_display);

		register_activation_hook($file, array($this, 'activate'));
		register_deactivation_hook($file, array($this, 'deactivate'));
		register_uninstall_hook($file, array($this, 'uninstall'));
	}

	public function activate() {
		$defaults = array(
			'format' => 'both',
			'catID' => '',
			'interval' => 'daily',
			'anchors' => ''
		);

		$this->options->update($defaults, false);

		wp_schedule_event(time(), $this->options->get('interval'), 'smart_archives_update');
	}

	public function deactivate() {
		wp_clear_scheduled_hook('smart_archives_update');
	}

	public function uninstall() {
		$this->options->delete();
	}
}

// < WP 2.7
if ( !function_exists('register_uninstall_hook') ) :
function register_uninstall_hook() {}
endif;
