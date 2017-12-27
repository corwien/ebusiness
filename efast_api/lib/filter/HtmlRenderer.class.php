<?php

function get_web_page($tpl_file)
{
	$dirpath = dirname($tpl_file);

	if (!$dirpath) {
		return WEB_PAGE_TPL;
	}

	if (DS != "/") {
		$dirpath = str_replace("/", DS, $dirpath);
	}

	if (($dirpath == "/") || ($dirpath == "/")) {
		return $dirpath . WEB_PAGE_TPL;
	}

	if (file_exists($dirpath . DS . WEB_PAGE_TPL)) {
		return $dirpath . DS . WEB_PAGE_TPL;
	}

	$ctx = $GLOBALS["context"];
	$views_path = ROOT_PATH . $ctx->app_name . DS . "views";

	if ($ctx->theme) {
		$views_path .= DS . $ctx->theme;
	}

	if (defined("RUN_MLANG_VIEW") && RUN_MLANG_VIEW) {
		$views_path .= $ctx->app_lang . DS;
	}

	while (($dirpath != DS) && ($dirpath != $views_path)) {
		$pos = strrpos($dirpath, DS);
		$dirpath = substr($dirpath, 0, $pos);

		if (file_exists($dirpath . DS . WEB_PAGE_TPL)) {
			return $dirpath . DS . WEB_PAGE_TPL;
		}
	}
}

require_once (ROOT_PATH . "boot/req_inc.php");

if (!defined("WEB_PAGE_TPL")) {
	define("WEB_PAGE_TPL", "web_page.tpl.php");
}

if (!defined("WEB_PAGE_ERR_TPL")) {
	define("WEB_PAGE_ERR_TPL", "web_page_error.tpl.php");
}

if (!defined("RUN_WIDGET") || !RUN_WIDGET) {
	function render_widget($widget_id)
	{
	}
}

if (!defined("RUN_CONTROL") || !RUN_CONTROL) {
	function render_control($clazz, $id, array $options = array())
	{
	}
}

if (!function_exists("get_tpl_path")) {
	function get_tpl_path($tplname)
	{
		global $context;
		$views_path = ROOT_PATH . $context->app_name . DS . "views" . DS;

		if ($context->theme) {
			$views_path .= $context->theme . DS;
		}

		if (defined("RUN_MLANG_VIEW") && RUN_MLANG_VIEW) {
			$views_path .= $context->app_lang . DS;
		}

		if ("/" !== DS) {
			$tplname = str_replace("/", DS, $tplname);
		}

		return $views_path . $tplname . ".tpl.php";
	}
}
class HtmlRenderer implements IReponseRenderer
{
	private function render_error(array &$app, $view_path, $none_page)
	{
		if ($app["err_no"] == 0) {
			return NULL;
		}

		if ($none_page) {
			include ($view_path . WEB_PAGE_ERR_TPL);
		}
		else {
			echo "<html><head>\n<meta http-equiv='content-Type' content='text/html; charset=" . $GLOBALS["context"]->get_app_conf("charset") . "'/>\n";

			if (isset($app["title"])) {
				echo "<title>" . $app["title"] . "</title></head><body>";
			}

			include ($view_path . WEB_PAGE_ERR_TPL);
			echo "\n</body></html>";
		}

		exit();
	}

	public function render(array &$request, array &$response, array &$app)
	{
		if ($app["fmt"] !== "html") {
			return NULL;
		}

		$context = $GLOBALS["context"];
		$webpage_charset = $context->get_app_conf("charset");
		header("Content-Type: text/html;charset=" . $webpage_charset);
		if (isset($app["ttl"]) && (0 < $app["ttl"])) {
			header("Cache-Control:max-age={$app["ttl"]}");
		}

		$_none_page = isset($app["page"]) && (strcasecmp($app["page"], "NULL") == 0);
		$_views_path = ROOT_PATH . $context->app_name . DS . "views" . DS;

		if ($context->theme) {
			$_views_path .= $context->theme . DS;
		}

		if (defined("RUN_MLANG_VIEW") && RUN_MLANG_VIEW) {
			$_views_path .= $context->app_lang . DS;
		}

		if ($app["err_no"] !== 0) {
			$this->render_error($app, $_views_path, $_none_page);
		}

		if (isset($app["tpl"]) && $app["tpl"]) {
			$main_child_tpl = $app["tpl"];
			if (($main_child_tpl[1] !== ":") && ($main_child_tpl[0] !== "/")) {
				$main_child_tpl = $_views_path . "$main_child_tpl.tpl.php";
			}
		}
		else {
			$main_child_tpl = $_views_path . "$context->app_path{$app["grp"]}_{$app["act"]}.tpl.php";
		}

		if (!file_exists($main_child_tpl)) {
			$app["err_no"] = 20001;
			$app["err_msg"] = lang("req_err_not_found_tpl") . "[" . $main_child_tpl . "]";
			$this->render_error($app, $_views_path, $_none_page);
		}

		if ($_none_page) {
			$_FASTAPP_web_page_file_3D8jKw5L2q = $main_child_tpl;
			unset($main_child_tpl);
			include ($_FASTAPP_web_page_file_3D8jKw5L2q);
			return true;
		}
		else {
			if (!isset($app["page"])) {
				$_web_page_file = get_web_page($main_child_tpl);
			}
			else {
				$_web_page_file = $app["page"];
				if (($_web_page_file[1] !== ":") && ($_web_page_file[0] !== "/")) {
					$_web_page_file = $_views_path . $_web_page_file . ".tpl.php";
				}
			}

			$_FASTAPP_web_page_file_3D8jKw5L2q = $_web_page_file;
			unset($_views_path);
			unset($_none_page);
			unset($_web_page_file);

			if (file_exists($_FASTAPP_web_page_file_3D8jKw5L2q)) {
				include ($_FASTAPP_web_page_file_3D8jKw5L2q);
			}
			else {
				$_FASTAPP_web_page_file_3D8jKw5L2q = $main_child_tpl;
				unset($main_child_tpl);
				echo "<html><head>\n<meta http-equiv='content-Type' content='text/html; charset=" . $GLOBALS["context"]->get_app_conf("charset") . "' />\n";

				if (isset($app["title"])) {
					echo "<title>" . $app["title"] . "</title></head><body>\n";
				}

				include ($_FASTAPP_web_page_file_3D8jKw5L2q);
				echo "\n</body></html>";
			}

			return true;
		}
	}
}


?>
