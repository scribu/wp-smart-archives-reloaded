<?php

// Version 0.5b
// TODO: Add select, textarea

abstract class scbForms_05 {
/* Generates one or more input fields with labels
$args =	array (
*	'type' => 'submit' | 'text' | 'radio' | 'checkbox'
*	'names' => string | array
	'values' => string | array
	'check' => true | false
	'extra' => string
	'desc' => string
	'desc_pos' => 'before' | 'after' | 'none'
);
$options = array() [values with which to fill]
*/

	public function input($args, $options = array()) {
		$token = '%input%';

		extract(wp_parse_args($args, array(
			'desc_pos' => 'after',
			'check' => true
		)));

		// Check required fields
		if ( empty($type) )
			trigger_error('No type specified', E_USER_WARNING);

		if ( empty($names) )
			trigger_error('No name specified', E_USER_WARNING);

		// Check for defined options
		if ( $check && 'submit' != $type )
			self::check_names($names, $options);

		$f1 = is_array($names);
		$f2 = is_array($values);

		// Set default values
		if ( 'text' == $type && !$f1 && !$f2 )
			$values = htmlentities(stripslashes($options[$names]));
		elseif ( in_array($type, array('checkbox', 'radio')) && empty($values) )
			$values = true;

		// Determine what goes where
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
			if ( FALSE == stripos($desc, $token) )
				if ( 'before' == $desc_pos )
					$desc .= ' ' . $token;
				elseif ( 'after' == $desc_pos )
					$desc = $token . ' ' . $desc;
			$desc = str_replace($token, $input, $desc);

			// Add label
			if ( 'none' == $desc_pos || empty($desc) )
				$output .= $input;
			else
				$output .= sprintf("<label for='%s'>%s</label> ", $$i1, $desc);
		}

		return $output;
	}

	public function form_wrap($content, $nonce = '') {
		if ( empty($nonce) )
			$nonce = $this->nonce;

		$output .= "<form method='post' action=''>\n";
		$output .= wp_nonce_field($action = $nonce, $name = "_wpnonce", $referer = true , $echo = false );
		$output .= $content;
		$output .= "</form>\n";

		return $output;
	}


//_____HELPER METHODS (SHOULD NOT BE CALLED DIRECTLY)_____


	// Used by form_row()
	protected function check_names($names, $options) {
		$names = (array) $names;

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
