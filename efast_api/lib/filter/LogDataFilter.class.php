<?php

class LogDataFilter implements IRequestFilter
{
	public function handle_before(array &$request, array &$response, array &$app)
	{
		if (!$GLOBALS["app_debug"]) {
			return NULL;
		}

		$str = print_r($request, true);
		$GLOBALS["context"]->log_debug("request data: $str");
		$str = print_r($app, true);
		$GLOBALS["context"]->log_debug("app data:$app");
	}

	public function handle_after(array &$request, array &$response, array &$app)
	{
		if (!$GLOBALS["app_debug"]) {
			return NULL;
		}

		$str = print_r($response, true);
		$GLOBALS["context"]->log_debug("response data:$response");
		$str = print_r($app, true);
		$GLOBALS["context"]->log_debug("app data:$app");
	}
}

require_once (ROOT_PATH . "boot/req_inc.php");

?>
