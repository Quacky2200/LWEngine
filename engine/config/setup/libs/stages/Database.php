<?php

namespace LWEngine\Config\Setup\Stages;

use LWEngine\Config\Setup\Stage as Stage;
use LWEngine\Engine as Engine;
use \Element as Element;

class Database extends Stage {

	private $config;

	public function __construct() {
		parent::__construct("engine-database-chapter", "Let's Start", "These database details are required to allow the engine to store information (e.g. pages, settings, users). If you do not know these details, your hosting provider will be able to provide you with them.");
	}

	public function onLoad() {
		$this->config = Engine::getConfig();
		$this->config->setFilePath(TEMP_CONFIG_FILE);
		$this->config->open();

		// If it has been done already & we can connect, ignore.
		try {
			if (Engine::getDatabase($this->config)) {
				$this->setEnabled(false);
			}
		} catch (\PDOException $e) {
			// Error with DB
		}
	}

	public function getElements() {
		return array(
			new Element("p", array(
				"class" => "input error " . $this->addName("error")),
				"Cannot connect to the database, make sure the details are correct and that the service is running."
			),
			new Element("h3", null, "Database Host"),
			new Element("input", array(
				"name" => $this->addName("host"),
				"placeholder" => "localhost",
				"value" => $this->config->getProp('db.host', '')
			)),
			new Element("h3", null, "Database Username"),
			new Element("input", array(
				"name" => $this->addName("username"),
				"placeholder" => "root",
				"value" => $this->config->getProp('db.username', '')
			)),
			new Element("h3", null, "Database Password"),
			new Element("input", array(
				"name" => $this->addName("password"),
				"type" => "password",
				"placeholder" => "(your database password)",
				"value" => $this->config->getProp('db.password', '')
			)),
			new Element("h3", null, "Database Name"),
			new Element("input", array(
				"name" => $this->addName("dbname"),
				"placeholder" => "(e.g. Engine)",
				"value" => $this->config->getProp('db.database', '')
			)),
			new Element("h3", null, "Driver"),
			new Element("select", array(
				"name" => $this->addName("driver"),
				"value" => $this->config->getProp('db.driver', 'mysql')
			), $this->buildOptions(array(
				'mysql' => 'MySQL',
				'pgsql' => 'PostgreSQL',
				'sqlite' => 'Sqlite',
				'dblib' => 'MS SQL',
				/* TODO: Add more drivers here... */
			))),
			new Element("h3", null, "Persistence"),
			new Element("select", array(
				"name" => $this->addName("persistence"),
				"value" => $this->config->getProp('db.driver', 'mysql')
			), $this->buildOptions(array(
				true => 'On',
				false => 'Off'
			))),
			// TODO: Enhance Sqlite support
			new Element("select", array(
				'style' => 'display:none;',
				'class' => 'sqlite kind',
				"name" => $this->addName("sqlite-kind"),
				"value" => $this->config->getProp('db.sqlite.type', 'mysql')
			), $this->buildOptions(array(
				'memory' => 'Memory',
				'file' => 'Filepath'
			))),
			new Element("input", array(
				'style' => 'display: none;',
				'class' => 'sqlite path',
				"name" => $this->addName("sqlite-filepath"),
				"value" => $this->config->getProp('db.sqlite.path', 'mysql')
			)),
		);
	}

	private static function buildOptions($data) {
		$result = null;
		foreach($data as $key => $val) {
			$result[] = new Element('option', array('value' => $key), $val);
		}
		return $result;
	}

	private function try_connect($host, $username, $password, $name, $driver = 'mysql') {
		try {
			if (isset($host, $username, $password, $name)) {
				return new \PDO("${driver}:host=${host};", $username, $password);
			}
			return false;
		} catch (\PDOException $e){
			return false;
		}
	}

	public function onSubmit() {
		$host = $_POST[$this->addName("host")];
		$username = $_POST[$this->addName("username")];
		$password = $_POST[$this->addName("password")];
		$name = $_POST[$this->addName("dbname")];
		$driver = $_POST[$this->addName('driver')];
		if (($db = $this->try_connect($host, $username, $password, $name)) && $db) {
			$db->exec("CREATE DATABASE IF NOT EXISTS " . $name);
			$this->config->setFilePath(TEMP_CONFIG_FILE);
			$this->config->setProp('db.host', $host);
			$this->config->setProp('db.username', $username);
			$this->config->setProp('db.password', $password);
			$this->config->setProp('db.database', $name);
			$this->config->setProp('db.driver', $driver);
			$this->config->save(true);
		} else {
			$this->sendStatus(true, array($this->addName("error")));
		}

	}
}
?>