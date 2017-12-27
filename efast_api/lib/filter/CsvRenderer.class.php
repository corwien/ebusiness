<?php

class CsvRenderer implements IReponseRenderer
{
	private $delimit = ",";

	public function render(array &$request, array &$response, array &$app)
	{
		if ($app["fmt"] === "csv") {
			if ($app["err_no"] !== 0) {
				header("Content-type: text/html;charset=UTF-8");
				echo "<b>" . lang("req_err_title") . "</b><br>" . lang("req_err_no") . ":" . $app["err_no"] . "<br>\n" . lang("req_err_msg") . $app["err_msg"];
				return true;
			}
			else if (0 < count($response)) {
				header("Content-type: text/csv;charset=gbk");
				header("Content-Disposition: attachment; filename=\"" . $app["grp"] . "_" . $app["act"] . ".csv\"");
				reset($response);

				if (count($response) == 1) {
					$response = current($response);
				}

				reset($response);
				if (isset($app["caption"]) && ($app["caption"] == "y")) {
					$data = current($response);
					$title = array_keys($data);
					echo iconv("utf-8", "gbk", implode($this->delimit, $title)) . "\n";
				}

				foreach ($response as $row ) {
					$val = array_values($row);
					echo iconv("utf-8", "gbk", implode($this->delimit, $val)) . "\n";
				}
			}

			return true;
		}

		return false;
	}
}

require_once (ROOT_PATH . "boot/req_inc.php");

?>
