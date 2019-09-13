<?php

require_once(__DIR__ . '/vendor/autoload.php');

class ErrorHandler {

	private static $enabled = false;

	private static $handler = __CLASS__ . '::defaultHandler';
	private static $shutdownRegistered = false;

	private static $errorHTML = "
		<html>
			<head>
				<title>{errorName}</title>
				<style>
					html{width:100%;height:100%;}
					body{font-family: Arial, Helvetica, sans-serif; text-align: center;}
					div {display: table; width: 100%; height: 100%}
					div div {display: table-cell; vertical-align: middle;}
					h1, h2, h3, h4 {margin: 0; font-weight: 100;}
					pre {min-width: 700px; display: inline-block; white-space: pre-wrap; max-height: 25em; overflow: scroll}
				</style>
			</head>
			<body>
				<div>
					<div>
						<h1>Error {errorCode}: {errorName}</h1>
						<pre style='text-align: {errorAlignment}'>{errorDescription}</pre>
					</div>
				</div>
			</body>
		</html>
	";

	public static function setHandler($obj) {
		if ($obj === null) {
			$obj = __CLASS__ . '::defaultHandler';
		}
		static::$handler = $obj;
		static::stop();
		static::start();
	}

	public static function setErrorHTML($code) {
		self::$errorHTML = $code;
	}

	public static function defaultHandler($error) {
		$str = str_replace("{errorCode}", $error['code'], self::$errorHTML);
		$str = str_replace("{errorName}", $error['name'], $str);
		$str = str_replace("{errorDescription}", $error['description'], $str);
		if (stripos($error['description'], 'stack trace') !== false) {
			$alignment = 'left';
		} else {
			$alignment = 'center';
		}
		$str = str_replace('{errorAlignment}', $alignment, $str);
		die($str);
	}

	public static function primitiveError(
		$errorCode = 500,
		$errorName = 'Internal Server Error',
		$errorDescription = null
	) {
		if (ob_get_status()) {
			ob_end_clean();
		}
		call_user_func_array(static::$handler, array(
			array(
				'code' => $errorCode,
				'name' => $errorName,
				'description' => $errorDescription
			)
		));
	}

	public static function exceptionHandler($num, $msg, $file, $line) {
		$errorCase = array(
			E_WARNING => "Warning!",
			E_ERROR => "Error!",
			E_USER_ERROR => "User Error",
			E_USER_WARNING => "User Warning",
			E_USER_NOTICE => "User Notice",
		);
		$header = (isset($errorCase[$num]) ? $errorCase[$num] : "An unknown error occurred");
		self::primitiveError(500, $header, $msg . "Line ${line} in ${file}", 'left');
	}

	public static function fatalHandler() {
		$error = error_get_last();
		if (self::$enabled && $error !== null) {
			$errorMsg = $error['message'] . ' on line ' . $error['line'] . " (" . $error['file'] . ")";
			self::primitiveError(500, "Fatal error occurred", $errorMsg, 'left');
		}
	}

	public static function start() {
		error_reporting(0);
		self::$enabled = true;
		set_error_handler(__CLASS__ . '::exceptionHandler', E_ERROR);
		if (!static::$shutdownRegistered) {
			register_shutdown_function(__CLASS__ . '::fatalHandler');
			static::$shutdownRegistered = true;
		}
	}

	public static function stop() {
		self::$enabled = false;
		error_reporting(E_ALL);
		restore_error_handler();
	}
}

ErrorHandler::start();

(@include_once "engine/Engine.php") or ErrorHandler::primitiveError(500, "Missing Engine class.");
LWEngine\Engine::instance()->run();
