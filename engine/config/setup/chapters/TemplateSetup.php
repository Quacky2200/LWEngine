<?php
class TemplateSetup extends SetupChapter{
	public function __construct(){
		parent::__construct("engine-template-chapter", "Template Time", "Select the template to use for this installation");
	}
	public function onLoad(){
		//If the template is from one of the templates then ignore
		$Config = new Config(TEMP_CONFIG_FILE, false);
		if($Config->configExists()){
			if(!is_null($Config->TEMPLATE) && $this->testTemplate($Config->TEMPLATE)){
				$this->setEnabled(false);
			}
		}
	}
	private function testTemplate($templateName){
		return Engine::getTemplate($templateName) !== null;
	}
	private function getTemplateOptions(){
		$templates = Engine::getTemplates();
		$elements = array();
		if(is_array($templates)){
			foreach($templates as $template){
				$name = basename(dirname($template));
				array_push($elements, new Element("option", null, $name));
			}
			return $elements;
		}
		return null;
	}
	public function getElements(){
		return array(
			new Element("p", array("class"=>"input error " . $this->addName("error")), "Cannot select this Template. Please select another"),
			new Element("p", array("class"=>"input error " . $this->addName("template-config-error")), "This template has an configuration error."),
			new Element("select", array("name"=>$this->addName("options")), $this->getTemplateOptions()),
		);
	}
	public function onSubmit(){
		$templateName = $_POST[$this->addName("options")];
		if($this->testTemplate($templateName) === true){
			$config = Engine::getConfig();
			$config->setConfigFilename(TEMP_CONFIG_FILE);
			$config->TEMPLATE = $templateName;
			$template = include(Engine::getTemplate($templateName));
			if(!$template instanceof Template){
				$this->sendStatus(true, array($this->addName("error")));
			} else{
				$template->configure($this);
				$config->save(true);
			}
		} else {
			$this->sendStatus(true, array($this->addName("error")));
		}
	}
}
?>