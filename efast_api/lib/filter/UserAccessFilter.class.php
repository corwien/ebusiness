<?php

class UserAccessFilter implements IRequestFilter
{
	/**
	 * @var app_secret  用来生成数据签名的密码，默认为APP_SALT常量.
	 */
	public $app_secret = APP_SALT;
	/**
	 * @var app_secret_func 设置得到app_secret的函数，仅用于$app ['mode'] == 'func'，即OpenAPI中。
	 * 函数原型：function app_secret_func($app_key) 
	 * 参数$app_key为$app['key']，返回$app_key对应的app_secret。
	 */
	public $app_secret_func;

	public function handle_before(array &$request, array &$response, array &$app)
	{
		if ($app["mode"] == "func") {
			if ($this->app_secret_func && isset($app["key"]) && $app["key"]) {
				try {
					$f = $this->app_secret_func;
					$app_secret = $f($app["key"]);

					if ($app_secret) {
						$this->app_secret = $app_secret;
					}
				}
				catch (Exception $e) {
				}
			}

			$sign_m = (isset($app["sign_method"]) ? $app["sign_method"] : "md5");
			if (!isset($app["key"]) || ($app["sign"] != self::makeSign($request, $app["key"], $sign_m, $this->app_secret))) {
				$GLOBALS["context"]->put_error(401, lang("req_err_401"));
				return true;
			}

			unset($app["sign"]);
			unset($app["sign_method"]);
		}
		else {
			if ($app["grp"] == "login") {
				return NULL;
			}

			if (isset($_COOKIE["app_efid"]) && $_COOKIE["app_efid"]) {
				$efid = &$_COOKIE["app_efid"];
			}
			else if (RUN_SAFE === true) {
				$GLOBALS["context"]->put_error(401, lang("req_err_401"));
				return true;
			}
			else {
				$efid = &$app["efid"];
			}

			$user_id = $role_id = $group_id = $role_id = $sign = $urg = $settime = NULL;

			if (!self::parseId($efid, $user_id, $role_id, $group_id, $settime, $this->app_secret)) {
				$GLOBALS["context"]->put_error(401, lang("req_err_401"));
				return true;
			}

			unset($app["efid"]);
			$app["user_id"] = $user_id;
			$app["role_id"] = $role_id;
			$app["group_id"] = $group_id;
			if ((300 < (time() - $settime)) && ($app["mode"] !== "cli")) {
				$value = self::makeId($user_id, $role_id, $group_id, $this->app_secret);
				$GLOBALS["context"]->set_cookie("app_efid", $value);
			}
		}
	}

	static public function parseId($id, &$user_id, &$role_id, &$group_id, &$settime, $salt = APP_SALT)
	{
		list($sign, $urg) = explode(":", base64_decode(urldecode($id)));
		if (empty($sign) || empty($urg)) {
			return false;
		}

		list($ef, $user_id, $role_id, $group_id, $settime) = explode("|", $urg);
		if (($ef == "eF") && $settime && $user_id && !isset($role_id) && !isset($group_id)) {
			return false;
		}

		return $sign == strtoupper(md5($salt . $urg . $salt));
	}

	static public function makeId($user_id, $role_id, $group_id, $salt = APP_SALT)
	{
		if (!$user_id) {
			$user_id = "0";
		}

		if (!$role_id) {
			$role_id = "0";
		}

		if (!$group_id) {
			$group_id = "0";
		}

		$settime = time();
		$u = "eF|$user_id|$role_id|$group_id|$settime";
		$s = strtoupper(md5($salt . $u . $salt));
		return urlencode(base64_encode($s . ":" . $u));
	}

	static public function makeSign($params, $key, $sign_method = "md5", $salt = APP_SALT)
	{
		$sign = $key;
		ksort($params);

		foreach ($params as $key => $val ) {
			if (($key != "") && isset($val)) {
				if (is_array($val)) {
					if (0 < count($val)) {
						foreach ($val as $item ) {
							$sign .= $key . $item;
						}
					}
				}
				else {
					$sign .= $key . $val;
				}
			}
		}

		if ($sign_method === "hmac") {
			$sign = strtoupper(hash_hmac("md5", $sign, $salt));
		}
		else {
			$sign = strtoupper(md5($salt . $sign . $salt));
		}

		return $sign;
	}
}

require_once (ROOT_PATH . "boot/req_inc.php");

?>
