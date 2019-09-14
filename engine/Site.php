<?php

namespace LWEngine;

require_once(__DIR__ . '/SiteExceptions.php');

use LWEngine\SiteExceptions as Exceptions;
use LWEngine\Engine as Engine;

abstract class Site {

	protected static $name = 'Example Site';

	protected static $description = 'This is an example site';

	// protected static $title = '';

	protected static $routes = null;

	protected $controller = null;

	public function __construct() {
		if (static::$routes === null) {
			// Attempt to preload routes from file
			try {
				static::$routes = $this->getRoutes();
			} catch (\Exception $e) {
				// No point throwing here yet.
			}
		}
	}

	public function setupInstall($setup) {
		Engine::log(__METHOD__ . ':- No setup installation functionality present');
	}

	public function setupRemove($setups) {
		Engine::log(__METHOD__ . ':- No setup removal functionality present');
	}

	private function getRoutes() {
		if (static::$routes !== null) {
			return static::$routes;
		} else {
			try {
				$filepath = $this->getLocalDir() + '/routes.json';
				if (file_exists($filepath)) {
					$routes = @json_decode(file_get_contents($filepath), true);
					if (!$routes) {
						throw new Exceptions\RouteConfigurationException('JSON format error');
					}
				} else {
					throw new Exceptions\EmptyRoutesException('No routes can be found in this configuration');
				}
			} catch (\Exception $e) {
				throw new Exceptions\RouteConfigurationException('Unable to get site routes: ' + $e->getMessage());
			}
		}
	}

	/**
	 * Generate URL.
	 *
	 * This function allows you to generate a URL from the existing set of named
	 * routes along with data to fill it.
	 *
	 * If the route contains generic unnamed groups (e.g. /*) then this can be
	 * filled in using array indexes than named groups or can be left blank.
	 *
	 * If the URL cannot be provided then a 'javascript:;' otherwise known as
	 * 'javascript:void();' will be provided. This is hopefully easier to find
	 * when debugging.
	 *
	 * @var    string    $name     Route name to generate
	 * @var    mixed     $data     Data to fill the URL's parameters
	 * @return string              Generated URL or javascript:; on no match
	 */
	public function generateUrl($name, $data = null) {

		if ($data === null) {
			$data = array();
		}

		$routes = $this->routes;

		foreach ($routes as $item) {
			if (!(isset($item['name']) && $item['name'] === $name)) {
				continue;
			}

			// Start looking at route and try to generate a URL
			$uri = preg_replace_callback('/(\/:([^\/]+)|(\*[^\/]*))/', function($match) use ($data) {
				static $i = 0;

				$name = rtrim($match[2] ?: $match[3], '?');

				$selection = preg_match('/\(([\w\_\d\|]+)\)/', $match[0], $groups) != false;

				if ($selection) {
					$name = str_replace($groups[0], '', $name);
					$selection = explode('|', $groups[1]);
				}

				if ($name === '*') {
					$name = $i++;
				}

				// Allow group expectations (e.g. '/:user(bob|alice)')
				if ($selection && array_key_exists($name, $data)) {
					if ($data[$name] === null) {
						$data[$name] = '';
					}
					if (!in_array($data[$name], $selection)) {
						$last = array_pop($selection);
						throw new Exceptions\RouteConfigurationException(
							'Parameter ' . $name . ' cannot be filled as the ' .
							'available value is restricted to the following: ' .
							(count($selection) > 0 ? implode(', ', $selection) .
							' and ' . $last : $last)
						);
					}
				}


				return '/' . (isset($data[$name]) ? $data[$name] : '');
			}, $item['route']);

			return $this->engine->getRemoteAbsolutePath($uri);
		}

		// None was found, returning no-click
		return 'javascript:;';
	}

	/**
	 * Prepare Routes.
	 *
	 * This function prepares the routes before runtime, this allows us to make
	 * corrections and move naming conventions to actual code (e.g. slugs/params
	 * to regexes).
	 *
	 * @param  array    $routes    Configured Routes
	 * @return array               Prepared Routes
	 */
	protected function prepareRoutes($routes) {
		foreach ($routes as &$item) {
			$route = &$item['route'];
			$old = $route;
			$route = preg_replace('/\/?\*/', '/?(.*)', $route);

			// Create a route filter so that we know what parameters to fill
			preg_match_all('/(?::[\w]+|\.\*)/', $route, $matches);
			$item['filter'] = $matches[0];

			$route = '^' . preg_replace_callback('/(\/:([^\/]+))/', function($match) {
				$optional = '';
				$name = $match[2];
				if ($name[strlen($name) - 1] == '?') {
					$optional = '?';
					$name = substr($name, 0, strlen($name) - 1);
				}
				$pattern = "(?:\/(?<${name}>[^\/]+))";

				// Allow group expectations (e.g. '/:user(bob|alice)')
				if (preg_match('/\(([^\)]+)\)/', $name, $match) != false) {
					$name = str_replace($match[0], '', $name);
					$pattern = "(?:\/(?<${name}>{$match[1]}))";
				}

				return $pattern . $optional;
			}, $route) . '(?:\/|$)';

			$route = '/' . preg_replace('/((?<!=^|\\\)\\/)/', '\/', $route) . '/';
		}

		return $routes;
	}

	/**
	 * Run Site.
	 *
	 * Starts the site by matching routes to controllers.
	 *
	 * @param  string    $url    The current URL
	 * @return void
	 */
	public function run($url) {
		$routes = $this->prepareRoutes($this->routes);

		foreach ($routes as $item) {
			$route = $item['route'];

			$match = false;
			try {
				$match = preg_match($route, $url, $matched);
			} catch (\Exception $e) {
				throw new Exceptions\RouteConfigurationException('Regex match exception', 0, $e);
			}

			if (!$match) {
				continue;
			}

			$handler = $item['handler'];
			try {

				// Get the correct parameters since preg_match returns all of
				// matched values with and without the keys.
				$filters = array_flip($item['filter']);
				$params = array();
				foreach ($filters as $key => $val) {
					$value = (isset($matched[$val + 1]) ? $matched[$val + 1] : null);

					if ($key == '.*') {
						array_push($params, $value);
					} else {
						$params[ltrim($key, ':')] = $value;
					}
				}

				$request = array(
					'path' => $url,
					'params' => $params,
					'query' => $_GET,
					'post' => $_POST,
					'root' => Engine::instance()->remoteRootDir,
					'protocol' => Engine::isSecure() ? 'https' : 'http',
					'name' => (isset($item['name']) ? $item['name'] : null),
					'method' => $_SERVER['REQUEST_METHOD'],
				);

				$handler = &$item['handler'];

				// Shorthand use of new objects w/ ControllerName->MethodName, if
				// you don't want this to be controlled, then use a static function
				// and manually initalise the class yourself in the handler.

				$sep = false;
				$type = 'anonymous';
				if (($pos = strpos($handler, '->')) && $pos !== false) {
					$instance = true;
					$sep = $pos;
					$type = 'instance';
				} else if (($pos = strpos($handler, '::')) && $pos !== false) {
					$sep = $pos;
					$type = 'static';
				} /* else { ... anonymous function ... } */

				if ($type === 'static' || $type === 'instance') {
					$reflection = new \ReflectionClass($this);
					$namespace = $reflection->getNamespaceName();
					$class = substr($handler, 0, $sep);
					$classes = array(
						$class,
						"${namespace}\\${class}",
						"${namespace}\\Controllers\\${class}"
					);
					$found = null;

					foreach ($classes as $test) {
						if (class_exists($test)) {
							$found = $test;
							break;
						}
					}

					if ($found === null) {
						throw new Exceptions\RouteConfigurationException(
							'Route handler class cannot be found'
						);
					}
					$class = $found;
					unset($found, $reflection, $namespace);

					if ($type === 'instance') {
						$class = new $class();
					}
					$handler = array(
						$class,
						substr($handler, $sep + 2)
					);
				}

				if (is_array($handler)) {
					$this->controller = $handler[0];
				} else if (is_callable($handler)) {
					$this->controller = $handler;
				} else {
					throw new \Exceptions\SiteRuntimeException('Invalid handler set for route');
				}

				return \call_user_func_array($handler, array($request));
			} catch (\Exception $e) {
				throw new Exceptions\SiteRuntimeException('An exception occured during route handler', 0, $e);
				return;
			}
		}
		if (empty($routes) || !is_array($routes)) {
			throw new Exceptions\EmptyRoutesException('No routes are available');
		} else {
			throw new Exceptions\SiteNotFoundException('Cannot find any pages with this URL');
		}
	}

	public function render($file, $data = null) {
		return Engine::instance()->render($file, $data);
	}

	public function __get($name) {
		switch ($name) {
			case 'name':
				return static::$name;
			case 'description':
				return static::$description;
			case 'routes':
				return $this->getRoutes();
			case 'localDir':
				return $this->getLocalDir();
			case 'remoteDir':
				return $this->getRemoteDir();
			case 'engine':
				return Engine::instance();
			case 'db':
			case 'database':
				return \LWEngine\Engine::instance()->database;
			case 'controller':
				return $this->controller;
			default: {
				if (isset($this->{$name})) {
					return $this->{$name};
				} else {
					return null;
				}
			}
		}
	}

	public function __isset($name) {
		switch ($name) {
			case 'name':
			case 'description':
			case 'routes':
			case 'localDir':
			case 'remoteDir':
			case 'engine':
			case 'db':
			case 'database':
			case 'controller':
				return true;
			default:
				return false;
		}
	}

	protected final function getLocalDir() {
		static $local;

		if ($local === null) {
			// Returns the local directory for the site
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
}
?>