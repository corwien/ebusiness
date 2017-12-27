<?php

function get_theme_js($jslist, $mutil_lang = true)
{
	if (!$jslist) {
		return "";
	}

	$s = "";

	foreach (explode(",", $jslist) as $js ) {
		$u = get_theme_url("js/" . trim($js), $mutil_lang);
		$s .= "<script type='text/javascript' src='$u'></script>\n";
	}

	return $s;
}

function echo_theme_js($jslist, $mutil_lang = true)
{
	echo get_theme_js($jslist, $mutil_lang);
}

function get_theme_css($csslist, $mutil_lang = true)
{
	if (!$csslist) {
		return "";
	}

	$l = "";

	foreach (explode(",", $csslist) as $css ) {
		$u = get_theme_url("css/" . trim($css), $mutil_lang);
		$l .= "<link rel='stylesheet' type='text/css' href='$u'></link>\n";
	}

	return $l;
}

function echo_theme_css($csslist, $mutil_lang = true)
{
	echo get_theme_css($csslist, $mutil_lang);
}

function get_select_option(array $data, $sel_key = NULL, $null_caption = NULL)
{
	if (!$data) {
		return "";
	}

	$o = "";

	if ($null_caption) {
		$o .= "<option value='null'";
		if ($sel_key && ($sel_key == "null")) {
			$o .= " selected='selected' ";
		}

		$o .= ">$null_caption</option>";
	}

	foreach ($data as $key => $val ) {
		if (empty($key) || empty($val)) {
			continue;
		}

		$o .= "<option value='$key' ";
		if ($sel_key && ($sel_key == $key)) {
			$o .= " selected='selected' ";
		}

		$o .= " >$val</option> ";
	}

	return $o;
}


?>
