<?php

/**
* Logging class
*/
class LogPagSeguro{
	
	private static $log;
	private static $active;
	private static $fileLocation;
	
	private function __construct() {
		self::$active = PagSeguroConfig::logIsActive();
		if (self::$active) {
			$fileLocation = PagSeguroConfig::getLogFileLocation();
			if (file_exists($fileLocation) && is_file($fileLocation)) {
				self::$fileLocation = $fileLocation;
			} else {
				self::createFile();
			}
		}
	}
	
	public static function init() {
		if (self::$log == null) {
			self::$log = new LogPagSeguro();
		}
		return self::$log;
	}
	
	/**
	 * Creates the log file
	 * @throws Exception
	 * @return boolean
	 */
	public static function createFile() {
		if (!self::$active) { return false; }
		$defaultPath   = PagSeguroLibrary::getPath();
		$defaultName   = 'PagSeguro.log';
		self::$fileLocation = $defaultPath.DIRECTORY_SEPARATOR.$defaultName;
		if ($f = @fopen(self::$fileLocation, "a")) {
			fclose($f);
		} else {
			throw new Exception("Can't create log file. Permission denied. File location: ".self::$fileLocation);				
		}		
	}
	
	/**
	 * Prints a info message in the log file
	 * @param String $message
	 */
	public static function info($message){
		self::logMessage($message, 'info');
	}
	
	/**
	 * Prints a warning message in the log file
	 * @param String $message
	 */
	public static function warning($message){
		self::logMessage($message, 'warning');
	}
	
	/**
	 * Prints an error message in the log file
	 * @param String $message
	 */
	public static function error($message){
		self::logMessage($message, 'error');
	}
	
	/**
	 * Prints a debug messa gein the log file
	 * @param String $message
	 */
	public static function debug($message){
		self::logMessage($message, 'debug');
	}
	
	/**
	 * Logs a message
	 * @param String $message
	 * @param String $type
	 * @throws Exception
	 */
	private static function logMessage($message, $type = null) {
		if (!self::$active) { return false; }
		if ($file = @fopen(self::$fileLocation, "a") ) {
			$date_message = "{" . @date("Y/m/d H:i:s", time()) . "}";
			$type_message = "";
			switch ($type) {
				case 'info' : 		$type_message = "[Info]"; 		break;
				case 'warning' : 	$type_message = "[Warning]"; 	break;
				case 'error' : 		$type_message = "[Error]"; 		break;
				case 'debug' :
				default: 			$type_message = "[Debug]"; 		break;
			}
			$str = "$date_message $type_message $message";
			fwrite($file, "$str \r\n");
			fclose($file);
		} else {
			throw new Exception("Can't create log file. Permission denied. File location: ".self::$fileLocation);				
		}
	}
	
	/**
	 * Retrieves the log messages
	 * @param integer $negativeOffset
	 * @param boolean $reverse
	 */
	public static function getHtml($negativeOffset = null, $reverse = null) {
		if (!self::$active) { return false; }
		if (file_exists(self::$fileLocation) && $file = file(self::$fileLocation)) {
			if ($negativeOffset !== null) {
				$file = array_slice($file, (-$negativeOffset), null, true);
			}
			if ($reverse) {
				$file = array_reverse($file, true);
			}
			$content  = "";
			foreach($file as $key => $value){
				$html  = ("<p>".str_replace("\n", "<br>", $value)."</p>");
				$html  = str_replace("[", "<strong>", $html);
				$html  = str_replace("]", "</strong>", $html);
				$html  = str_replace("{", "<span>", $html);
				$html  = str_replace("}", "</span>", $html);
				$content .= $html;
			}
		}
		return isset($content) ? $content : false;
	}
	
}

?>