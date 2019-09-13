<?php

namespace LWEngine\Config\Setup\Stages;

use LWEngine\Config\Setup\Stage as Stage;
use LWEngine\Engine as Engine;
use \Element as Element;

class Finish extends Stage {

	public function __construct() {
		parent::__construct("engine-finish-chapter", "Hurrah!", "The setup was successful!<br/><br/><div style='width:500px;height:50px'></div>Click Finish to exit the setup.");
	}

	public function onLoad() {}

	public function onSubmit() {
		rename(TEMP_CONFIG_FILE, CONFIG_FILE);
		$this->sendStatus(false, Engine::instance()->getRemoteAbsolutePath('/'));
	}

	public function getElements() {
		return array();
	}
}
?>
