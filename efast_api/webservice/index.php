<?php

function stripslashes_deep($value)
{
    $value = (is_array($value) ? array_map("stripslashes_deep", $value) : stripslashes($value));
    return $value;
}

if (get_magic_quotes_gpc()) {
    $_POST = array_map("stripslashes_deep", $_POST);
    $_GET = array_map("stripslashes_deep", $_GET);
    $_COOKIE = array_map("stripslashes_deep", $_COOKIE);
    $_REQUEST = array_map("stripslashes_deep", $_REQUEST);
}

// D:/xampp/php/php.exe -f D:/xampp/htdocs/efast/efast_api/webservice/web/index.php app_fmt=json app_act=taobao_api/taobao_trades_sold_get_all sd_id=4 start_modified="2012-09-01 00:00:00" end_modified="2012-09-11 00:00:00"
define("RUN_FROM_INDEX", true);
include(dirname(dirname(__FILE__)) . "/boot/req_init.php");
$res = $GLOBALS["context"]->fire_request_handle();
// 明晚分析这个方法的使用【2017-12-26 03:01:14】
// 明晚分析引用方法require_lib()【2017-12-27 01:47:14】
// $ret = json_encode(array('resp_data' => array('code' => '1', 'msg' => 'success')));
// echo $ret;
// echo var_export($GLOBALS["context"], true);
// $ret = $context->prepare_request_handle();
// echo var_export($ret, true);

