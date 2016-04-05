<?php 
abstract class Page{
	//The name of the page
	public abstract function getName();
	//Get the official URL for the page
	public abstract function getURL();
	//Let the derived class determine if they match the URL by returning a true/false
	public abstract function isMatch($URL);
	
	public function getGeneratedTitle($template, $includeElement = false){
		//Generate a nice Title for our page
		$startElement = ($includeElement ? "<title>" : "");
		$endElement = ($includeElement ? "</title>" : "");
		return $startElement . $this->getName() . " - " . $template->getName() . $endElement;
	}
	//Run the code for the page
	public abstract function run($template);
	//Start showing the page details
	public abstract function show($template);
}
?>