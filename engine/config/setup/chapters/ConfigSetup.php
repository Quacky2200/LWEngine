<?php 
class ConfigSetup extends SetupChapter{
	private $htaccessFilename;
	public function __construct(){
		$rootpath = $_SERVER['DOCUMENT_ROOT'] . str_replace(basename($_SERVER['PHP_SELF']), "", $_SERVER['PHP_SELF']);
		$this->htaccessFilename = "$rootpath.htaccess";
		parent::__construct("engine-welcome-chapter", "Welcome", "Before we start, we first have to setup a few things to get you going. Please follow the steps, it will only take a couple of minutes.");
	}
	public function onLoad(){
		if(file_exists(TEMP_CONFIG_FILE) && file_exists($this->htaccessFilename)){
			$this->setEnabled(false);
		}
	}
	public function onSubmit(){
		ErrorHandler::stop();
		$config = Engine::getConfig();
		$config->setConfigFilename(TEMP_CONFIG_FILE);
		$done_config = $config->save(true);
		$done_htaccess = file_put_contents($this->htaccessFilename, 
"Options -Indexes
RewriteEngine On
#RewriteBase /
#RewriteCond %{HTTPS} !on
#RewriteRule (.*) https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

RewriteCond %{REQUEST_FILENAME} -f
RewriteRule \.(jp[e]?g|gif|png|css|js|ttf|woff|ico|bmp|pdf|doc[x]?)$ - [L]

#Redirect all files not match index.php
RewriteCond %{REQUEST_FILENAME} !(.*)/index\.php$
RewriteRule ^.*$ index.php?current_engine_page=$0 [L,NC,QSA]");
		if(!$done_htaccess || !$done_config || !in_array('mod_rewrite', apache_get_modules())){
			$this->sendStatus(true, array($this->addName('write-error')));
		}
	}
	public function getElements(){
		return array(
			new Element("p", array("class"=>"input error " . $this->addName("write-error")), "Cannot write to the directory. Make sure you have given the right privileges to your webserver software. <b>N.B.</b>You must have mod_rewrite installed and enabled."),
		);
	}
}
?>