<?php
require_once('SetupDialog.php');
require_once('SetupChapter.php');
Engine::requireAllInDir(__DIR__ . '/chapters/');
class Setup extends Template{
	public function getName(){
		return __CLASS__;
	}
	public function configure($setup){}
	public function getPages(){
		return array(
			new SetupDialog(array(
				new ConfigSetup(),
				new DatabaseSetup(),
				new TemplateSetup(),
				new FinishSetup()
			))
		);
	}
}
return new Setup();
?>