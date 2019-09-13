<?php

namespace LWEngine\Config\Setup;

class Setup extends \LWEngine\Site {

	static $name = 'LWEngine Setup';
	static $title = 'Start';
	static $description = 'Setup LWEngine configuration';
	static $routes = array(
		array(
			'name' => 'setup',
			'route' => '*',
			'handler' => /* \LWEngine\Config\Setup\Controllers\ */'Dialog->run'
		),
	);

	public function __construct() {
		// Do nothing at this time.
	}
}

return new Setup();
?>