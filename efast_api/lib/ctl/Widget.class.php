<?php

class Widget
{
	static 	public $wgt_list = array();
	static 	public $opt_mdtime = -1;

	static public function get_border_file($border)
	{
		if (empty($border)) {
			return NULL;
		}

		return get_tpl_path($border);
	}

	static public function get_tpl_file($action)
	{
		$path = $grp = $act = NULL;
		$GLOBALS["context"]->get_path_grp_act($action, $path, $grp, $act);
		return get_tpl_path("$path{$grp}_$act");
	}

	static public function get_app_file($action)
	{
		$path = $grp = $act = NULL;
		$GLOBALS["context"]->get_path_grp_act($action, $path, $grp, $act);
		$file = "{$GLOBALS["context"]->app_name}/web/app/$path$grp.php";

		if (DS == "\\") {
			return ROOT_PATH . str_replace("/", DS, $file);
		}
		else {
			return ROOT_PATH . str_replace("\\", DS, $file);
		}
	}

	static public function add($widget_id, $action, $border, $title, array $request = array())
	{
		self::$wgt_list[$widget_id] = array("id" => $widget_id, "action" => $action, "border" => $border, "title" => $title, "request" => $request);
	}

	static public function render($widget_id)
	{
		if (!isset(self::$wgt_list[$widget_id])) {
			return NULL;
		}

		$wgt = self::$wgt_list[$widget_id];
		$tpl_file = self::get_tpl_file($wgt["action"]);
		if (isset($wgt["border"]) && $wgt["border"]) {
			$border_file = self::get_border_file($wgt["border"]);
		}
		else {
			$border_file = NULL;
		}

		if (isset($GLOBALS["context"]->response["wgt_$widget_id"])) {
			$response = $GLOBALS["context"]->response["wgt_$widget_id"];
		}
		else {
			$response = array();
		}

		$request = $wgt["request"];
		$app = &$GLOBALS["context"]->app;
		$_FASTAPP_TITLE_5Dw8KrLj = $app["title"];
		$app["title"] = $wgt["title"];
		unset($wgt);
		if ($border_file && file_exists($border_file)) {
			$main_child_tpl = $tpl_file;
			include ($border_file);
		}
		else if (file_exists($tpl_file)) {
			include ($tpl_file);
		}

		$app["title"] = $_FASTAPP_TITLE_5Dw8KrLj;
	}

	static public function call($widget_id, $need_include_file = true)
	{
		if (!isset(self::$wgt_list[$widget_id])) {
			return NULL;
		}

		$context = $GLOBALS["context"];
		$response = array();
		$app = &$context->app;
		$wgt = &self::$wgt_list[$widget_id];
		$action = $wgt["action"];
		$request = &$wgt["request"];

		if ($need_include_file) {
			$app_file = self::get_app_file($action);

			if (!file_exists($app_file)) {
				return NULL;
			}

			require_once ($app_file);
		}

		$path = $grp = $act = NULL;
		$context->get_path_grp_act($action, $path, $grp, $act);
		$obj = RequestContext::get_obj_from_grp($grp, $path);

		if ($obj) {
			if (method_exists($obj, $act)) {
				$callback = array($obj, $act);
			}
		}
		else if (function_exists($act)) {
			$callback = $act;
		}
		else {
			$callback = NULL;
		}

		if ($callback) {
			try {
				$title = $app["title"];
				$app["title"] = "";
				$request["widget_id"] = $widget_id;

				if (is_array($callback)) {
					$result = $callback[0]->{$callback[1]}($request, $response, $app);
				}
				else {
					$result = $callback($request, $response, $app);
				}

				$context->response["wgt_$widget_id"] = $response;

				if (!empty($app["title"])) {
					$wgt["title"] = $app["title"];
				}

				$app["title"] = $title;
			}
			catch (Exception $e) {
				$context->log_error("Widget::call fail:" . $e);
			}
		}
	}

	static public function get_response($widget_id, array &$response)
	{
		if (!isset(self::$wgt_list[$widget_id])) {
			$response = array();
			return false;
		}

		$wgt = self::$wgt_list[$widget_id];
		$wgt_id = "wgt_$widget_id";

		if (isset($GLOBALS["context"]->response[$wgt_id])) {
			$response = $GLOBALS["context"]->response[$wgt_id];
			return true;
		}
		else {
			$response = array();
			return false;
		}
	}

	static public function get_request($widget_id, array &$request)
	{
		if (!isset(self::$wgt_list[$widget_id])) {
			$request = array();
			return false;
		}

		$request = self::$wgt_list[$widget_id]["request"];
		return true;
	}

	static public function get_title($widget_id, &$title)
	{
		if (!isset(self::$wgt_list[$widget_id])) {
			$title = "";
			return false;
		}

		$title = self::$wgt_list[$widget_id]["title"];
		return true;
	}
}

function render_widget($widget_id)
{
	Widget::render($widget_id);
}


?>
