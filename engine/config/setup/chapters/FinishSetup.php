<?php 
class FinishSetup extends SetupChapter{
	public function __construct(){
		parent::__construct("engine-finish-chapter", "Hurrah!", "The setup was successful!<br/><br/><div style='width:500px;height:50px'></div>Click Finish to exit the setup.");
	}
	public function onLoad(){}

	public function onSubmit(){
		rename(TEMP_CONFIG_FILE, CONFIG_FILE);
		$this->sendStatus(false, Engine::getRemoteDir($_SERVER['REQUEST_URI']));
	}
	public function getElements(){
		return array();
	}
}
?>