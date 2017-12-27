<?php

class FileWidgetAddFilter implements IRequestFilter
{
	public function handle_before(array &$request, array &$response, array &$app)
	{
		$context = $GLOBALS["context"];
		$path = $grp = $act = NULL;

		if (isset($context->app["path"])) {
			$path = $context->app["path"];
		}

		if (isset($context->app["grp"])) {
			$grp = $context->app["grp"];
		}

		if (isset($context->app["act"])) {
			$act = $context->app["act"];
		}

		$opt_file = ROOT_PATH . $context->app_name . DS . " views" . DS . $context->theme . DS;
		if (defined("RUN_MLANG_VIEW") && RUN_MLANG_VIEW) {
			$opt_file .= $context->app_lang . DS;
		}

		$opt_file .= "$path{$grp}_$act.opt.php";

		if (!file_exists($opt_file)) {
			return NULL;
		}

		Widget::$opt_mdtime = filemtime($opt_file);
		include ($opt_file);

		foreach ($options as $widget_id => $opt ) {
			Widget::add($widget_id, $opt["action"], $opt["border"], $opt["title"], $opt["request"]);
		}
	}
}


?>
