<?php
abstract class Template{
	private $CSS, $JS;
	public function __construct(){
		$this->CSS = array();
		$this->JS = array();
	}
	public final function addCSS($filename){
		return array_push($this->CSS, $filename);
	}
	public final function addJS(){
		return array_push($this->JS, $filename);
	}
	public final function addMultipleCSS($Multiple){
		$this->CSS = array_unique(array_merge($this->CSS, $Multiple));
	}
	public final function addMultipleJS($Multiple){
		$this->JS = array_unique(array_merge($this->JS, $Multiple));;
	}
	public abstract function getPages();
	public abstract function getName();
	public abstract function configure($setup);
	//Return array of CSS and JS Dependencies
	public final function getCSSAndJSDependencies($appendTemplateDefaultPublicDirectory = true){
		$values = array();
		foreach($this->CSS as $value){
			$url = (strpos($value, "http://") > -1 || strpos($value, "https://") > -1 ? $value : Engine::getRemoteDir(($appendTemplateDefaultPublicDirectory ? $this->getLocalDir() . '/public/' . $value : $value)));
			array_push($values, "<link href='" . $url . "' rel='stylesheet' type='text/css'/>");
		}
		foreach($this->JS as $value){
			$url = (strpos($value, "http:") > -1 || strpos($value, "https://") > -1 ? $value : Engine::getRemoteDir(($appendTemplateDefaultPublicDirectory ? $this->getLocalDir() . '/public/' . $value : $value)));
			array_push($values, "<script src='" . $url . "'></script>");
		}
		return $values;
	}
	//Echo out the CSS and JS Dependencies
	public final function echoCSSAndJSDependencies(){
		echo implode("\n", $this->getCSSAndJSDependencies());
	}
	public final function getLocalDir(){
		//Returns the local directory for the page
		$reflectionClass = new ReflectionClass(get_class($this));
		return dirname($reflectionClass->getFileName());
	}
	public final function traverse($URL){
		$pages = $this->getPages();
		if (!is_null($pages) and $pages !== false){
			foreach($pages as $Page){
				if(!is_null($Page) && $Page instanceof Page && $Page->isMatch($URL)){
					try{
						$Page->run($this);
						$Page->show($this);
						return null;
					} catch (Exception $e){
						ErrorHandler::primitiveError(500, "Page script error", $e->getMessage());
					}
				}
			}
		}
		ErrorHandler::primitiveError(404, "Page not found", "Cannot find any pages with this URL.");
	}
}
?>