<?php

function debug($var, $method = true, $exit = false)
{
	echo " <pre>";
	$method ? print_r($var) : var_dump($var);
	echo "</pre> -----------------------";

	if ($exit) {
		exit();
	}
}

function objectToArray($e)
{
	$e = (array) $e;

	foreach ($e as $k => $v ) {
		if (gettype($v) == "resource") {
			return NULL;
		}

		if ((gettype($v) == "object") || (gettype($v) == "array")) {
			$e[$k] = (array) objecttoarray($v);
		}
	}

	return $e;
}

function GetRandStr($len)
{
	$chars = array("a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k", "l", "m", "n", "o", "p", "q", "r", "s", "t", "u", "v", "w", "x", "y", "z", "A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z", "0", "1", "2", "3", "4", "5", "6", "7", "8", "9");
	$charsLen = count($chars) - 1;
	shuffle($chars);
	$output = "";

	for ($i = 0; $i < $len; $i++) {
		$output .= $chars[mt_rand(0, $charsLen)];
	}

	return $output;
}

function xml2array(&$xmlString, array &$arr)
{
	$xmlString = trim($xmlString);

	if (strpos($xmlString, "<") !== 0) {
		return false;
	}

	try {
		$root = simplexml_load_string($xmlString, "SimpleXMLElement", LIBXML_NOCDATA);
		$name = $root->getName();
		$resultarr = array();
		_webmethod_xml2array_fillarray($root, $resultarr);
		$arr[$name] = $resultarr;
		return true;
	}
	catch (Exception $e) {
		throw new Exception("返回xml解析错误:" . $e->getMessage());
	}
}

function _webmethod_xml2array_fillArray($root, array &$resultarr)
{
	if (!$root) {
		return NULL;
	}

	$rootarr = get_object_vars($root);

	foreach ($rootarr as $key => $val ) {
		if (is_object($val)) {
			$newarr = array();
			_webmethod_xml2array_fillarray($val, $newarr);

			if (0 < count($newarr)) {
				$resultarr[$key] = $newarr;
			}
			else {
				$resultarr[$key] = NULL;
			}
		}
		else {
			if (is_array($val) && (0 < count($val))) {
				$valarr = array();
				$val = array_values($val);

				for ($i = 0; $i < count($val); $i++) {
					$v = $val[$i];

					if (is_object($v)) {
						$newarr = array();
						_webmethod_xml2array_fillarray($v, $newarr);
						$valarr[] = $newarr;
					}
					else {
						$valarr[] = $v;
					}
				}

				$resultarr[$key] = $valarr;
			}
			else {
				$resultarr[$key] = $val;
			}
		}
	}
}

function addslashes_deep_obj($obj)
{
	if (is_object($obj) == true) {
		foreach ($obj as $key => $val ) {
			$obj->$key = addslashes_deep($val);
		}
	}
	else {
		$obj = addslashes_deep($obj);
	}

	return $obj;
}

function addslashes_deep($value)
{
	if (empty($value)) {
		return $value;
	}
	else {
		return is_array($value) ? array_map("addslashes_deep", $value) : addslashes($value);
	}
}

function u_json_decode($mixed, $is_array = true)
{
	global $context;
	$file = ROOT_PATH . $context->app_name . "/lib/util/JSON.class.php";

	if (!file_exists($file)) {
		throw new Exception("can not find json class");
	}

	require_once ($file);
	$json = new JSON();
	$type = ($is_array ? 1 : 0);
	return $json->decode($mixed, $type);
}


?>
