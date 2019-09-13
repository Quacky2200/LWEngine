<?php

namespace LWEngine\Models;

//use LWEngine\Engine as Engine;

abstract class ModelProvider {

	static $className = __CLASS__;

	public static function instance() {
		static $instance;

		if ($instance === null) {
			$instance = new static::$className();
		}

		return $instance;
	}

	public function __call($func, $args) {
		$reflectionClass = new \ReflectionClass(get_class($this));
		$name = $reflectionClass->getShortName();
		if (static::$className !== $name) {
			static::$className = $name;
		}

		return call_user_func_array("${name}::${func}", $args);
	}

	public static function getType($type) {
		throw new ModelException('Unimplemented method');
	}

	public static function select() {
		throw new ModelException('Unimplemented method');
	}

	public static function insert() {
		throw new ModelException('Unimplemented method');
	}

	public static function update() {
		throw new ModelException('Unimplemented method');
	}

	public static function delete() {
		throw new ModelException('Unimplemented method');
	}

	public static function create() {
		throw new ModelException('Unimplemented method');
	}

	public static function drop() {
		throw new ModelException('Unimplemented method');
	}

	public static function join() {
		throw new ModelException('Unimplemented method');
	}
}