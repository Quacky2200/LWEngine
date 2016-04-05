<?php
class DatabaseSetup extends SetupChapter{
	public function __construct(){
		parent::__construct("engine-database-chapter", "Let's Start", "These database details are required to allow the engine to store information (e.g. pages, settings, users). If you do not know these details, your hosting provider will be able to provide you with them.");
	}
	private $config;
	public function onLoad(){
		$this->config = Engine::getConfig();
		$this->config->setConfigFilename(TEMP_CONFIG_FILE);
		$this->config->open();
		//If it has been done already & we can connect, ignore.
		if($this->try_connect($this->config->DB_HOST, $this->config->DB_USERNAME, $this->config->DB_PASSWORD, $this->config->DB_NAME) === true){
			$this->setEnabled(false);
		}
	}
	public function getElements(){
		return array(
			new Element("p", array("class"=>"input error " . $this->addName("error")), "Cannot connect to the database, make sure the details are correct. Also, make sure your MySQL service is running."),
			new Element("h3", null, "Database Host"),
			new Element("input", array("name"=>$this->addName("host"), "placeholder"=>"localhost", "value"=>$this->config->DB_HOST ?: "")),
			new Element("h3", null, "Database Username"),
			new Element("input", array("name"=>$this->addName("username"), "placeholder"=>"root", "value"=>$this->config->DB_USERNAME ?: "")),
			new Element("h3", null, "Database Password"),
			new Element("input", array("name"=>$this->addName("password"), "type"=>"password", "placeholder"=>"(your database password)", "value"=>$this->config->DB_PASSWORD ?: "")),
			new Element("h3", null, "Database Name"),
			new Element("input", array("name"=>$this->addName("dbname"), "placeholder"=>"(e.g. Engine)", "value"=>$this->config->DB_NAME ?: ""))
		);
	}
	private function try_connect($HOST, $UNAME, $PASS, $DBNAME){
		try{
			if($HOST != null && $UNAME != null && $DBNAME != null){
				$dbh = new PDO("mysql:host=$HOST;", $UNAME, $PASS);
				return true;
			}
			return false;
		} catch (PDOException $e){
			return false;
		}
	}
	public function onSubmit(){
		$host = $_POST[$this->addName("host")];
		$username = $_POST[$this->addName("username")];
		$password = $_POST[$this->addName("password")];
		$name = $_POST[$this->addName("dbname")];
		if($this->try_connect($host, $username, $password, $name) === true){
			$dbh = new PDO("mysql:host=$host;", $username, $password);
			$dbh->exec("CREATE DATABASE IF NOT EXISTS " . $name);
			$this->config->setConfigFilename(TEMP_CONFIG_FILE);
			$this->config->DB_HOST = $host;
			$this->config->DB_USERNAME = $username;
			$this->config->DB_PASSWORD = $password;
			$this->config->DB_NAME = $name;
			$this->config->save(true);
		} else {
			$this->sendStatus(true, array($this->addName("error")));
		}

	}
}
?>