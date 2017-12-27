<?php

function render_control($clazz, $id, array $options = array())
{
	$control = ControlFilter::new_control($clazz);

	try {
		if ($control) {
			$control->render($clazz, $id, $options);
		}
	}
	catch (Exception $e) {
		$GLOBALS["context"]->log_error("call control render [$clazz] fail," . $e->getMessage());
	}
}

class Control implements IControl
{
	static 	public $ctl_class = "ctl_class_";

	public function render($clazz, $id, array $options)
	{
		if (!$clazz) {
			$clazz = get_class($this);
		}

		echo self::encode_ctl_clazz($clazz, $id, $options);
	}

	public function handle($clazz, $id, $options, array &$request, array &$app)
	{
	}

	static public function encode_ctl_clazz($clazz, $id, array $options)
	{
		$opt = base64_encode(serialize(array("clz" => $clazz, "opt" => $options)));
		return "<input type='hidden' name='" . self::$ctl_class . "$id' value='$opt'>";
	}

	static public function decode_ctl_clazz($name, $value)
	{
		$cnt = strlen(self::$ctl_class);

		if (strncasecmp($name, self::$ctl_class, $cnt) !== 0) {
			return false;
		}

		$id = substr($name, $cnt, strlen($name) - $cnt);
		$a = unserialize(base64_decode($value));
		if (!isset($a["clz"]) || !isset($a["opt"])) {
			return false;
		}

		return array("id" => $id, "clazz" => $a["clz"], "options" => $a["opt"]);
	}

	static public function pump($name, array &$request)
	{
		if (isset($request[$name])) {
			$result = $request[$name];
			unset($request[$name]);
		}
		else {
			$result = NULL;
		}

		return $result;
	}
}

require_once (ROOT_PATH . "boot/req_inc.php");

?>
