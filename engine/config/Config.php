<?php
class Config{
	/*
		Config Data
	*/
	public $DB_HOST = 'localhost';
	public $DB_USERNAME, $DB_PASSWORD, $DB_NAME;
	public $TEMPLATE;
	/*
		Config Functionality
	*/
	private static $configFilename;
	public function __construct($configFilename, $automaticallyOpen = true){
		self::$configFilename = $configFilename;
		if($automaticallyOpen){
			if($this->configExists()){
				$this->open();
			} else{
				throw new Exception("Configuration file was set to automatically open, but it doesn't exist.");
			}
		}
	}
	public function configExists(){
		return file_exists(self::$configFilename);
	}
	public function getConfigFilename(){
		return self::$configFilename;
	}
	public function setConfigFilename($value){
		self::$configFilename = $value;
	}
	public function open(){
		if(file_exists(self::$configFilename)){
			$contents = json_decode(file_get_contents(self::$configFilename));
			foreach($contents as $key=>$value){
				$this->$key = $value;
			}
			return true;
		}
		else{
			return false;
		}
	}
	public function save($overwrite = false){
		if($overwrite || (!file_exists(self::$configFilename) && !$overwrite)){
			return file_put_contents(self::$configFilename, json_encode(get_object_vars($this), JSON_PRETTY_PRINT), LOCK_EX);
		}
		else{
			return false;
		}
	}
}
?>
