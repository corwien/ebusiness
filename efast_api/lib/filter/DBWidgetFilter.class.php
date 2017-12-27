<?php

class DBWidgetAddFilter implements IRequestFilter
{
	private $table = "req_widget_opt";
	public $request_is_json = true;

	private function get_data(RequestContext $context, $action, $key)
	{
		if (isset($context->cache)) {
			$result = $context->cache->get($key);
		}

		if ($result === NULL) {
			try {
				$wgt_opt = $context->db->create_mapper($this->table);
				$wgt_opt->cols("widget_id,widget_action,border,title,request,mdtime")->where_col("app_action", $action)->where_col("state", 0, ">");
				$result = @$wgt_opt->find_all_by();

				if (isset($context->cache)) {
					if (!$result) {
						$result = "NULL";
					}

					$context->cache->set($key, $result);
				}
			}
			catch (DBException $e) {
				$context->log_error("DBWidgetAddFilter db error:" . $e->getMessage());
				$result = NULL;
			}
		}

		if ($result === "NULL") {
			$result = NULL;
		}

		return $result;
	}

	public function handle_before(array &$request, array &$response, array &$app)
	{
		$context = $GLOBALS["context"];
		$key = $this->table . "/opt_" . str_replace(array("/", "\\"), "_", $context->action);
		$options = $this->get_data($context, $context->action, $key);
		$time_offset = date_create()->getOffset();
		Widget::$opt_mdtime = 0;
		if ($options && (0 < count($options))) {
			foreach ($options as $opt ) {
				$imdtime = strtotime($opt["mdtime"]) + $time_offset + 60;

				if (Widget::$opt_mdtime < $imdtime) {
					Widget::$opt_mdtime = $imdtime;
				}

				if ($this->request_is_json) {
					$setting = json_decode($opt["request"], true);
				}
				else {
					$setting = eval ("return {$opt["request"]};");
				}

				Widget::add($opt["widget_id"], $opt["widget_action"], $opt["border"], $opt["title"], $setting);
			}
		}
	}
}

class CacheWidgetCallFilter implements IReponseFilter
{
	public $force = false;
	private $app_list = array();

	public function handle_after(array &$request, array &$response, array &$app)
	{
		$context = $GLOBALS["context"];
		$cache_dir = ROOT_PATH . $context->app_name . DS . "cache" . DS . "app" . DS;

		if (count(Widget::$wgt_list) <= 0) {
			$cache_file = $cache_dir . $context->action . ".wgt.php";

			if (file_exists($cache_file)) {
				unlink($cache_file);
				Widget::$opt_mdtime = time();
			}

			return NULL;
		}

		$path = dirname($context->action);

		if (DS == "\\") {
			$path = str_replace("/", DS, $path);
		}

		$path = $cache_dir . $path;

		if (!file_exists($path)) {
			mkdir($path, 511, true);
		}

		$path .= DS . basename($context->action) . ".wgt";
		$cache_file = $path . ".php";
		$cache_dep = $path . ".dep";
		if (!$this->force && $this->check_cache_valid($cache_file, $cache_dep)) {
			self::call($cache_file);
			return NULL;
		}

		if (file_exists($cache_file)) {
			unlink($cache_file);
		}

		foreach ($this->app_list as $app_file ) {
			file_put_contents($cache_file, trim(file_get_contents($app_file)), FILE_APPEND);
		}

		file_put_contents($cache_dep, implode(";", $this->app_list));
		Widget::$opt_mdtime = filemtime($cache_file);
		self::call($cache_file);
	}

	private function call($cache_file)
	{
		include_once ($cache_file);

		foreach (array_keys(Widget::$wgt_list) as $widget_id ) {
			Widget::call($widget_id, false);
		}
	}

	private function check_cache_valid($cache_file, $cache_dep)
	{
		$result = true;

		if (file_exists($cache_file)) {
			$cache_time = filemtime($cache_file);
		}
		else {
			$result = false;
			$cache_time = -100;
		}

		if ($cache_time < Widget::$opt_mdtime) {
			$result = false;
		}

		foreach (Widget::$wgt_list as $widget_id => $wgt ) {
			$action = $wgt["action"];
			$app_file = Widget::get_app_file($action);

			if (!file_exists($app_file)) {
				continue;
			}

			if ($result) {
				$im = filemtime($app_file);
				if ($im && ($cache_time <= $im)) {
					$result = false;
				}
			}

			$this->app_list[] = $app_file;
		}

		$this->app_list = array_unique($this->app_list);

		if ($result) {
			if (file_exists($cache_dep)) {
				$list = explode(";", file_get_contents($cache_dep));
				if ((count($this->app_list) != count($list)) || array_diff($this->app_list, $list)) {
					$result = false;
				}
			}
			else {
				$result = false;
			}
		}

		return $result;
	}
}

class WidgetCallFilter implements IReponseFilter
{
	public function handle_after(array &$request, array &$response, array &$app)
	{
		if (count(Widget::$wgt_list) <= 0) {
			return NULL;
		}

		foreach (array_keys(Widget::$wgt_list) as $widget_id ) {
			Widget::call($widget_id);
		}
	}
}

require_once (ROOT_PATH . "boot/req_inc.php");
require_once (ROOT_PATH . "lib/ctl/Widget.class.php");

?>
