<?php

class Smtp_mail
{
	public $connection;
	public $recipients;
	public $headers;
	public $timeout;
	public $errors;
	public $status;
	public $body;
	public $from;
	public $host;
	public $port;
	public $helo;
	public $auth;
	public $user;
	public $pass;

	public function __construct($params = array())
	{
		if (!defined("CRLF")) {
			define("CRLF", "\r\n", true);
		}

		$this->timeout = 10;
		$this->status = SMTP_STATUS_NOT_CONNECTED;
		$this->host = "localhost";
		$this->port = 25;
		$this->auth = false;
		$this->user = "";
		$this->pass = "";
		$this->errors = array();

		foreach ($params as $key => $value ) {
			$this->$key = $value;
		}

		$this->helo = $this->host;
		$this->auth = ("" == $this->user ? false : true);
	}

	public function connect($params = array())
	{
		if (!isset($this->status)) {
			$obj = new smtp($params);

			if ($obj->connect()) {
				$obj->status = SMTP_STATUS_CONNECTED;
			}

			return $obj;
		}
		else {
			if (!empty($GLOBALS["_CFG"]["smtp_ssl"])) {
				$this->host = "ssl://" . $this->host;
			}

			$this->connection = @fsockopen($this->host, $this->port, $errno, $errstr, $this->timeout);

			if ($this->connection === false) {
				$this->errors[] = "Access is denied.";
				return false;
			}

			@socket_set_timeout($this->connection, 0, 250000);
			$greeting = $this->get_data();

			if (is_resource($this->connection)) {
				$this->status = 2;
				return $this->auth ? $this->ehlo() : $this->helo();
			}
			else {
				$this->errors[] = "Failed to connect to server: " . $errstr;
				return false;
			}
		}
	}

	public function send($params = array())
	{
		foreach ($params as $key => $value ) {
			$this->$key = $value;
		}

		if ($this->is_connected()) {
			if ($this->auth) {
				if (!$this->auth()) {
					return false;
				}
			}

			$this->mail($this->from);

			if (is_array($this->recipients)) {
				foreach ($this->recipients as $value ) {
					$this->rcpt($value);
				}
			}
			else {
				$this->rcpt($this->recipients);
			}

			if (!$this->data()) {
				return false;
			}

			$headers = str_replace(CRLF . ".", CRLF . "..", trim(implode(CRLF, $this->headers)));
			$body = str_replace(CRLF . ".", CRLF . "..", $this->body);
			$body = (substr($body, 0, 1) == "." ? "." . $body : $body);
			$this->send_data($headers);
			$this->send_data("");
			$this->send_data($body);
			$this->send_data(".");
			return substr($this->get_data(), 0, 3) === "250";
		}
		else {
			$this->errors[] = "Not connected!";
			return false;
		}
	}

	public function helo()
	{
		if (is_resource($this->connection) && $this->send_data("HELO " . $this->helo) && (substr($error = $this->get_data(), 0, 3) === "250")) {
			return true;
		}
		else {
			$this->errors[] = "HELO command failed, output: " . trim(substr($error, 3));
			return false;
		}
	}

	public function ehlo()
	{
		if (is_resource($this->connection) && $this->send_data("EHLO " . $this->helo) && (substr($error = $this->get_data(), 0, 3) === "250")) {
			return true;
		}
		else {
			$this->errors[] = "EHLO command failed, output: " . trim(substr($error, 3));
			return false;
		}
	}

	public function auth()
	{
		if (is_resource($this->connection) && $this->send_data("AUTH LOGIN") && (substr($error = $this->get_data(), 0, 3) === "334") && $this->send_data(base64_encode($this->user)) && (substr($error = $this->get_data(), 0, 3) === "334") && $this->send_data(base64_encode($this->pass)) && (substr($error = $this->get_data(), 0, 3) === "235")) {
			return true;
		}
		else {
			$this->errors[] = "AUTH command failed: " . trim(substr($error, 3));
			return false;
		}
	}

	public function mail($from)
	{
		if ($this->is_connected() && $this->send_data("MAIL FROM:<" . $from . ">") && (substr($this->get_data(), 0, 2) === "250")) {
			return true;
		}
		else {
			return false;
		}
	}

	public function rcpt($to)
	{
		if ($this->is_connected() && $this->send_data("RCPT TO:<" . $to . ">") && (substr($error = $this->get_data(), 0, 2) === "25")) {
			return true;
		}
		else {
			$this->errors[] = trim(substr($error, 3));
			return false;
		}
	}

	public function data()
	{
		if ($this->is_connected() && $this->send_data("DATA") && (substr($error = $this->get_data(), 0, 3) === "354")) {
			return true;
		}
		else {
			$this->errors[] = trim(substr($error, 3));
			return false;
		}
	}

	public function is_connected()
	{
		return is_resource($this->connection) && ($this->status === SMTP_STATUS_CONNECTED);
	}

	public function send_data($data)
	{
		if (is_resource($this->connection)) {
			return fwrite($this->connection, $data . CRLF, strlen($data) + 2);
		}
		else {
			return false;
		}
	}

	public function get_data()
	{
		$return = "";
		$line = "";

		if (is_resource($this->connection)) {
			while ((strpos($return, CRLF) === false) || ($line[3] !== " ")) {
				$line = fgets($this->connection, 512);
				$return .= $line;
			}

			return trim($return);
		}
		else {
			return "";
		}
	}

	public function error_msg()
	{
		if (!empty($this->errors)) {
			$len = count($this->errors) - 1;
			return $this->errors[$len];
		}
		else {
			return "";
		}
	}
}

function send_mail($to_name, $to_mail, $subject, $msg, $conf, $html = false, $notification = false)
{
	$app_charset = $GLOBALS["context"]->get_app_conf("charset");
	$charset = (isset($conf["smtp_charset"]) ? $conf["smtp_charset"] : $app_charset);
	$use_mail = (isset($conf["smtp_use_mail"]) ? $conf["smtp_use_mail"] : false);
	$from_name = (isset($conf["smtp_from_name"]) ? $conf["smtp_from_name"] : NULL);
	$from_mail = (isset($conf["smtp_from_mail"]) ? $conf["smtp_from_mail"] : NULL);
	$smtp_host = (isset($conf["smtp_host"]) ? $conf["smtp_host"] : NULL);
	$smtp_port = (isset($conf["smtp_port"]) ? $conf["smtp_port"] : 25);
	$smtp_user = (isset($conf["smtp_user"]) ? $conf["smtp_user"] : NULL);
	$smtp_pass = (isset($conf["smtp_pass"]) ? $conf["smtp_pass"] : NULL);

	if ($app_charset !== $charset) {
		$to_name = iconv($app_charset, $charset, $to_name);
		$subject = iconv($app_charset, $charset, $subject);
		$msg = iconv($app_charset, $charset, $msg);
		$from_name = iconv($app_charset, $charset, $from_name);
	}

	if ($use_mail && function_exists("mail")) {
		$content_type = ($html ? "Content-Type: text/html; charset=$charset" : "Content-Type: text/plain; charset=$charset");
		$headers = array();
		$headers[] = "From: \"=?" . $charset . "?B?" . base64_encode($from_name) . "?=\" <" . $from_mail . ">";
		$headers[] = $content_type . "; format=flowed";

		if ($notification) {
			$headers[] = "Disposition-Notification-To: \"=?$charset?B?{base64_encode($from_name)}?=\"<$from_mail>";
		}

		return @mail($to_mail, "=?" . $charset . "?B?" . base64_encode($subject) . "?=", $msg, implode("\r\n", $headers));
	}
	else {
		if (!function_exists("fsockopen")) {
			return false;
		}

		$content_type = ($html ? "Content-Type: text/html; charset=$charset" : "Content-Type: text/plain; charset=$charset");
		$msg = base64_encode($msg);
		$headers = array();
		$headers[] = "Date: " . gmdate("D, j M Y H:i:s") . " +0000";
		$headers[] = "To: \"=?" . $charset . "?B?" . base64_encode($to_name) . "?=\" <" . $to_mail . ">";
		$headers[] = "From: \"=?" . $charset . "?B?" . base64_encode($from_name) . "?=\" <" . $from_mail . ">";
		$headers[] = "Subject: =?" . $charset . "?B?" . base64_encode($subject) . "?=";
		$headers[] = $content_type . "; format=flowed";
		$headers[] = "Content-Transfer-Encoding: base64";
		$headers[] = "Content-Disposition: inline";

		if ($notification) {
			$headers[] = "Disposition-Notification-To: \"=?$charset?B?{base64_encode($from_name)}?=\"<$from_mail>";
		}

		require_lang("net", false);
		if (empty($smtp_host) || empty($smtp_port)) {
			$GLOBALS["context"]->log_error(lang("mail_option_invalid"));
			return lang("mail_option_invalid");
		}

		$params["recipients"] = $to_mail;
		$params["headers"] = $headers;
		$params["from"] = $from_mail;
		$params["body"] = $msg;
		$smtp = new Smtp_mail(array("host" => $smtp_host, "port" => $smtp_port, "user" => $smtp_user, "pass" => $smtp_pass));
		if ($smtp->connect() && $smtp->send($params)) {
			return true;
		}

		$err_msg = $smtp->error_msg();

		if (empty($err_msg)) {
			$err = lang("mail_unknown_err");
		}
		else if (strpos($err_msg, "Failed to connect to server") !== false) {
			$err = sprintf(lang("mail_connnect_fail"), $smtp_host . ":" . $smtp_port);
		}
		else if (strpos($err_msg, "AUTH command failed") !== false) {
			$err = lang("mail_auth_fail");
		}
		else if (strpos($err_msg, "bad sequence of commands") !== false) {
			$err = lang("mail_send_fail");
		}
		else {
			$err = $err_msg;
		}

		$GLOBALS["context"]->log_error($err);
		return $err;
	}
}

define("SMTP_STATUS_NOT_CONNECTED", 1, true);
define("SMTP_STATUS_CONNECTED", 2, true);

?>
