<?php

class conf_select_option extends Control
{
	public function render($clazz, $id, array $options)
	{
		$conf = $GLOBALS["context"]->conf;
		if (!$conf || !isset($options["var_key"])) {
			return NULL;
		}

		$var_key = $options["var_key"];
		$data = $conf->get_var($var_key);

		if (!$data) {
			return NULL;
		}

		$sel_key = (isset($options["sel_key"]) ? $options["sel_key"] : NULL);
		$null_caption = (isset($options["null_caption"]) ? $options["null_caption"] : NULL);
		echo get_select_option($data, $sel_key, $null_caption);
	}

	public function do_get_data(array &$request, array &$response, array &$app)
	{
		$conf = $GLOBALS["context"]->conf;
		if (!$conf || !isset($request["var_key"])) {
			exit();
		}

		$var_key = $request["var_key"];
		$data = $conf->get_var($var_key);

		if (!$data) {
			exit();
		}

		if ($app["fmt"] == "html") {
			$sel_key = (isset($request["sel_key"]) ? $request["sel_key"] : NULL);
			$null_caption = (isset($request["null_caption"]) ? $request["null_caption"] : NULL);
			echo get_select_option($data, $sel_key, $null_caption);
			exit();
		}
		else {
			$response["data"] = $data;
		}
	}
}

require_once (ROOT_PATH . "lib/ctl/Control.class.php");
require_once (ROOT_PATH . "lib/util/render_util.php");

?>
