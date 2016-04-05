<?php 
include("../engine/Engine.php");
include("../engine/config/Config.php");
class EngineTest extends \PHPUnit_Framework_TestCase{
	/* 
		At the moment we can only run the ones present
		as they are the only ones that aren't affected
		by the instance. For example, we cannot test the
		instance of Engine because it start's running
		and the instance functions are actually methods and also,
		won't return any valuable information.
	*/
	public function test_getConfig(){
		//Make sure that what we get from the engine, gives us the actual config class and an instantiated object
		$this->assertEquals(true, Engine::getConfig() !== null && Engine::getConfig() instanceof Config);
	}
	public function test_getLocalDir(){
		//Get directory
		$dir = Engine::getLocalDir();
		//Test that the directory exists
		$this->assertEquals(true, file_exists($dir));
		//get the path to where we think the engine SHOULD exist
		$getPath = realpath(__DIR__ . "/../engine/");
		//Check whether the engine exists to where we think it is.
		$this->assertEquals(true, $dir == $getPath);
	}
	public function test_getRemoteDir(){
		/*
			This unit test is against the filepath of the engine.
			This is so that the function gives us a relative URL 
			rather than a filepath, this then allows us to transform
			paths such as CSS etc to their correct local on a website.
			With the assistance of getRemoteAbsolutePath, we can get 
			the server address and also the virtualhost address if there
			is one. (e.g. Cardiff Uni project server use your email username,
			in other words, mine is /JamesM27 on the project.cs.cf.ac.uk domain
			however, without this fix, apache treats the folder as root, i.e. "/").
		*/
		$_SERVER['DOCUMENT_ROOT'] = "/var/www/";
		$this->assertEquals("/", Engine::getRemoteDir("/"));
		//Test against a realworld scenario, i.e. our actual file path.
		$_SERVER['DOCUMENT_ROOT'] = realpath(__DIR__ . '../');
		$this->assertEquals("/home", Engine::getRemoteDir(__DIR__ . "/home"));
	}
	public function test_getRemoteAbsolutePath(){
		//These are some unit test variables that we need to use
		//We need to know if the function is able to give us the 
		//right URL.
		$_SERVER['SERVER_PORT'] = 80;
		$_SERVER['HTTP_HOST'] = "localhost";
		$_SERVER['REMOTE_ADDRESS'] = "/";
		$this->assertEquals("http://localhost/", Engine::getRemoteAbsolutePath("/"));
	}
	public function test_isSecure(){
		//We use some unit test variables to make sure that in a real-world scenario,
		//if a server were to be using HTTPS, it would be able to detect it (normally 443)
		$_SERVER['SERVER_PORT'] = 80;
		$this->assertEquals(false, Engine::isSecure());
		$_SERVER['SERVER_PORT'] = 443;
		$this->assertEquals(true, Engine::isSecure());
	}
	public function test_fixPath(){
		//We assume that the fixPath function will insert both start and end slashes
		$this->assertEquals("/hello/world/", Engine::fixPath("hello/world"));
		$this->assertEquals("/hello/world/", Engine::fixPath("hello/world/"));
		$this->assertEquals("/hello/world/", Engine::fixPath("/hello/world"));
		$this->assertEquals("/", Engine::fixPath("/"));
	}
	public function test_startsWith(){
		//This test, tests against the function startsWith which is used to detect if a string starts in a string
		//A good example is to test if a string starts with hello in "helloworld"
		$this->assertEquals(true, Engine::startsWith("helloworld", "hello"));
		//Here we make sure it actually works by giving it a wrong value, we expect to get false
		$this->assertEquals(false, Engine::startsWith("helloworld", "world"));
	}
	public function test_endsWith(){
		//The same as startsWith but instead of starting, we're checking that a string ends in another string
		//We use the same string to check if helloworld ends with world
		$this->assertEquals(true, Engine::endsWith("helloworld", "world"));
		//We also make sure it works again
		$this->assertEquals(false, Engine::endsWith("helloworld", "hello"));
	}
	public function test_generateRandomString(){
		//We can only test length
		//We can't really test against a random, not in this function anyways.
		$this->assertEquals(10, strlen(Engine::generateRandomString(10)));
	}
	public function test_getTemplates(){
		//We need to test the getTemplates function in Engine so that we are able to
		//get all the available templates within the system we then test to make sure
		//that the template includes a real directory and filename. A correct template
		//will use the filename main.php
		foreach(Engine::getTemplates() as $value){
			$this->assertEquals(true, file_exists($value) && Engine::endsWith($value, "main.php"));
		}
	}
	public function test_getTemplate(){
		//We are testing the getTemplate function in Engine.
		//We want to know if getTemplate returns the filename to the template name. For example "FacebookAnalyser" should go to /engine/templates/FacebookAnalyser/main.php
		//The function must return the path to the main template file, we remove the main.php to get the template name, and we also test with our getTemplates function so
		//that it doesn't matter what templates are there, as long as our function returns the right path.

		//Get a list of templates, then test for each one if it can get the template path and that it's in the template array
		$this->assertEquals(null, Engine::getTemplate(""));
		//Get list of templates
		foreach(Engine::getTemplates() as $value){
			//Test that the function returns the same as the value from the getTemplates array
			$this->assertEquals($value, Engine::getTemplate(basename(str_replace(basename($value), "", $value))));
		}
	}
}
?>