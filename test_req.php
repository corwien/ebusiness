<?php

require_once("init.php");

require_once(ROOT_BASE_PATH . "moudle/openapi/biz_taobao/BizTaobao.class.php");

$obj = new BizTaobao();
$ret = $obj->test();

print_r($ret);

