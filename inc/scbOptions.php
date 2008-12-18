<?php

// Version 1.0

class scbOptions {
	var $key;
	var $data;

	public function __construct($key, $data = '') {
		$this->key = $key;

		if ( $data )
			$this->data = $data;
		else
			$this->data = get_option($this->key);
	}

	public function get($field = '') {
		if ( empty($field) === true )
			return $this->data;

		return @$this->data[$field];
	}

	public function update($data, $override = true) {
		if ( is_array($this->data) && is_array($data) && !$override )
			$newdata = array_merge($data, $this->data);
		else
			$newdata = $data;

		if ( $this->data !== $newdata ) {
			$this->data = $newdata;

			   add_option($this->key, $this->data) or
			update_option($this->key, $this->data);
		}
	}

	public function delete() {
		delete_option($this->key);
	}
}
