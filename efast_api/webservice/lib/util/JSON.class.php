<?php

class JSON
{
	public $at = 0;
	public $ch = "";
	public $text = "";

	public function encode($arg, $force = true)
	{
		static $_force;

		if (is_null($_force)) {
			$_force = $force;
		}

		if ($_force && (OS_CHARSET == "utf-8") && function_exists("json_encode")) {
			return json_encode($arg);
		}

		$returnValue = "";
		$c = "";
		$i = "";
		$l = "";
		$s = "";
		$v = "";
		$numeric = true;

		switch (gettype($arg)) {
		case "array":
			foreach ($arg as $i => $v ) {
				if (!is_numeric($i)) {
					$numeric = false;
					break;
				}
			}

			if ($numeric) {
				foreach ($arg as $i => $v ) {
					if (0 < strlen($s)) {
						$s .= ",";
					}

					$s .= $this->encode($arg[$i]);
				}

				$returnValue = "[" . $s . "]";
			}
			else {
				foreach ($arg as $i => $v ) {
					if (0 < strlen($s)) {
						$s .= ",";
					}

					$s .= $this->encode($i) . ":" . $this->encode($arg[$i]);
				}

				$returnValue = "{" . $s . "}";
			}

			break;

		case "object":
			foreach (get_object_vars($arg) as $i => $v ) {
				$v = $this->encode($v);

				if (0 < strlen($s)) {
					$s .= ",";
				}

				$s .= $this->encode($i) . ":" . $v;
			}

			$returnValue = "{" . $s . "}";
			break;

		case "integer":
		case "double":
			$returnValue = (is_numeric($arg) ? (string) $arg : "null");
			break;

		case "string":
			$returnValue = "\"" . strtr($arg, array("\r" => "\\r", "\n" => "\\n", "\t" => "\\t", "\b" => "\b", "\f" => "\\f", "\\" => "\\\\", "\"" => "\\\"", "\000" => "\u0000", "\001" => "\u0001", "\002" => "\u0002", "\003" => "\u0003", "\004" => "\u0004", "\005" => "\u0005", "\006" => "\u0006", "\a" => "\u0007", "\010" => "\b", "\v" => "\u000b", "\f" => "\\f", "\016" => "\u000e", "\017" => "\u000f", "\020" => "\u0010", "\021" => "\u0011", "\022" => "\u0012", "\023" => "\u0013", "\024" => "\u0014", "\025" => "\u0015", "\026" => "\u0016", "\027" => "\u0017", "\030" => "\u0018", "\031" => "\u0019", "\032" => "\u001a", "\033" => "\u001b", "\034" => "\u001c", "\035" => "\u001d", "\036" => "\u001e", "\037" => "\u001f")) . "\"";
			break;

		case "boolean":
			$returnValue = ($arg ? "true" : "false");
			break;

		default:
			$returnValue = "null";
		}

		return $returnValue;
	}

	public function decode($text, $type = 0)
	{
		if (empty($text)) {
			return "";
		}
		else if (!is_string($text)) {
			return false;
		}

		if ((OS_CHARSET === "utf-8") && function_exists("json_decode")) {
			return addslashes_deep_obj(json_decode(stripslashes($text), $type));
		}

		$this->at = 0;
		$this->ch = "";
		$this->text = strtr(stripslashes($text), array("\r" => "", "\n" => "", "\t" => "", "\b" => "", "\000" => "", "\001" => "", "\002" => "", "\003" => "", "\004" => "", "\005" => "", "\006" => "", "\a" => "", "\010" => "", "\v" => "", "\f" => "", "\016" => "", "\017" => "", "\020" => "", "\021" => "", "\022" => "", "\023" => "", "\024" => "", "\025" => "", "\026" => "", "\027" => "", "\030" => "", "\031" => "", "\032" => "", "\033" => "", "\034" => "", "\035" => "", "\036" => "", "\037" => ""));
		$this->next();
		$return = $this->val();
		$result = (empty($type) ? $return : $this->object_to_array($return));
		return addslashes_deep_obj($result);
	}

	public function error($m)
	{
		trigger_error($m . " at offset " . $this->at . ": " . $this->text, 256);
	}

	public function next()
	{
		$this->ch = (!isset($this->text[$this->at]) ? "" : $this->text[$this->at]);
		$this->at++;
		return $this->ch;
	}

	public function str()
	{
		$i = "";
		$s = "";
		$t = "";
		$u = "";

		if ($this->ch == "\"") {
			while ($this->next() !== NULL) {
				if ($this->ch == "\"") {
					$this->next();
					return $s;
				}
				else if ($this->ch == "\\") {
					switch ($this->next()) {
					case "b":
						$s .= "\b";
						break;

					case "f":
						$s .= "\\f";
						break;

					case "n":
						$s .= "\\n";
						break;

					case "r":
						$s .= "\\r";
						break;

					case "t":
						$s .= "\\t";
						break;

					case "u":
						$u = 0;

						for ($i = 0; $i < 4; $i++) {
							$t = (int) sprintf("%01c", hexdec($this->next()));

							if (!is_numeric($t)) {
								break 2;
							}

							$u = ($u * 16) + $t;
						}

						$s .= chr($u);
						break;

					case "'":
						$s .= "'";
						break;

					default:
						$s .= $this->ch;
					}
				}
				else {
					$s .= $this->ch;
				}
			}
		}

		$this->error("Bad string");
	}

	public function arr()
	{
		$a = array();

		if ($this->ch == "[") {
			$this->next();

			if ($this->ch == "]") {
				$this->next();
				return $a;
			}

			while (isset($this->ch)) {
				array_push($a, $this->val());

				if ($this->ch == "]") {
					$this->next();
					return $a;
				}
				else if ($this->ch != ",") {
					break;
				}

				$this->next();
			}

			$this->error("Bad array");
		}
	}

	public function obj()
	{
		$k = "";
		$o = new StdClass();

		if ($this->ch == "{") {
			$this->next();

			if ($this->ch == "}") {
				$this->next();
				return $o;
			}

			while ($this->ch) {
				$k = $this->str();

				if ($this->ch != ":") {
					break;
				}

				$this->next();
				$o->$k = $this->val();

				if ($this->ch == "}") {
					$this->next();
					return $o;
				}
				else if ($this->ch != ",") {
					break;
				}

				$this->next();
			}
		}

		$this->error("Bad object");
	}

	public function assoc()
	{
		$k = "";
		$a = array();

		if ($this->ch == "<") {
			$this->next();

			if ($this->ch == ">") {
				$this->next();
				return $a;
			}

			while ($this->ch) {
				$k = $this->str();

				if ($this->ch != ":") {
					break;
				}

				$this->next();
				$a[$k] = $this->val();

				if ($this->ch == ">") {
					$this->next();
					return $a;
				}
				else if ($this->ch != ",") {
					break;
				}

				$this->next();
			}
		}

		$this->error("Bad associative array");
	}

	public function num()
	{
		$n = "";
		$v = "";

		if ($this->ch == "-") {
			$n = "-";
			$this->next();
		}

		while (("0" <= $this->ch) && ($this->ch <= "9")) {
			$n .= $this->ch;
			$this->next();
		}

		if ($this->ch == ".") {
			$n .= ".";
			while ($this->next() && ("0" <= $this->ch) && ($this->ch <= "9")) {
				$n .= $this->ch;
			}
		}

		if (($this->ch == "e") || ($this->ch == "E")) {
			$n .= "e";
			$this->next();
			if (($this->ch == "-") || ($this->ch == "+")) {
				$n .= $this->ch;
				$this->next();
			}

			while (("0" <= $this->ch) && ($this->ch <= "9")) {
				$n .= $this->ch;
				$this->next();
			}
		}

		$v += $n;

		if (!is_numeric($v)) {
			$this->error("Bad number");
		}
		else {
			return $v;
		}
	}

	public function word()
	{
		switch ($this->ch) {
		case "t":
			if (($this->next() == "r") && ($this->next() == "u") && ($this->next() == "e")) {
				$this->next();
				return true;
			}

			break;

		case "f":
			if (($this->next() == "a") && ($this->next() == "l") && ($this->next() == "s") && ($this->next() == "e")) {
				$this->next();
				return false;
			}

			break;

		case "n":
			if (($this->next() == "u") && ($this->next() == "l") && ($this->next() == "l")) {
				$this->next();
				return NULL;
			}

			break;
		}

		$this->error("Syntax error");
	}

	public function val()
	{
		switch ($this->ch) {
		case "{":
			return $this->obj();
		case "[":
			return $this->arr();
		case "<":
			return $this->assoc();
		case "\"":
			return $this->str();
		case "-":
			return $this->num();
		default:
			return ("0" <= $this->ch) && ($this->ch <= "9") ? $this->num() : $this->word();
		}
	}

	public function object_to_array($obj)
	{
		$_arr = (is_object($obj) ? get_object_vars($obj) : $obj);

		foreach ($_arr as $key => $val ) {
			$val = (is_array($val) || is_object($val) ? $this->object_to_array($val) : $val);
			$arr[$key] = $val;
		}

		return $arr;
	}
}

if (!defined("OS_CHARSET")) {
	define("OS_CHARSET", "utf-8");
}

?>
