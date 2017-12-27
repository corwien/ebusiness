<?php

class UserDebugFilter implements IRequestFilter
{
	public function handle_before(array &$request, array &$response, array &$app)
	{
		if (defined("DEBUG") && DEBUG) {
			return NULL;
		}

		if (!isset($app["user_debug"]) || !isset($app["user_id"])) {
			return NULL;
		}

		if (RequestContext::is_in_cli()) {
			if ($app["user_debug"] !== APP_SALT) {
				return NULL;
			}
		}
		else {
			if ((RUN_SAFE === true) && ($app["mode"] != "json") && !isset($_COOKIE["app_user_debug"])) {
				return NULL;
			}

			if ($app["user_debug"] !== self::digist($app["user_id"])) {
				return NULL;
			}
		}

		$GLOBALS["app_debug"] = true;
		$log = $GLOBALS["context"]->log;
		$log->log_path .= $app["user_id"];
		$log->threshold = Log::DEBUG;
	}

	static public function digist($user_id)
	{
		return md5(APP_SALT . $user_id . self::APP_SALT);
	}
}

require_once (ROOT_PATH . "boot/req_inc.php");

?>
