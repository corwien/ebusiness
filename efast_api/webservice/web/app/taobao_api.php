<?php

/*
* SKU库存同步
 * @param unknown_type $sd_id	商店ID
 * @param unknown_type $sku		系统商品条码
 * @param unknown_type $num		同步数量
 *
 * 示例：D:/xampp/php/php.exe -f D:/xampp/htdocs/efast/efast_api/webservice/web/index.php app_fmt=json app_act=taobao_api/item_quantity_sync sd_id=4 sku=aaaaa num=51 checked=0
 *
 * 注意，这里使用地址符 &response作为响应，所以，不用return返回
 * */

function item_quantity_sync(array & $request,array & $response,array & $app)
{
    $ret = NULL;
   // $response = array('code' => 0, 'msg' => 'test', 'data' => 'hahahah');
    $response = array('name' => 'corwien', 'age' => '25', 'data' => 'hahahah');
}







