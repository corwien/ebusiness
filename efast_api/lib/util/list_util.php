<?php

function list_sort_by($list, $field, $sortby = "asc")
{
	if (!is_array($list)) {
		return false;
	}

	$refer = $result = array();

	foreach ($list as $i => $data ) {
		$refer[$i] = &$data[$field];
	}

	switch ($sortby) {
	case "asc":
		asort($refer);
		break;

	case "desc":
		arsort($refer);
		break;

	case "nat":
		natcasesort($refer);
		break;
	}

	foreach ($refer as $key => $val ) {
		$result[] = &$list[$key];
	}

	return $result;
}


?>
