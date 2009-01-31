<?php

// Version 0.5b

if ( ! class_exists('scbForms_05') )
	require_once('scbForms.php');

abstract class scbOptionsPage_05 extends scbForms_05 {
	// Page args
	protected $args = array(
		'page_title' => '',
		'short_title' => '',
		'page_slug' => ''
	);

	// scbOptions object holder
	protected $options = NULL;

	// Form actions
	protected $actions = array();

	// Nonce string
	protected $nonce = 'update_settings';


//_____MAIN METHODS_____


	// Main constructor
	public function __construct($file = '') {
		$this->setup();

		if ( isset($this->options) )
			$this->options->setup($file, $this->defaults);

		add_action('admin_menu', array($this, 'page_init'));
	}

	// This is where all the page args goes
	abstract protected function setup();

	// This is where the page content goes
	abstract public function page_content();

	// Generates a standard page head
	protected function page_header() {
		$this->form_handler();

		$output .= "<div class='wrap'>\n";
		$output .= "<h2>".$this->args['page_title']."</h2>\n";

		return $output;
	}

	// Generates a standard page footer
	protected function page_footer() {
		$output = "</div>\n";

		return $output;
	}

	// Wrap a field in a table row
	public function form_row($args, $options, $check = true) {
		$args['check'] = $check;
		return "\n<tr>\n\t<th scope='row'>{$args['title']}</th>\n\t<td>\n\t\t". parent::input($args, $options, $check) ."</td>\n\n</tr>";
	}

	// Generates multiple rows and wraps them in a form table
	protected function form_table($rows, $action = 'Save Changes') {
		$output .= "<table class='form-table'>\n";

		$options = $this->options->get();
		foreach ( $rows as $row )
			$output .= $this->form_row($row, $options);

		$output .= "</table>\n";
		$output .= $this->submit_button($action);

		return parent::form_wrap($output, $this->nonce);
	}

	// Generates a submit form button
	protected function submit_button($action = 'Save Changes') {
		if ( in_array($action, $this->actions) )
			trigger_error("Duplicate action for submit button: {$action}", E_USER_WARNING);

		$this->actions[] = $action;
		$output .= "<p class='submit'>\n";
		$output .= parent::input(array(
			'type' => 'submit',
			'names' => 'action',
			'values' => $action,
			'extra' => 'class="button-primary"',
			'desc_pos' => 'none'
		));
		$output .= "</p>\n";

		return $output;
	}


//_____HELPER METHODS (SHOULD NOT BE CALLED DIRECTLY)_____


	// Registers a page
	public function page_init() {
		if ( !current_user_can('manage_options') )
			return false;

		extract($this->args);
		add_options_page($short_title, $short_title, 8, $page_slug, array($this, 'page_content'));
	}

	// Update options
	protected function form_handler() {
		if ( 'Save Changes' != $_POST['action'] )
			return false;

		check_admin_referer($this->nonce);

		foreach ( $this->options->get() as $name => $value )
			$new_options[$name] = $_POST[$name];

		$this->options->update($new_options);

		echo '<div class="updated fade"><p>Settings <strong>saved</strong>.</p></div>';
	}
}
