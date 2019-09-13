<?php

namespace LWEngine;

class Config {
	/*
	 *	Config Data
	 */
	private $props;

	/*
	 *	Config Functionality
	 */
	private static $filepath;

	public function __construct($filepath, $automaticallyOpen = true) {
		$this->props = array();
		self::$filepath = $filepath;
		if ($automaticallyOpen) {
			if ($this->exists()) {
				$this->open();
			} else {
				throw new \Exception("Configuration file was set to automatically open, but it doesn't exist.");
			}
		}
	}

	public function exists() {
		return file_exists(self::$filepath);
	}

	public function getFilePath() {
		return self::$filepath;
	}

	public function setFilePath($value) {
		self::$filepath = $value;
	}

	public function getProp($name, $default = null) {
		$props = $this->props;

		if (stripos($name, '.') !== false) {
			$path = explode('.', $name);
			$name = array_pop($path);

			foreach($path as $key) {
				if (array_key_exists($key, $props)) {
					$props = $props[$key];
					continue;
				}

				return $default;
			}
		}

		if (array_key_exists($name, $props)) {
			return $props[$name];
		} else {
			return $default;
		}
	}

	public function setProp($name, $value) {
		$props = &$this->props;

		if (stripos($name, '.') !== false) {
			$path = explode('.', $name);
			$name = array_pop($path);

			foreach($path as $key) {
				if (!array_key_exists($key, $props)) {
					$props[$key] = array();
				}

				$props = &$props[$key];
			}
		}

		$props[$name] = $value;
	}

	public function open() {
		if (file_exists(self::$filepath)) {
			$contents = @json_decode(file_get_contents(self::$filepath), true);
			if (!is_null($contents)) {
				$this->props = $contents;
			} else {
				throw new \Exception('The configuration file opened is not a valid JSON file');
			}
			return true;
		} else {
			return false; //throw new \Exception('The configuration file doesn\'t exist');
		}
	}

	public function save($overwrite = false) {
		if ($overwrite || (!file_exists(self::$filepath) && !$overwrite)) {
			return file_put_contents(self::$filepath, json_encode($this->props, JSON_PRETTY_PRINT), LOCK_EX);
		} else {
			return false;
		}
	}
}
?>
