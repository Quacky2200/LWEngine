<?php
define("CONFIG", dirname(__FILE__) . "/config/config-data.json");
class Engine{
	private $currentTemplate;
	public function __construct(){
		self::startSession();
		$this->getRequiredVersion();
		$this->getRequiredLibraries();
		$this->getRequiredConfig();
		$this->currentTemplate = self::getTemplate($this->getConfig()->TEMPLATE);
		$this->useTemplate($this->currentTemplate);
		$this->run();
	}
	private function getRequiredLibraries(){
		try{
			require(__DIR__ . "/config/Config.php");
			require(__DIR__ . "/structure/Template.php");
			require(__DIR__ . "/structure/Page.php");
		} catch(Exception $e){
			ErrorHandler::primitiveError(500, "Cannot load Engine libraries", $e->getMessage());
		}
	}
	private function getRequiredVersion(){
		$versionRequired = "5.3.0";
		if (!(version_compare(PHP_VERSION, $versionRequired) >= 0)){
			ErrorHandler::primitiveError(500, "Cannot initiate Engine", "Requires PHP version $versionRequired and higher (Currently " . PHP_VERSION . ")");
		}
	}
	private function getRequiredConfig(){
		try{
			if(!$this->getConfig()->configExists()){
				$this->currentTemplate = Engine::requireFile(__DIR__ . "/config/setup/main.php");
				$this->run();
				exit(0);
			}
			$this->getConfig()->open();
		} catch (Exception $e) {
			ErrorHandler::primitiveError(500, "Cannot initiate configuration", $e->getMessage());
		}
	}
	public static function getConfig(){
		static $instance;
		if(null === $instance){
			$instance = new Config(CONFIG, false);
		}
		return $instance;
	}
	public static function getDatabase(){
		static $instance;
		if(null === $instance){
			$config = self::getConfig();
			$instance = new PDO("mysql:host=$config->DB_HOST;dbname=$config->DB_NAME", $config->DB_USERNAME, $config->DB_PASSWORD);
		}
		return $instance;
	}
	public static function requireAllInDir($dir){
		$returnedFiles = array();
		foreach(glob($dir . "*.php") as $filename){
			array_push($returnedFiles, self::requireFile(realpath($filename)));
		}
		return $returnedFiles;
	}
	public static function requireFile($file){
		$required = require($file);
		return $required;
	}
	public static function getLocalDir(){
		return __DIR__;
	}
	public static function getRemoteDir($path){
		/*	
			This function parses a path to return a relative URL (except http:// or https://)
			in attempt to transform a current relative URL such as "/home" or filepath 
			(e.g. "C:\XAMPP\htdocs\index.php"). This then allows files such as CSS, JS, or
			even pages relating to the website to be safely transformed into usable addresses.
			This is mostly because webservers sometimes store your site in an annoying manner,
			For example, the Cardiff Uni project server use your email username to create an 
			alias that is used as your document root. This then affects the way our files and
			pages can be retrieved. To relieve this kind of stress, I created this function to
			do it all for us. My Cardiff Uni project alias is JamesM27 and would create a lot 
			of pain when redirecting files and would treat the root folder as "/". Using 
			DOCUMENT_ROOT would allow files to remove the local directory but with aliases it
			created a problem when dealing with pages. I used PHP_SELF to get the relative 
			running PHP script ("/JamesM27/Tests/index.php") and removed the filename allowing
			me to get the directory ("/JamesM27/Tests/") so that pages such as "/home" would be
			treated as "/JamesM27/Tests/home", and thereby removing the pain.

			DOCUMENT_ROOT is where the host storing our stuff (e.g. C:\XAMPP\htdocs\).
			We get the real path of where it is stored and replace everything there
			with nothing so that something as basic as C:\XAMPP\htdocs\index.php
			becomes \index.php
				
			PHP_SELF is the script that is currently running. As our engine is created 
			remotely from (e.g. /index.php), we get then get the basename of PHP_SELF
			which is "index.php" and replace it with nothing. That then leaves us with
			"/" and allows us to know the working directory of the script. We then 
			replace all the backwards slashes with forward slashes in case we're on
			Windows. We then want to replace our 
		*/
		if(Engine::startsWith($path, "http://") || Engine::startsWith($path, "https://")) return $path;
		$newPath = str_replace(realpath($_SERVER["DOCUMENT_ROOT"]), "", $path);
		//Get the working directory from PHP_SELF
		$workingDir = str_replace(basename($_SERVER["PHP_SELF"]), "", $_SERVER["PHP_SELF"]);
		//Replace all the backslashes with forward slashes.
		$newPath = str_replace("\\", "/",  $newPath);
		//If we currently aren't in the working directory, add it.
		if(!Engine::startsWith($newPath, $workingDir)) $newPath = str_replace("//", "/", $workingDir . $newPath);
		//Give back our new path we created
		return $newPath;
	}
	public static function getRemoteAbsolutePath($path){
		//Get the fixed relative directory
		$newPath = Engine::getRemoteDir($path);
		//Return the absolute path (e.g. "http://localhost/home" or "https://project.cs.cf.ac.uk/JamesM27/Tests/home")
		return (self::isSecure() ? "https://" : "http://") . $_SERVER["HTTP_HOST"] . $newPath;
	}
	public static function isSecure(){
		//Thank you to this answer: http://stackoverflow.com/questions/1175096/how-to-find-out-if-youre-using-https-without-serverhttps#answer-2886224
		return (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off") || $_SERVER["SERVER_PORT"] == 443;
	}
	public static function fixPath($path){
		//Return a path that has missing starting and ending slashes
		$path = (self::startsWith($path, "/") ? $path : "/" . $path);
		$path = (self::endsWith($path, "/") ? $path : $path . "/");
		return preg_replace("#/+#","/", $path);
	}
	public static function startsWith($haystack, $needle) {
		// search backwards starting from haystack length characters from the end
		return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== FALSE;
	}
	public static function endsWith($haystack, $needle) {
		// search forward starting from end minus needle length characters
		return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== FALSE);
	}
	public static function generateRandomString($length = 10) {
		//Generate a random string of uppercase and lowercase letters including numbers 0-9.
		//Can be used to create seeds/ID"s etc etc
		$characters = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
		$randomString = "";
		for ($i = 0; $i < $length; $i++) {
			$randomString .= $characters[rand(0, strlen($characters) - 1)];
		}
		return $randomString;
	}
	public function useTemplate($filename){
		$template = self::requireFile($filename);
		if($template instanceof Template){
			$this->currentTemplate = $template;
		} else if (!($template instanceof Template)){
			ErrorHandler::primitiveError(500, "Cannot initiate Template", $filename . "<br/>File above must use the Template class.");
		}
	}
	public static function getTemplates(){
		//Get an array of templates
		$paths = glob(self::getLocalDir() . "/templates/*/main.php");
		foreach($paths as &$path){
			//Fix their path when returning the the template
			$path = realpath($path);
		}
		return $paths;
	}
	public static function getTemplate($Name){
		$find = __DIR__ . "/templates/" . $Name . "/main.php";
		return (file_exists($find) ? realpath($find) : null);
	}
	public function run(){
		try{
			//Traverse the Template if possible, otherwise the template is null;
			if(is_null($this->currentTemplate)){
				throw new Exception("No template found.");
			} else {
				$url = $this->fixPath(isset($_GET["current_engine_page"]) ? $_GET["current_engine_page"] : "");	
				$this->currentTemplate->traverse($url);
			}
		} catch (Exception $e) {
			ErrorHandler::primitiveError(500, "Cannot run Engine", $e->getMessage());
		}
	}
	public static function startSession(){
		session_start();
	}
	public static function clearSession(){
		session_unset();
        session_destroy();
        $_SESSION[] = array();
        //Delete the session cookie
        if(init_get("session.use_cookies")){
            $params = session_get_cookie_params();
            setcookie(session_name(), "", time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
	}
}
?>