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
