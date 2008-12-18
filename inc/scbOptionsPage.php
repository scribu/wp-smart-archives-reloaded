<?php

// Version 1.0

abstract class scbOptionsPage {
	var $args = array();
	var $options = array();
	var $actions = array();
	var $nonce = 'update_settings';

	protected function init() {
		add_action('admin_menu', array(&$this, 'page_init'));
	}

	public function page_init() {
		if ( !current_user_can('manage_options') )
			return false;

		extract($this->args);
		add_options_page($short_title, $short_title, 8, $page_slug, array(&$this, 'page_content'));
	}

	abstract public function page_content();

	protected function form_handler() {
		if ( 'Save Changes' != $_POST['action'] )
			return false;

		check_admin_referer($this->nonce);

		foreach ( $this->options->get() as $name => $value )
			$new_options[$name] = $_POST[$name];

		$this->options->update($new_options);

		echo '<div class="updated fade"><p>Settings <strong>saved</strong>.</p></div>';
	}

	protected function page_header() {
		$this->form_handler();

		$output .= "<div class='wrap'>\n";
		$output .= "<h2>".$this->args['page_title']."</h2>\n";

		return $output;
	}

	protected function page_footer() {
		$output .= "</div>\n";

		return $output;
	}

	protected function submit_button($action = 'Save Changes') {
		if ( in_array($action, $this->actions) )
			trigger_error('Duplicate action for submit button: '.$action, E_USER_WARNING);

		$this->actions[] = $action;
		$output .= "<p class='submit'>\n";
		$output .= "<input name='action' type='submit' class='button-primary' value='{$action}' />\n";
		$output .= "</p>\n";

		return $output;
	}

	protected function form_wrap($content) {
		$output .= "<form method='post' action=''>\n";
		$output .= wp_nonce_field($action = $this->nonce, $name = "_wpnonce", $referer = true , $echo = false );
		$output .= $content;
		$output .= "</form>\n";

		return $output;
	}

	protected function form_table($rows, $action = 'Save Changes') {
		$output .= "<table class='form-table'>\n";

		$options = $this->options->get();
		foreach ( $rows as $row )
			$output .= $this->form_row($row, $options);

		$output .= "</table>\n";
		$output .= $this->submit_button($action);

		return $this->form_wrap($output);
	}

	public function form_row($args, $options, $check=true) {
		extract($args);

		$f1 = is_array($names);
		$f2 = is_array($values);

		if ( $check )
			self::check_names($names, $options);

		if ( $type == 'text' && !$f1 && !$f2 )
			$values = htmlentities(stripslashes($options[$names]));

		if ( $f1 || $f2 ) {
			if ( $f1 && $f2 )
				$a = array_combine($names, $values);
			elseif ( $f1 && !$f2 )
				$a = array_fill_keys($names, $values);
			elseif ( !$f1 && $f2)
				$a = array_fill_keys($values, $names);

			if ( $f1 ) {
				$i1 = 'name';
				$i2 = 'val';
			}

			if ( $f2 ) {	
				$i1 = 'val';
				$i2 = 'name';
			}
	
			$l1 = 'name';

		} else {
			$a = array($names => $values);

			$i1 = 'name';
			$i2 = 'val';

			$l1 = 'desc';
		}

		foreach ( $a as $name => $val ) {
			if ( in_array($type, array('checkbox', 'radio')) )
				$extra = ($options[$$i1] == $$i2) ? "checked='checked' " : '';

			$inputs[] = sprintf('<input name="%1$s" value="%2$s" type="%3$s" %4$s/> ', $$i1, $$i2, $type, $extra );
			$inputs[] = sprintf("<label for='%1\$s'>%2\$s</label> ", $$i1, $$l1);
		}

		return "\n<tr>\n\t<th scope='row'>$title</th>\n\t<td>\n\t\t". implode($inputs, "\n") ."</td>\n\n</tr>";
	}

	public function check_names($names, $options) {
		if ( !is_array($names) )
			$names = array($names);

		foreach ( array_diff($names, array_keys($options)) as $key )
			trigger_error('Option not defined: '.$key, E_USER_WARNING);
	}
}

// < PHP 5.2
if ( !function_exists('array_fill_keys') ) :
function array_fill_keys($keys, $value) {
	$r = array();

	foreach($keys as $key)
		$r[$key] = $value;

	return $r;
}
endif;
