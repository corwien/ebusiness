<?php

class Log_api implements ILog
{
	const ERROR = 1;
	const WARN = 2;
	const INFO = 3;
	const DEBUG = 4;

	public $threshold = self::ERROR;
	public $date_fmt = "Y-m-d H:i:s";
	public $is_split = true;
	public $level_hints = array("self::ERROR\000\010" => NULL, "self::WARN\000\010" => NULL, "self::DEBUG\000\010" => NULL, "self::INFO\000\010" => NULL);
	public $log_path;
	private $enabled = false;

	public function error($msg)
	{
		$this->write_log($msg);
	}

	public function warn($msg)
	{
		$this->write_log($msg, self::WARN);
	}

	public function debug($msg)
	{
		$this->write_log($msg, self::DEBUG);
	}

	public function info($msg)
	{
		$this->write_log($msg, self::INFO);
	}

	public function __construct($log_path)
	{
		$this->setLogPath($log_path);
	}

	public function setLogPath($log_path)
	{
		$this->enabled = $log_path && is_dir($log_path);
		$this->log_path = $log_path;
	}

	private function write_log($msg, $level = self::ERROR)
	{
		if ($this->enabled === false) {
			return NULL;
		}

		if ((self::ERROR < $level) && ($this->threshold < $level)) {
			return NULL;
		}

		$level = ($level < self::ERROR ? self::ERROR : self::INFO < $level ? self::INFO : $level);
		$filepath = $this->log_path . "error_";

		if ($this->is_split) {
			$filepath .= date("Y-m-d") . ".";
		}

		$filepath .= "log";
		$message = "[" . date($this->date_fmt) . "]\t[" . lang($this->level_hints[$level]) . "]\t$msg\n";
		file_put_contents($filepath, $message, FILE_APPEND);
	}

	static public function register($prop)
	{
		$app_log_path = $GLOBALS["context"]->get_app_conf("log_path");
		$app_log_split = $GLOBALS["context"]->get_app_conf("log_split");

		if (!$app_log_path) {
			$app_log_path = ROOT_PATH . "logs" . DS;

			if (!file_exists($app_log_path)) {
				mkdir($app_log_path);
			}
		}

		if (!isset($app_log_split)) {
			$app_log_split = true;
		}

		$log = new Log_api($app_log_path);

		if (DEBUG) {
			$log->threshold = Log_api::DEBUG;
		}
		else {
			$log->threshold = Log_api::ERROR;
		}

		$log->is_split = $app_log_split;
		$log->debug("Log object create");
		return $log;
	}
}

require_once (ROOT_PATH . "boot/req_inc.php");

?>
