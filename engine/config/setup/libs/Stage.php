<?php

namespace LWEngine\Config\Setup;
use \Element as Element;

abstract class Stage extends Element {

	// Enabled allows chapters to be dynamically updated
	private $enabled = true;

	// The name for the submit form (to catch the POST submission)
	private $name;

	// headerName is the title of the chapter
	private $headerName;

	// headerDescription describes what chapter with helpful information
	private $headerDescription;

	// Control actions for the setup form
	public $controls;

	public final function isEnabled() {
		return $this->enabled;
	}

	protected final function setEnabled($value) {
		$this->enabled = $value;
	}

	// What happens when the setup chapter instance is loaded (checking for completeness etc)
	public abstract function onLoad();

	// What happens when the setup chapter instance has been submitted to the website (to save the setup info)
	public abstract function onSubmit();

	// The input elements (can contain more as these are just element classes)
	public abstract function getElements();

	public function getName() {
		return $this->name;
	}

	public function addName($name) {
		// Make the input errors easier...for now...
		return $this->getName() . '-' . $name;
	}


	public function sendStatus($failed = false, $details = null) {
		//try{ob_clean();} catch(Exception $e){}
		die(json_encode(array("status"=>($failed ? "error" : "ok"), "details"=>$details)));
	}

	public function __construct($name, $headerName, $headerDescription) {
		// Name to use on submit form
		$this->name = $name;
		$this->headerName = $headerName;
		$this->headerDescription = $headerDescription;
		$this->controls = new Element("div", array("class" => "SlideControls"), null);

		// Load the Chapter
		$this->onLoad();

		// Load onSubmit if we have POST'd
		if(isset($_POST[$this->name])){
			$this->onSubmit();
			$this->sendStatus();
		}

		parent::__construct("slide", null, array(
			new Element("form", array("method" => "post"), array(
				new Element("section", null, array(
					new Element("div", null, array(
						new Element("h1", null, $this->headerName),
						new Element("p", null, $this->headerDescription),
						$this->getElements(),
						$this->controls,
						new Element("section", array("class" => "ajax"), new Element("div", null, new Element("div", null, array(
							new Element("div", array("class" => "loader")),
							new Element("h1", null, "Working on it...")
						))))
					)),
				))
			))
		));
	}
}
?>
