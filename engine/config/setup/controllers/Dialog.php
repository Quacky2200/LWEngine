<?php

namespace LWEngine\Config\Setup\Controllers;

use \LWEngine\Config\Setup\Stages as Stages;
use \LWEngine\Engine as Engine;
use \Element as Element;

define('TEMP_CONFIG_FILE', __DIR__ . '/../temp-config-data.json');
define('CONFIG_FILE', __DIR__ . '/../../config-data.json');

class Dialog extends \LWEngine\Controller {

	public function __construct() {
		$this->engine->addAutoloadDirectory(__DIR__ . '/../libs');
		$this->engine->addAutoloadDirectory(__DIR__ . '/../libs/stages');
	}

	public function run() {

		if (!file_exists(CONFIG_FILE)) {
			Engine::getConfig()->setFilePath(TEMP_CONFIG_FILE);
			Engine::getConfig()->open();
		}

		$stages = $this->retrieveStages(array(
			new Stages\Config(),
			new Stages\Database(),
			new Stages\Site(),
			new Stages\Finish(),
		));

		require(__DIR__ . '/../views/header.php');
		$slides = new Element("slides", null, $stages);
		echo $slides->toHTML();
		require(__DIR__ . '/../views/footer.php');
	}

	public function echoStylesheets($stylesheets) {
		foreach ($stylesheets as $i => $css) {
			$path = $this->engine->getRemoteAbsolutePath(
				$this->site->localDir . '/public/' . $css
			);
			echo '<link rel="stylesheet" type="text/css" href="' . $path . '">';
		}
	}

	public function echoScripts($scripts) {
		foreach ($scripts as $i => $script) {
			$path = $this->engine->getRemoteAbsolutePath(
				$this->site->localDir . '/public/' . $script
			);
			echo '<script src="' . $path . '" type="text/javascript"></script>';
		}
	}

	private function retrieveStages($stages) {
		if (!is_null($stages)) {

			$stages = array_values(array_filter($stages, function($stage) {
				return $stage->isEnabled();
			}));

			foreach ($stages as $key => $stage) {
				$stage->controls->elements = array();
				if ($key == 0) {
					// Show first stage
					$stage->attributes = array('class' => 'active');
				}
				if ($key > 0) {
					// Show Previous
					array_push(
						$stage->controls->elements,
						new Element("input", array(
							"type" => "submit",
							"value" => "Previous"
						), "&nbsp;")
					);
				}
				if ($key < count($stages) - 1) {
					// Show Next
					array_push(
						$stage->controls->elements,
						new Element("input", array(
							"type" => "submit",
							"value" => "Next",
							"name" => $stage->getName()
						), null)
					);
				} else {
					// Show Finish
					array_push(
						$stage->controls->elements,
						new Element("input", array(
							"type" => "submit",
							"value" => "Finish",
							"name" => $stage->getName()
						), null)
					);
				}
			}

			return $stages;
		}
	}
}
?>