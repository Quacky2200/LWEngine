<?php
$time = microtime();
$time = explode(' ', $time);
$time = $time[1] + $time[0];
$start = $time;
class ErrorHandler{
	private static $errorHTML = "
		<html>
			<head>
				<title>{errorName}</title>
				<style>
					html{display: table;width:100%;height:100%;}
					body{font-family: Arial, Helvetica, sans-serif; text-align: center;display: table-cell;vertical-align: middle;}
				</style>
			</head>
			<body>
				<h1>Error {errorCode}: {errorName}</h1>
				<h4>{errorDescription}</h4>
			</body>
		</html>
	";
	public static function setErrorHTML($code){
		self::$errorHTML = $code;
	}
	public static function primitiveError($errorCode, $errorName, $errorDescription){
		$errorCodeReplace = str_replace("{errorCode}", $errorCode, self::$errorHTML);
		$errorNameReplace = str_replace("{errorName}", $errorName, $errorCodeReplace);
		$errorDescriptionReplace = str_replace("{errorDescription}", $errorDescription, $errorNameReplace);
		die($errorDescriptionReplace);
	}
	public static function exceptionHandler($errno, $errstr, $errfile, $errline){
		ob_clean();
		$errorCase = array(E_WARNING=>"Warning!", E_ERROR=>"Error!", E_USER_ERROR=>"User Error", E_USER_WARNING=>"User Warning", E_USER_NOTICE=>"User Notice");
		self::primitiveError(500, (array_key_exists($errno, $errorCase) ? $errorCase[$errno] : "An unknown error occurred"), $errstr . "<br/>Line " . $errline . " in " . $errfile);
	}
	public static function fatalHandler(){
		$error = error_get_last();
		if(self::$Enabled && $error !== null){
			$errorMsg = $error['message'] . ' on line ' . $error['line'] . "<br/>(" . $error['file'] . ")";
			self::primitiveError(500, "Fatal error occurred", $errorMsg);
		}
	}
	private static $Enabled = false;
	public static function start(){
		error_reporting(0);
		self::$Enabled = true;
		set_error_handler(__CLASS__ . '::exceptionHandler', E_ALL);
		register_shutdown_function(__CLASS__ . '::fatalHandler');
	}
	public static function stop(){
		self::$Enabled = false;
		error_reporting(E_ALL);
		restore_error_handler();
		register_shutdown_function("exit");
	}
}
ErrorHandler::start();

(@include_once "engine/Engine.php") or ErrorHandler::primitiveError(500, "Missing Engine class.");
$engine = new Engine();
//TODO: Remove debugging footer when project is complete
$time = microtime();
$time = explode(' ', $time);
$time = $time[1] + $time[0];
$finish = $time;
$total_time = round(($finish - $start), 4);
function convert($size)
{
    $unit=array('b','kb','mb','gb','tb','pb');
    return @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i];
}
?>
<div class="footer">Finished in <?php echo $total_time;?> seconds using <?php echo convert(memory_get_usage(false));?> w/ <?php echo PHP_VERSION;?></div>
