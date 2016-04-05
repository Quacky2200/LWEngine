<?php 
include('Element.php');
abstract class SetupChapter extends Element{
	private $enabled = true;
	public final function isEnabled(){
		return $this->enabled;
	}
	protected final function setEnabled($value){
		$this->enabled = $value;
	}
	//What happens when the setup chapter instance is loaded (checking for completeness etc)
	public abstract function onLoad();
	//What happens when the setup chapter instance has been submitted to the website (to save the setup info)
	public abstract function onSubmit();
	//The input elements (can contain more as these are just element classes)
	public abstract function getElements();
	//The name for the submit form (to catch the POST submission)
	//headerName of the chapter
	//headerName describes the chapter to assure the user they're entering the right information in
	private $name, $headerName, $headerDescription;
	public function getName(){
		return $this->name;
	}
	public function addName($name){
		//Make the input errors easier...for now...
		return $this->getName() . '-' . $name;
	}
	public $SlideControls;
	public function sendStatus($failed = false, $details = null){
		ob_clean();
		die(json_encode(array("status"=>($failed ? "error" : "ok"), "details"=>$details)));
	}
	public function __construct($name, $headerName, $headerDescription){
		//Name to use on submit form
		$this->name = $name;
		$this->headerName = $headerName;
		$this->headerDescription = $headerDescription;
		$this->SlideControls = new Element("div", array("class"=>"SlideControls"), null);
		//Load the Chapter
		$this->onLoad();
		//Load onSubmit if we have POST'd
		if(isset($_POST[$this->name])){
			$this->onSubmit();
			$this->sendStatus();
		}
		parent::__construct("slide", null, array(
			new Element("form", array("method"=>"post"), array(
				new Element("section", null, array(
					new Element("div", null, array(
						new Element("h1", null, $this->headerName),
						new Element("p", null, $this->headerDescription),
						$this->getElements(),
						$this->SlideControls,
						new Element("section", array("class"=>"ajax"), new Element("div", null, new Element("div", null, array(
							new Element("div", array("class"=>"loader")),
							new Element("h1", null, "Working on it...")
						))))
					)), 
				))
			))
		));
	}
}
?>