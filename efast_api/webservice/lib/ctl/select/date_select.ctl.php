<?php

class date_select extends Control
{
	public function handle($clazz, $id, $options, array &$request, array &$app)
	{
		if (basename($clazz) !== get_class($this)) {
			return NULL;
		}

		if (!isset($request["ctl_{$id}_y"])) {
			return NULL;
		}

		$year = $this->pump("ctl_{$id}_y", $request);
		$month = $this->pump("ctl_{$id}_m", $request);
		$day = $this->pump("ctl_{$id}_d", $request);

		if (!$month) {
			$date = date_create_from_format("Y", $year);
		}
		else if (!$day) {
			$date = date_create_from_format("Y-m", "$year-$month");
		}
		else {
			$date = new DateTime("$year-$month-$day");
		}

		$cur_val = date_timestamp_get($date);
		$min_val = $this->get_date($options["min"]);
		$max_val = $this->get_date($options["max"]);
		$cur_val = ($cur_val < $min_val ? $min_val : $max_val < $cur_val ? $max_val : $cur_val);
		$request[$id] = $cur_val;
	}

	public function get_date($dt)
	{
		if (!$dt) {
			return 0;
		}
		else if (is_object($dt)) {
			return date_timestamp_get($dt);
		}
		else if (is_int($dt)) {
			return $dt;
		}
		else {
			return date_timestamp_get(new DateTime($dt));
		}
	}

	public function render($clazz, $id, array $options)
	{
		$min_val = $this->get_date($options["min"]);
		$max_val = $this->get_date($options["max"]);
		$cur_val = $this->get_date($options["cur"]);
		$val = max($min_val, $max_val);
		$min_val = min($min_val, $max_val);
		$max_val = $val;
		$cur_val = ($cur_val < $min_val ? $min_val : $max_val < $cur_val ? $max_val : $cur_val);
		$val = getdate($min_val);
		$minyear = $val["year"];
		$minmon = $val["mon"];
		$minday = $val["mday"];
		$val = getdate($max_val);
		$maxyear = $val["year"];
		$maxmon = $val["mon"];
		$maxday = $val["mday"];
		$val = getdate($cur_val);
		$curyear = $val["year"];
		$curmon = $val["mon"];
		$curday = $val["mday"];
		$max_mday = 31;

		if (in_array($curmon, array(2, 4, 6, 9, 11))) {
			if ($curmon == 2) {
				if ((($curyear % 400) == 0) || ((($curyear % 4) == 0) && (($curyear % 100) != 0))) {
					$max_mday = 29;
				}
				else {
					$max_mday = 28;
				}
			}
			else {
				$max_mday = 30;
			}
		}

		echo "<span id='ctl_$id'>";
		echo "<select id='ctl_{$id}_y' name='ctl_{$id}_y'>";

		for ($i = $minyear; $i <= $maxyear; $i++) {
			echo "<option value='$i'";

			if ($i == $curyear) {
				echo " selected='selected' ";
			}

			echo ">$i</option>";
		}

		echo "</select>年&nbsp;";
		echo "<select id='ctl_{$id}_m' name='ctl_{$id}_m'>";

		for ($i = 1; $i <= 12; ++$i) {
			echo "<option value='$i'";

			if ($i == $curmon) {
				echo " selected='selected' ";
			}

			echo ">$i</option>";
		}

		echo "</select>月&nbsp;";
		echo "<select id='ctl_{$id}_d' name='ctl_{$id}_d'>";

		for ($i = 1; $i <= $max_mday; ++$i) {
			echo "<option value='$i'";

			if ($i == $curday) {
				echo " selected='selected' ";
			}

			echo ">$i</option>";
		}

		echo "</select>日";
		parent::render($clazz, $id, array("min" => $min_val, "max" => $max_val));
		echo "</span>\n";
		echo "<script type='text/javascript' src='" . get_url("js/ctl/ctl_date_select.js") . "'></script>";
		echo "<script type='text/javascript'>var sel_$id=new ctl_date_select('ctl_{$id}_y','ctl_{$id}_m','ctl_{$id}_d');";
		echo "sel_$id.set_range($minyear,$minmon,$minday,$maxyear,$maxmon,$maxday);";
		echo "sel_$id.set_sel_date($curyear,$curmon,$curday);sel_$id.startup();</script>";
	}
}

require_once (ROOT_PATH . "lib/ctl/Control.class.php");

?>
