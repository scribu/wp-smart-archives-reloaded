<?php

// Version 0.6.0.3

abstract class scbForms_06 {
	/* Generates one or more input fields, with labels
	$args =	array (
		'type' => any valid <input> type
		'names' => string | array
		'values' => string | array (default: 1 or $options['name'])
		'check' => true | false (default: true)
		'extra' => string (default: class="widefat")
		'desc' => string (default: name)
		'desc_pos' => 'before' | 'after' | 'none' (default: after)
	);
	$options = array('name' => 'value'...)
	*/

	public function input($args, $options = array()) {
		$token = '%input%';

		extract(wp_parse_args($args, array(
			'desc_pos' => 'after',
			'check' => true,
			'extra' => 'class="widefat"'
		)));

		// Check required fields
		if ( empty($type) )
			trigger_error('No type specified', E_USER_WARNING);

		if ( empty($names) )
			trigger_error('No name specified', E_USER_WARNING);

		// Check for defined options
		if ( $check && 'submit' != $type && !empty($options) )
			self::check_names($names, $options);

		$f1 = is_array($names);
		$f2 = is_array($values);

		// Set default values
		if ( !isset($values) )
			if ( 'text' == $type && !$f1 && !$f2 )
				$values = wp_specialchars($options[$names], ENT_QUOTES);
			elseif ( in_array($type, array('checkbox', 'radio')) && empty($values) )
				$values = true;

		// Determine what goes where
		if ( $f1 || $f2 ) {
			if ( $f1 && $f2 )
				$a = array_combine($names, $values);
			elseif ( $f1 && !$f2 )
				$a = array_fill_keys($names, $values);
			elseif ( !$f1 && $f2 )
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

		// Generate output
		foreach ( $a as $name => $val ) {
			// Build extra string
			$extra_string = $extra;

			if ( in_array($type, array('checkbox', 'radio')) && $options[$$i1] == $$i2)
				$extra_string .= " checked='checked'";

			// Build the item
			$input = sprintf('<input %4$s name="%1$s" value="%2$s" type="%3$s" /> ', $$i1, $$i2, $type, $extra_string);

			// Add description
			$desc = $$l1;
			$desc = str_replace('[]', '', $desc);
			if ( FALSE == stripos($desc, $token) )
				if ( 'before' == $desc_pos )
					$desc .= ' ' . $token;
				elseif ( 'after' == $desc_pos )
					$desc = $token . ' ' . $desc;
			$desc = str_replace($token, $input, $desc);
			$desc = trim($desc);

			// Add label
			if ( 'none' == $desc_pos || empty($desc) )
				$output[] = $input . "\n";
			else
				$output[] = sprintf("<label for='%s'>%s</label>\n", $$i1, $desc);
		}
		return implode("\n", $output);
	}

	// Adds a form around the $content, including a hidden nonce field
	public function form_wrap($content, $nonce = '') {
		if ( empty($nonce) )
			$nonce = $this->nonce;

		$output .= "\n<form method='post' action=''>\n";
		$output .= $content;
		$output .= wp_nonce_field($action = $nonce, $name = "_wpnonce", $referer = true , $echo = false);
		$output .= "\n</form>\n";

		return $output;
	}


//_____HELPER METHODS (SHOULD NOT BE CALLED DIRECTLY)_____


	// Checks if selected $names have equivalent in $options. Used by form_row()
	protected function check_names($names, $options) {
		$names = (array) $names;

		foreach ( $names as $i => $name )
			$names[$i] = str_replace('[]', '', $name);

		foreach ( array_diff($names, array_keys($options)) as $key )
			trigger_error("Option not defined: {$key}", E_USER_WARNING);
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
