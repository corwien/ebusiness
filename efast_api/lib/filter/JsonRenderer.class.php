<?php

class JsonRenderer implements IReponseRenderer
{
	public function render(array &$request, array &$response, array &$app)
	{
		if (($app["mode"] !== "func") && ($app["fmt"] !== "json")) {
			return NULL;
		}

		if ($app["err_no"] !== 0) {
			$json = json_encode(array(
	"resp_data" => array("code" => $app["err_no"], "msg" => $app["err_msg"])
	));
		}
		else {
			$json = json_encode(array("resp_data" => $response));
		}

		if (isset($request["callback"]) && $request["callback"] && ($app["mode"] !== "func")) {
			echo $request["callback"] . "(" . $json . ")";
		}
		else {
			echo $json;
		}

		return true;
	}
}

require_once (ROOT_PATH . "boot/req_inc.php");

?>
