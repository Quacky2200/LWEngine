<?php

namespace LWEngine;

require(__DIR__ . '/config/Config.php');
require(__DIR__ . '/Site.php');

define("CONFIG", dirname(__FILE__) . "/config/config-data.json");

use PDO as PDO;
use ErrorHandler as ErrorHandler;

class Engine {

	protected $site;
	protected static $debug = 0;
	protected static $autoloadDirs = array(
		__DIR__ . '/',
	);
	public $renderDefaults;

	public function __construct() {
		// Start allowing details to be saved/configured
		self::startSession();

		// Must be at least PHP 5.6.0
		$versionRequired = "5.6.0";
		if (!(version_compare(PHP_VERSION, $versionRequired) >= 0)) {
			ErrorHandler::primitiveError(500, "Cannot initiate Engine", "Requires PHP version $versionRequired and higher (Currently " . PHP_VERSION . ")");
		}

		// Allow us to automatically retrieve classes as best as possible
		$this->registerAutoloader();

		// Make sure buffering is correctly working.
		$this->initialiseBuffering();

		$this->initialiseConfig();
	}

	public static function instance() {
		static $instance;

		if ($instance === null) {
			$instance = new Engine();
		}

		return $instance;
	}

	/**
	 * Initialise Configuration.
	 *
	 * This function loads up the configuration file which is required by the
	 * engine, otherwise the primitive error handling service will throw a
	 * 'Cannot initiate configuration' error.
	 *
	 * @return void
	 */
	private function initialiseConfig() {
		try {
			$this->getConfig()->open();
			static::$debug = $this->config->getProp('debug', false);
		} catch (\Exception $e) {
		 	throw new \Exception('Cannot initiate configuration: ' . $e->getMessage());
		}
	}

	private function initialiseSite() {

		if (!$this->getConfig()->exists()) {
			$this->useSite(__DIR__ . '../config/setup/setup.php');
			return;
		}

		$site_name = $this->getConfiguredSiteName();

		if (($site = self::getSitePath($site_name)) && !is_null($site)) {
			$this->useSite($site);
		} else {
			throw new \Exception("Site ${site_name} is invalid or does not exist");
		}
	}

	/**
	 * Initialise Buffering.
	 *
	 * This function configures output buffering according to configuration
	 * settings.
	 *
	 * @return void
	 */
	private function initialiseBuffering() {
		// Silently remove everything here and restart buffer if necessary
		if (ob_get_status()) {
			ob_end_clean();
		}

		$buffer = $this->config->getProp('buffer', true);
		if ($buffer && !ob_get_status()) {
			ob_start();
		}
	}

	/**
	 * Autoload handler.
	 *
	 * This function handles autoloading classes that haven't yet been imported
	 * and tries to match as best as possible with the available autoloading
	 * directories currently configured.
	 *
	 * @param  string    $class    The class name
	 * @return mixed               Returns imported class or false
	 */
	protected function autoload($class) {
		$deps = array_reverse(static::$autoloadDirs);
		foreach ($deps as $dep) {
			$class = ltrim(str_replace('LWEngine\\', '', $class), '\\');
			$attempts = array(
				'filepath' => $dep . '/' . $class . '.php',
				'filepath_lower' => $dep . '/' . strtolower($class) . '.php',
				'filepath_basename' => $dep . '/' . basename($class) . '.php',
				'filepath_basename_lower' => $dep . '/' . strtolower(basename($class)) . '.php',
			);
			$attempts = array_flip(array_flip($attempts));
			foreach ($attempts as &$attempt) {
				$attempt = str_replace(
					DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR,
					DIRECTORY_SEPARATOR,
					preg_replace('/(\/|\\\)/', DIRECTORY_SEPARATOR, $attempt)
				);
				if (file_exists($attempt)) {
					return require_once($attempt);
				}
			}
		}
	}

	/**
	 * Register Autoloader.
	 *
	 * This function starts the handling of autoloading classes.
	 *
	 * @return void
	 */
	private function registerAutoloader() {
		spl_autoload_register(array(get_class($this), 'autoload'));
	}

	/**
	 * Unregister Autoloader.
	 *
	 * This function stops the handling of autoloading classes.
	 *
	 * @return void
	 */
	private function unregisterAutoloader() {
		spl_autoload_unregister(array(get_class($this), 'autoload'));
	}

	/**
	 * Add autoload directory.
	 *
	 * This adds a directory as a place to search for classes that are required
	 * and allows the need for using require in each file, and once required the
	 * class will automatically be discovered and imported.
	 *
	 * @param  string    $directory    The search directory
	 * @return void
	 */
	public function addAutoloadDirectory($directory) {
		if (is_string($directory) &&
			file_exists($directory) &&
			!in_array($directory, static::$autoloadDirs)) {
				\array_push(static::$autoloadDirs, $directory);
			return true;
		}

		return false;
	}

	/**
	 * Remove autoload directory.
	 *
	 * This removes a directory from one of the possible search locations during
	 * the autoloading process. Cleaning up can help keep good performance.
	 *
	 * @param  string    $directory    The search directory
	 * @return void
	 */
	public function removeAutoloadDirectory($directory) {
		if (is_string($directory) &&
			in_array($directory, static::$autoloadDirs)) {
			$key = array_search($directory, static::$autoloadDirs);
			unset(static::$autoloadDirs[$key]);
			return true;
		}

		return false;
	}

	/**
	 * Get Config.
	 *
	 * Returns current engine configuration.
	 *
	 * @return Config    engine configuration object
	 */
	public static function getConfig() {
		static $instance;
		if ($instance === null) {
			$instance = new Config(CONFIG, false);
		}

		return $instance;
	}

	/**
	 * Get Database.
	 *
	 * Returns the current database instance in the current configuration and is
	 * initialised automatically.
	 *
	 * @return Object    PDO database connection instance
	 */
	public static function getDatabase($config = null, $cache = true) {
		static $instance;

		if ($config === null) {
			$config = self::getConfig();
		}

		if ($instance === null || !$cache) {
			$driver = $config->getProp('db.driver', 'mysql');
			$host = $config->getProp('db.host', '127.0.0.1');
			$port = $config->getProp('db.port', '');
			$username = $config->getProp('db.username', 'root');
			$password = $config->getProp('db.password', '');
			$database = $config->getProp('db.database', '');
			$persistence = !!$config->getProp('db.persistence', true);

			if ($port > 0) {
				$host .= ':' . $port;
			}

			$opts = array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION);

			if ($persistence) {
				$opts[PDO::ATTR_PERSISTENT] = true;
			}

			$db = new PDO(
				"${driver}:host=${host};dbname=${database}",
				$username,
				$password,
				$opts
			);

			if ($cache) {
				$instance = $db;
			} else {
				return $db;
			}
		}

		return $instance;
	}

	/**
	 * Resolve Path.
	 *
	 * When given an unresolved path, the path will get transformed into the
	 * correct fully-formed resolved path. Example:
	 * /hello/world/../../test.php    >    /test.php
	 *
	 * @param  string    $path    The unresolved path
	 * @return string             The resolved path
	 */
	public final static function resolvePath($path) {
		$spath = str_replace('\\', '/', $path);
		$parts = explode('/', preg_replace("#/+#","/", $spath));
		$absolutes = array();
		foreach ($parts as $part) {
			if ('.' == $part) continue;
			if ('..' == $part) {
				array_pop($absolutes);
			} else {
				$absolutes[] = $part;
			}
		}

		return implode('/', $absolutes);
	}

	/**
	 * Get Local Directory.
	 *
	 * Returns the local directory of LWEngine (e.g. C:\XAMPP\htdocs\engine)
	 *
	 * @return string    Root Directory
	 */
	protected final function getLocalDir() {
		// Returns the local directory for the page
		$reflectionClass = new \ReflectionClass(get_class($this));
		return realpath(dirname($reflectionClass->getFileName()));
	}

	/**
	 * Get Root Remote Directory.
	 *
	 * Returns the remote root directory of LWEngine (e.g. /engine)
	 *
	 * @return string    Root Directory
	 */
	protected final function getRemoteDir() {
		static $remote;

		if ($remote === null) {
			$dir = $this->getLocalDir();
			$pos = strrpos($dir, dirname($_SERVER['SCRIPT_NAME']));
			if ($pos === false) {
				// Something's gone very wrong. Can't figure out root path.
				throw new \Exception('Unable to find remote path');
			}
			$remote = substr($dir, $pos);
		}

		return $remote;
	}

	/**
	 * Get Root File Path.
	 *
	 * Returns the local root directory of LWEngine (e.g. C:\XAMPP\htdocs)
	 *
	 * @return string    Root Directory
	 */
	protected final function getLocalRootDir() {
		return self::resolvePath($this->getLocalDir() . '/../');
	}

	/**
	 * Get Root Remote Path.
	 *
	 * Returns the remote root directory of LWEngine (e.g. /)
	 *
	 * @return string    Root Directory
	 */
	protected final function getRemoteRootDir() {
		return Engine::resolvePath($this->remoteDir, '/../');
	}

	/**
	 * Get Remote Absolute Path.
	 *
	 * Returns an absolute path for a remote URL
	 *
	 * @param  string $path
	 * @return string
	 */
	public function getRemoteAbsolutePath($path) {
		static $server, $local, $remote;

		// Returns nothing if the path is already an absolute address
		if (preg_match('/^(https?:\/\/|javascript:|(ftp|file|blob)?:\/\/)/', $path)) {
			return $path;
		}

		if ($server === null) {
			$protocol = $this->isSecure() ? 'https:' : 'http:';
			$port = $_SERVER['SERVER_PORT'];
			$host = $_SERVER['HTTP_HOST'] . ($port != 80 && $port != 443 ? ":${port}" : '');
			$server = "${protocol}//${host}";
		}

		if ($local === null) {
			$local = $this->getLocalRootDir();
		}

		if ($remote === null) {
			$remote = $this->getRemoteRelativePath('../');
		}

		$path = $this->resolvePath($path);
		$path =  $this->resolvePath($remote . '/' . ltrim(str_replace($local, '', $path), '/'));
		return "${server}${path}";
	}

	/**
	 * Get Remote Relative Path.
	 *
	 * Returns an relative path for a path
	 *
	 * @param  string    $path   Remote path
	 * @return string            Relative Remote Path
	 */
	public function getRemoteRelativePath($path) {
		static $root;

		// Returns nothing if the path is already an absolute address
		if (preg_match('/^(https?:\/\/|javascript:|(ftp|file|blob)?:\/\/)/', $path)) {
			throw new \Exception('Can only accept relative paths');
		}

		if ($root === null) {
			$root = $this->getRemoteRootDir();
		}

		return $this->resolvePath($root . '/' . ltrim(str_replace(__DIR__, '', $path), '/'));
	}

	/**
	 * Check if using secure host (HTTPS).
	 *
	 * Checks whether the incoming request was using HTTPS based on $_SERVER
	 * information.
	 *
	 * @return  boolean    Truth of secure connection condition
	 */
	public static function isSecure() {
		// Thank you to this answer: http://stackoverflow.com/questions/1175096/how-to-find-out-if-youre-using-https-without-serverhttps#answer-2886224
		return (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off") || $_SERVER["SERVER_PORT"] == 443;
	}

	/**
	 * Fixes URL paths.
	 *
	 * This function attempts to fix URL paths that have no start and end slash.
	 *
	 * If the URL contains HTTP(S):// then the fix will not be applied as it
	 * would always return /https://. Similiarly if we suspect that the path
	 * is a file due to the extension (e.g. .js, .json, .txt, .jpeg, etc.)
	 * then we will also prevent this from being fixed as it would always
	 * return file.jpeg/
	 *
	 * Some may prefer this to be forced with non-compliant URL schemes, where
	 * a person may use 'https://example.com/picture.jpeg/download' and can be
	 * forced with forceStart/forceStart.
	 *
	 * @param  string  $path          The URL path to fix
	 * @param  boolean $forceStart    Whether to prevent start URL checks
	 * @param  boolean $forceEnd      Whether to prevent end URL checks
	 * @return string                 The fixed path
	 */
	public static function fixPath($path, $forceStart = false, $forceEnd = false) {
		if (!preg_match('/^((https?)\:\/\/|\.{1,2}\/)/', $path) || $forceStart) {
			$path = (self::startsWith($path, "/") ? $path : "/" . $path);
		}
		if (!preg_match('/\.(\w+)$/', $path) || $forceEnd) {
			$path = (self::endsWith($path, "/") ? $path : $path . "/");
		}

		return preg_replace("#/+#","/", $path);
	}

	/**
	 * Check string starts with needle.
	 *
	 * This function checks whether the string starts with the specified string.
	 *
	 * @param  string $haystack    The string to check the start of
	 * @param  string $needle      The start string we're looking for
	 * @return boolean             Truth of condition
	 */
	public static function startsWith($haystack, $needle) {
		// Search backwards starting from haystack length characters from the end
		return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== FALSE;
	}

	/**
	 * Check string ends with needle.
	 *
	 * This function checks whether the string ends with the specified string.
	 *
	 * @param  string $haystack    The string to check the end
	 * @param  string $needle      The ending string we're looking for
	 * @return boolean             Truth of condition
	 */
	public static function endsWith(string $haystack, $needle) {
		// Search forward starting from end minus needle length characters
		return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== FALSE);
	}

	/**
	 * Generate a random string.
	 *
	 * This function generates a random string, defaulted to a length of 10, and
	 * a 0-9a-zA-Z alphabet.
	 *
	 * @param  integer $length        Length of generated string
	 * @param  string  $characters    Alphabet to create string
	 * @return string                 The generated string
	 */
	public static function generateRandomString($length = 10, $characters = null) {
		// Generate a random string of uppercase and lowercase letters including numbers 0-9.
		if (empty($characters)) {
			$characters = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
		}
		$str = "";
		for ($i = 0; $i < $length; $i++) {
			$str .= $characters[rand(0, strlen($characters) - 1)];
		}
		return $str;
	}

	/**
	 * Use Site
	 *
	 * This function loads the site so that it's ready to be used. An \Exception
	 * is silently thrown using the primitive error handling service if the site
	 * does not meet the requirements.
	 *
	 * @param  string    $filepath
	 * @return void
	 */
	public function useSite($filepath) {
		if (!is_string($filepath)) {
			throw new \Exception("Invalid site path");
		}

		$this->addAutoloadDirectory(dirname($filepath));
		$this->addAutoloadDirectory(realpath(dirname($filepath) . '/controllers'));
		$this->addAutoloadDirectory(realpath(dirname($filepath) . '/models'));
		$site = require($filepath);

		if ($site instanceof Site) {
			$this->site = $site;
		} else if (!($site instanceof Site)) {
			ErrorHandler::primitiveError(500, "Cannot initiate Site", $filepath . " must use the Site class.");
		}
	}

	/**
	 * Get Available Sites.
	 *
	 * Retrieves a list of compatible sites filepaths for us to use.
	 *
	 * @return  mixed    Array of site filepaths
	 */
	public function getAvailableSites() {
		// Get an array of sites
		$paths = \glob(__DIR__ . '/sites/*/*.php');

		return array_values(array_filter($paths, function($path) {
			$lower = strtolower($path);

			// Look for sitename and sitename entry (e.g. testing/testing.php)
			return basename(dirname($lower)) === str_replace('.php', '', basename($lower));
		}) ?: array());
	}

	/**
	 * Get Site Path.
	 *
	 * Retrieves the sites entry-point using the directory name of the site. If
	 * the entry-point filepath does not exist then this function will return
	 * null.
	 *
	 * @param  string $name    The safe name of the site
	 * @return string|null     The entry-point filepath or null.
	 */
	public function getSitePath($name) {
		$find = glob(__DIR__ . "/sites/*/*.php");
		foreach ($find as $i => $file) {
			if (strtolower($file) == strtolower(__DIR__ . "/sites/${name}/${name}.php")) {
				return (file_exists($file) ? realpath($file) : null);
			}
		}
		return null;
	}

	/**
	 * Get Current Site.
	 *
	 * Retrieves the current site in use by us.
	 *
	 * @return Site    The current site
	 */
	public function getSite() {
		return $this->site;
	}

	/**
	 * Get configured site name.
	 *
	 * Retrieves the configured site name within our configuration otherwise
	 * if no site is configured we will use an example site by default.
	 *
	 * If the example site cannot be found then we will throw an \Exception
	 * because we expect that no other site is available for use, nor were
	 * we setup to use it. Due to this, we expect any other site present in
	 * the site directory is not currently authorised for use and must be
	 * manually correct.
	 *
	 * @return string    Returns configured site name
	 */
	private function getConfiguredSiteName() {
		$demo = 'example';
		$site = $this->getConfig()->getProp('site', $demo);
		$site = preg_replace('/[^\w\d_ ]/', '', $site);
		$site = strtolower(str_replace(' ', '_', $site));

		if ($this->getSitePath($site) === null && $site === $demo) {
			throw new \Exception("The demo site '${demo}' is missing.");
		}

		return $site;
	}

	/**
	 * Run the engine.
	 *
	 * This function will traverse all the pages found within the site and
	 * will attempt to match the current request with the page's expected URL.
	 *
	 * @return void
	 */
	public function run() {

		$this->initialiseSite();

		try {
			// Traverse the site if possible, otherwise the site is null
			if (is_null($this->site)) {
				throw new \Exception("No site found during runtime.");
			} else {
				$url = $this->fixPath(isset($_GET['q']) ? $_GET['q'] : @$_SERVER['REQUEST_URI'] ?: '');
				$this->site->run($url);
			}
		} catch (\LWEngine\SiteExceptions\SiteNotFoundException $e) {
			die('404 - Not Found');
		} catch (\LWEngine\SiteExceptions\SiteRuntimeException $e) {
			ErrorHandler::primitiveError(500, 'There\'s a problem displaying this page', $e->getMessage());
		} catch (\LWEngine\SiteExceptions\EmptyRoutesException $e) {
			ErrorHandler::primitiveError(500, 'No routes have been added to this website', '(it\'s an empty site)');
		} catch (\Exception $e) {
			ErrorHandler::primitiveError(500, "Fatal Engine Error", $e->getMessage());
		}
	}

	/**
	 * Redirect.
	 *
	 * Attempts as best as possible to redirect to a URL. If the headers are
	 * sent then it will automatically send out a clickable redirect link. It
	 * will also try to click the link using JavaScript.
	 *
	 * This will kill the whole script to prevent extra processing.
	 *
	 * @return void
	 */
	private function redirect($url) {
		if (!headers_sent()) {
			header("Location: ${url}");
		} else {
			if (ob_get_status()) {
				ob_end_clean();
			}
			echo "Redirecting to <a id='redirect_me' href=\"${url}\">${url}</a>. " +
			"Click the link if redirection was not successful" +
			"<script>document.getElementById('redirect_me').click();</script>";
		}
		die();
	}

	/**
	 * Render.
	 *
	 * This function will render a view with the specified data using Twig.
	 *
	 * @return void
	 */
	public function render($filepath, $data = null) {
		$loader = new \Twig\Loader\FilesystemLoader($this->site->localDir . '/views');
		$twig = new \Twig\Environment(
			$loader,
			array_merge(array(
				'cache' => (static::$debug > 0 ? false : $this->localDir . '/cache'),
				'debug' => static::$debug > 0
			), $this->config->getProp('twig', array()))
		);
		$twig->addExtension(new \Twig\Extension\DebugExtension());

		if ($data === null) {
			$data = array();
		}

		$setDefaults = (is_array($this->renderDefaults) ? $this->renderDefaults : array());

		$data = array_merge(array(
			'engine' => $this,
			'site' => $this->site,
			'controller' => $this->site->controller,
			'this' => $this->site->controller,
			'title' => 'Untitled',
		), $setDefaults, $data);

		return $twig->render($filepath . '.twig', $data);
	}

	/**
	 * Log
	 *
	 * This function will save data into the correct log with the appropriate
	 * type, or will revert to the error log.
	 *
	 * @param  string    $data    String to log
	 * @param  string    $type    Type of information to log
	 * @return void
	 */
	private function _log($data, $type) {
		$path = $this->config->getProp("log.${type}", null);

		if ($path === null) {
			error_log($args);
		} else {
			if ($type == 'error') {
				error_log($args);
			}
			\file_put_contents($path, $args, LOCK_EX | FILE_APPEND);
		}
	}

	/**
	 * Log Information.
	 *
	 * This function will print information to the log. Many arguments can be
	 * specified and will be split by spaces.
	 *
	 * @return void
	 */
	private function logInfo(/* arguments(n) */) {
		$args = func_get_args();
		$str = '';
		foreach ($args as &$arg) {
			if (!is_string($arg) && is_object($arg)) {
				$arg = $arg->toString() ?: var_export($arg, true);
			} else if (is_array($arg)) {
				$arg = json_encode($arg);
			}
		}
		$args = implode(' ', $args);

		$this->_log($args, 'information');
	}

	/**
	 * Log Error.
	 *
	 * This function will print information to the error log. Many arguments can
	 * be specified and will be split by spaces.
	 *
	 * @return void
	 */
	private function logError(/* arguments(n) */) {
		$args = func_get_args();
		$str = '';
		foreach ($args as &$arg) {
			if (!is_string($arg) && is_object($arg)) {
				$arg = $arg->toString() ?: var_export($arg, true);
			} else if (is_array($arg)) {
				$arg = json_encode($arg);
			}
		}
		$args = implode(' ', $args);

		$this->_log($args, 'error');
	}


	/**
	 * Get Object Property.
	 *
	 * Attempt to retrieve an object property or return null.
	 *
	 * @param  string    $name    Property name
	 * @return mixed              Property value or null
	 */
	public function __get($name) {
		switch ($name) {
			case 'version':
				return '1.0.0';
			case 'config':
				return $this->getConfig();
			case 'debug':
				return static::$debug;
			case 'db':
			case 'database':
				return $this->getDatabase();
			case 'site':
				return $this->site;
			case 'controller':
				return $this->site->controller;
			case 'localDir':
				return $this->getLocalDir();
			case 'remoteDir':
				return $this->getRemoteDir();
			case 'localRootDir':
				return $this->getLocalRootDir();
			case 'remoteRootDir':
				return $this->getRemoteRootDir();
			case 'isSecure':
				return self::isSecure();
			default:
				return null;
		}
	}

	public function __isset($name) {
		switch ($name) {
			case 'version':
			case 'config':
			case 'debug':
			case 'db':
			case 'database':
			case 'site':
			case 'controller':
			case 'localDir':
			case 'remoteDir':
			case 'localRootDir':
			case 'remoteRootDir':
			case 'isSecure':
				return true;
			default:
				return false;
		}
	}

	/**
	 * Start Session.
	 *
	 * Starts session storage.
	 *
	 * @return void
	 */
	public static function startSession() {
		if (headers_sent()) {
			throw new \Exception('Unable to start a session when headers have already been set');
		}
		session_start();
	}

	/**
	 * Clear Session.
	 *
	 * This will remove everything stored in the session and will reset it back
	 * to it's original state. The session will not have been restarted and must
	 * be started manually.
	 *
	 * @return void
	 */
	public static function clearSession() {
		session_unset();
		session_destroy();
		$_SESSION[] = array();
		// Delete the session cookie
		if (init_get("session.use_cookies")) {
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
