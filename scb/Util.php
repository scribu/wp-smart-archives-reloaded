<?php

class scbUtil {

	static function array_to_sql($values) {
		foreach ( $values as &$val )
			$val = "'" . esc_sql(trim($val)) . "'";

		return implode(',', $values);
	}

	static function array_extract($array, $keys) {
		$r = array();
		foreach ( $keys as $key )
			if ( isset($array[$key]) )
				$r[$key] = $array[$key];

	   return $r;
	}
	
	static function do_scripts($handles) {
		global $wp_scripts;

		if ( ! is_a($wp_scripts, 'WP_Scripts') )
			$wp_scripts = new WP_Scripts();

		$wp_scripts->do_items((array) $handles);
	}

	static function do_styles($handles) {
		global $wp_styles;

		if ( ! is_a($wp_styles, 'WP_Styles') )
			$wp_styles = new WP_Styles();

		$wp_styles->do_items((array) $handles);
	}

	static function debug() {
		echo "<pre>";
		foreach ( func_get_args() as $arg )
			if ( is_array($arg) || is_object($arg) )
				print_r($arg);
			else
				var_dump($arg);
		echo "</pre>";
	}
}
