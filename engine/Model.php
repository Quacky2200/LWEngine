<?php

namespace LWEngine;

use LWEngine\Engine as Engine;

class Model /* extends ReflectiveObject */ {
	protected $db;
	protected static $schema;
	protected $provider;

	/**
	 * Construct the Model.
	 *
	 * Construct the model using the schema provided and database connection.
	 *
	 * The schema is looked at automatically. The provider by default is a MySQL
	 * provider.
	 *
	 * @param PDO    $db    Database instance
	 * @return void
	 */
	public function construct($db, $provider = null) {
		$this->db = $db;

		// TODO: implement below schema features, and remove this example...
		static::$schema = array(
			'table' => 'example_users',
			'joins' => null,
			'fields' => array(
				array(
					'name' => 'id',
					'type' => 'integer',
					'primary_key' => 1,
				),
				array(
					'name' => 'username',
					'type' => 'string',
					'max-length' => '64',
					'min-length' => '2',
					'default' => function() {
						return base64_encode(time());
					},
				),
				array(
					'name' => 'password',
					'type' => 'string',
					'max-length' => 1024,
					'min-length' => 128,
					'hidden' => true,
					'selectable' => false,
					'pre-process' => function($str) {

					},
				),
				array(
					'name' => 'ctime',
					'alias' => 'creationTime',
					'type' => 'integer',
					'post-process' => function($time) {
						return strftime('%d %m %Y', strtotime($time));
					},
				),
				array(
					'name' => 'first_name',
					'alias' => 'firstName',
					'type' => 'string',
				),
				array(
					'name' => 'last_name',
					'alias' => 'lastName',
					'type' => 'string',
				),
				array(
					'name' => 'full_name',
					'alias' => 'fullName',
					'type' => 'string',
					'virtual' => true,

				)
			),
		);

		if ($provider !== null) {
			$this->provider = $provider;
		}

		$this->checkSchema();
	}

	/**
	 * Get Property.
	 *
	 * Return read-only access to private/protected class variables. This will
	 * prevent the objects being overwritten, but the inner object contents
	 * (variables) can still be changed.
	 *
	 * @return mixed
	 */
	public function __get($name) {
		if (isset($this[$name])) {
			return $this[$name];
		} else {
			return null;
		}
	}

	/**
	 * Set Property.
	 *
	 * Allows specific model properties to be set or a 'Property is read-only'
	 * exception will be thrown.
	 *
	 * @param  string            $name     Name of property
	 * @param  mixed             $value    Value of property
	 * @throws ModelException              If issues occur setting a property
	 */
	public function __set($name, $value) {
		if ($name === 'schema') {
			if (is_array($value)) {
				static::$schema = $value;
				$this->checkSchema();
			}
		} else if ($name === 'provider') {
			if ($value instanceof ModelProvider) {
				$this[$name] = $value;
			} else {
				throw new ModelException('Provider must be a derived ModelProvider');
			}
		} else {
			throw new ModelException('Property is read-only.');
		}
	}

	protected function checkSchema() {

		$schema = static::$schema;

		if (!(isset($schema['table']) && !empty($schema['table']))) {
			throw new ModelException('No table name is present in schema');
		}

		if (!(isset($schema['fields']) && !empty($schema['fields']))) {
			throw new ModelException('No fields present in schema');
		}

		foreach ($schema['fields'] as &$field) {
			if ($field['name'] == 'id') {
				$field = array_merge(array(
					'type' => 'integer',
					'increment' => true,
					'primary' => true
				), $field);
			} else if (!isset($field['type'])) {
				$field['type'] = 'string';
			}

			$field['providerType'] = $this->provider->getType($field);
		}
	}

	public function create() {

	}

	public function drop() {}

	public function select($opts) {

	}

	public function update($opts) {

	}

	public function insert($opts) {

	}

	public function delete($opts) {

	}
}

class ModelException extends Exception {}
class ModelSchemaException extends Exception {}
class ModelSelectException extends Exception {}
class ModelInsertException extends Exception {}
class ModelUpdateException extends Exception {}
class ModelDeleteException extends Exception {}
// class ModelFieldException? extends Exception {}
?>