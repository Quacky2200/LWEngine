<?php

namespace LWEngine\Models;

class MySQLProvider {
	static $className = __CLASS__;

	public static function getType($field) {
		switch (strtolower($field['type'])) {
			case 'integer':
			case 'int':
			case 'number':
				if (isset($field['length'])) {
					if ($field['length'] > 0 && $field['length'] < 65) {
						return 'BIT';
					}/* else if ($field['length'] > )*/
				}
				return 'integer';
			case 'string':
			case 'text':

			case 'character':
			case 'char':
				return 'char';
			case 'blob':
			case 'bool':
			case 'boolean':
			case 'timestamp':
				return strtoupper($field['type']);
		}
	}
}