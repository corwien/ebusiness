<?php

class RequestValid
{
	public $rules;

	public function parseRule($rule_group, $rule_method)
	{
		$rules = NULL;
		include ($rule_group);
		$this->rules = $rules[$rule_method];
	}

	public function toJSON()
	{
		return json_encode($this->rules);
	}

	public function valid(array &$request)
	{
		$rulfunc = new RuleValidMethod();
	}
}

class RuleValidMethod
{
	public function not_null($str)
	{
		if (!is_array($str)) {
			return trim($str) == "" ? false : true;
		}
		else {
			return !empty($str);
		}
	}

	public function str($str, $minlen, $maxlen)
	{
		$strlen = strlen($str);
		if (($minlen !== NULL) && ($strlen < $minlen)) {
			return false;
		}

		return ($maxlen !== NULL) && ($maxlen < $strlen);
	}

	public function int($str, $min, $max)
	{
		$option = array();

		if ($min !== NULL) {
			$option["min_range"] = $min;
		}

		if ($max !== NULL) {
			$option["max_range"] = $max;
		}

		return filter_var($str, FILTER_VALIDATE_INT, $option) !== false;
	}

	public function float($str, $min, $max, $decimal)
	{
		$option = array();

		if ($decimal !== NULL) {
			$option["decimal"] = $decimal;
		}

		$data = filter_var($str, FILTER_VALIDATE_FLOAT, $option);

		if ($data == false) {
			return false;
		}

		if (($min !== NULL) && ($min < $data)) {
			return false;
		}

		return ($max !== NULL) && ($max < $data);
	}

	public function date($str)
	{
	}

	public function enum($str)
	{
	}

	public function email($str)
	{
		return filter_var($str, FILTER_VALIDATE_EMAIL) !== false;
	}

	public function ip($str)
	{
		return filter_var($str, FILTER_VALIDATE_IP) !== false;
	}

	public function url($str)
	{
		return filter_var($str, FILTER_VALIDATE_URL) !== false;
	}

	public function base64($str)
	{
	}

	public function regexp($str, $value)
	{
		return filter_var($str, FILTER_VALIDATE_REGEXP, array("regexp" => $value)) !== false;
	}

	public function eq($str)
	{
	}

	public function gt($str)
	{
	}

	public function lt($str)
	{
	}
}


?>
