<?php

function del_cookie($name, $pub = false)
{
	unset($_COOKIE[$name]);
	$GLOBALS["context"]->set_cookie($name, "", -42000, $pub);
}

function clean_session($name = NULL, $pub = false)
{
	if (isset($this->app["mode"]) && ($this->app["mode"] === "func")) {
		$this->sess_init = false;
		return NULL;
	}

	if ($name) {
		$GLOBALS["context"]->init_session();

		if (!$pub) {
			$name = "fAp" . $GLOBALS["context"]->app_name . $name;
		}

		unset($_SESSION[$name]);
	}
	else {
		session_start();
		$_SESSION = array();
		$params = session_get_cookie_params();
		setcookie(session_name(), "", time() - 42000, $params["path"], $params["domain"]);
		session_destroy();
	}
}

function get_client_ip()
{
	if (getenv("HTTP_CLIENT_IP") && strcasecmp(getenv("HTTP_CLIENT_IP"), "unknown")) {
		return getenv("HTTP_CLIENT_IP");
	}
	else {
		if (getenv("HTTP_X_FORWARDED_FOR") && strcasecmp(getenv("HTTP_X_FORWARDED_FOR"), "unknown")) {
			return getenv("HTTP_X_FORWARDED_FOR");
		}
		else {
			if (getenv("REMOTE_ADDR") && strcasecmp(getenv("REMOTE_ADDR"), "unknown")) {
				return getenv("REMOTE_ADDR");
			}
			else {
				if (isset($_SERVER["REMOTE_ADDR"]) && $_SERVER["REMOTE_ADDR"] && strcasecmp($_SERVER["REMOTE_ADDR"], "unknown")) {
					return $_SERVER["REMOTE_ADDR"];
				}
				else {
					return NULL;
				}
			}
		}
	}
}

function set_http_status($code)
{
	static $_http_status = array(100 => "Continue", 101 => "Switching Protocols", 200 => "OK", 201 => "Created", 202 => "Accepted", 203 => "Non-Authoritative Information", 204 => "No Content", 205 => "Reset Content", 206 => "Partial Content", 300 => "Multiple Choices", 301 => "Moved Permanently", 302 => "Moved Temporarily ", 303 => "See Other", 304 => "Not Modified", 305 => "Use Proxy", 307 => "Temporary Redirect", 400 => "Bad Request", 401 => "Unauthorized", 402 => "Payment Required", 403 => "Forbidden", 404 => "Not Found", 405 => "Method Not Allowed", 406 => "Not Acceptable", 407 => "Proxy Authentication Required", 408 => "Request Timeout", 409 => "Conflict", 410 => "Gone", 411 => "Length Required", 412 => "Precondition Failed", 413 => "Request Entity Too Large", 414 => "Request-URI Too Long", 415 => "Unsupported Media Type", 416 => "Requested Range Not Satisfiable", 417 => "Expectation Failed", 500 => "Internal Server Error", 501 => "Not Implemented", 502 => "Bad Gateway", 503 => "Service Unavailable", 504 => "Gateway Timeout", 505 => "HTTP Version Not Supported", 509 => "Bandwidth Limit Exceeded");

	if (array_key_exists($code, $_http_status)) {
		header("HTTP/1.1 $code $_http_status[$code]");
	}
}

function get_app_url_path($action, $is_control = false)
{
	$ctx = $GLOBALS["context"];

	if ($is_control) {
		if ($ctx->from_index) {
			$loc = $ctx->get_app_conf("base_url");
		}
		else {
			$loc = $ctx->get_app_conf("base_url") . "app/ctl/";
		}
	}
	else if ($ctx->from_index) {
		$loc = $ctx->get_app_conf("base_url");
	}
	else {
		$path = $grp = $act = NULL;
		$ctx->get_path_grp_act($action, $path, $grp, $act);

		if (!$grp) {
			$grp = $ctx->app_script;
		}

		if (!$path) {
			$path = $ctx->app_path;
		}

		$loc = $ctx->get_app_conf("base_url") . "app/$path$grp." . $ctx->get_app_conf("php_ext");
	}

	return $loc;
}

function get_app_url_query($action, $is_control = false)
{
	$ctx = $GLOBALS["context"];

	if ($is_control) {
		if (!$action) {
			$action = $ctx->app["ctl"];
		}

		if (!$action) {
			$action = "do_index";
		}

		if ($ctx->from_index) {
			$result = array("app_act" => "ctl/index/do_index", "app_ctl" => $action);
		}
		else {
			$result = array("app_ctl" => $action);
		}
	}
	else {
		if (!$action) {
			$action = $ctx->app["path"] . $ctx->app["grp"] . "/do_index";
		}

		$result = array("app_act", $action);
	}

	return $result;
}


?>
