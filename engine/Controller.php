<?php

namespace LWEngine;

abstract class Controller {

	public function __construct() {
		// Do nothing for now.
	}

	/**
	 * Generate URL.
	 *
	 * Generates a URL from the route name, and fills in any route parameters.
	 *
	 * @param  string   $name    Route name
	 * @param  mixed    $data    Route parameters
	 * @return string            Generated URL
	 */
	public function generateUrl($name, $data = null) {
		return Engine::instance()->site->generateUrl($name, $data);
	}

	/**
	 * Render.
	 *
	 * This function renders a view file with some accompanying data.
	 *
	 * @param  string    $filepath    The view filepath
	 * @param  mixed     $data        Data to use for view render
	 * @return void
	 */
	public function render($file, $data = null) {
		return $this->site->render($file, $data);
	}

	/**
	 * Render.
	 *
	 * This function renders a view file with some accompanying data and echoes
	 * it to the screen/client.
	 *
	 * @param  string    $filepath    The view filepath
	 * @param  mixed     $data        Data to use for view render
	 * @return void
	 */
	public function renderOut($file, $data = null) {
		echo $this->site->render($file, $data);
	}

	protected final function getLocalDir() {
		static $local;

		if ($local === null) {
			// Returns the local directory for the page
			$reflectionClass = new \ReflectionClass(get_class($this));
			$local = realpath(dirname($reflectionClass->getFileName()));
		}

		return $local;
	}

	protected final function getRemoteDir() {
		static $remote;

		if ($remote === null) {
			$dir = $this->getLocalDir();
			$pos = strrpos($dir, $this->engine->remoteDir);
			if ($pos === false) {
				// Something's gone very wrong. Can't figure out root path.
				throw new \Exception('Unable to find remote path');
			}
			$remote = substr($dir, $pos);
		}

		return $remote;
	}

	public function __get($name) {
		switch ($name) {
			case 'db':
			case 'database':
				return \LWEngine\Engine::instance()->database;
			case 'engine':
				return \LWEngine\Engine::instance();
			case 'site':
				return \LWEngine\Engine::instance()->site;
			case 'localDir':
				return $this->getLocalDir();
			case 'remoteDir':
				return $this->getRemoteDir();
			case 'publicDir':
				return $this->site->remoteDir . '/public';
			default:
				return null;
		}
	}

	public function __isset($name) {
		switch ($name) {
			case 'db':
			case 'database':
			case 'engine':
			case 'site':
			case 'localDir':
			case 'remoteDir':
			case 'publicDir':
				return true;
			default:
				return false;
		}
	}
}
?>