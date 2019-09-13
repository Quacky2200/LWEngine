<?php

namespace LWEngine\Config\Setup\Stages;

use LWEngine\Config\Setup\Stage as Stage;
use LWEngine\Engine as Engine;
use \Element as Element;

class Site extends Stage {

	public function __construct() {
		parent::__construct("engine-site-chapter", "Lastly...", "Select the site to use for this installation");
	}

	public function onLoad() {
		// Only enable this chapter if no valid site exists
		$Config = new \LWEngine\Config(TEMP_CONFIG_FILE, false);
		if ($Config->exists()) {
			if (($site = $Config->getProp('site')) &&
				$site !== null &&
				$this->testSite($site)) {
				$this->setEnabled(false);
			}
		}
	}

	private function testSite($site) {
		return Engine::instance()->getSitePath($site) !== null;
	}

	private function getOptions() {
		$sites = Engine::instance()->getAvailableSites();
		$elements = array();
		if (is_array($sites)) {
			foreach ($sites as $site) {
				$name = basename(dirname($site));
				array_push($elements, new Element("option", null, $name));
			}

			return $elements;
		}

		return null;
	}

	public function getElements() {
		return array(
			new Element("p", array("class"=>"input error " . $this->addName("error")), "Cannot select this site. Please select another"),
			new Element("p", array("class"=>"input error " . $this->addName("site-config-error")), "This site has an configuration error."),
			new Element("select", array("name"=>$this->addName("options")), $this->getOptions()),
		);
	}

	public function onSubmit() {
		$site = $_POST[$this->addName("options")];
		if ($this->testSite($site) === true) {
			$config = Engine::getConfig();
			$config->setFilePath(CONFIG_FILE);
			$config->setProp('site', $site);
			$config->save();
		} else {
			$this->sendStatus(true, array($this->addName("error")));
		}
	}
}
?>