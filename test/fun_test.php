<?php

if(!function_exists('dump'))
{
    /**
     * 打印数据
     * @param mixed $data （可以是字符串，数组，对象）
     * @param  boolean $is_exit 是否退出程序，默认否
     */
    function dump($data, $is_exit = false)
    {
        echo "<pre>";
        print_r($data);
        echo "</pre>";

        if($is_exit) exit();
    }

}

// 1.获取根目录,由后往前推
define('ROOT_PATH',substr(__FILE__,0,strlen(__FILE__)-17));    // 应用根目录  rtrim 'test/fun_test.php'
dump(ROOT_PATH);   // 打印 /Applications/XAMPP/xamppfiles/htdocs/web/camel/efast_wms/

// 2.设置include_path
set_include_path(get_include_path(). PATH_SEPARATOR . ROOT_PATH);		//设置include path，包含文件可忽略ROOT_PATH部分

// 3.定义分隔符常量
define('DS', DIRECTORY_SEPARATOR);

// 4.根据文件名获取类名和扩展名
dump("========4.根据文件名获取类名和扩展名============");
$impl_file = "/web/camel/efast_wms/test/hello.class.php";
list($impl_class, $ext) = explode('.', basename($impl_file), 2);
// array explode ( string $delimiter , string $string [, int $limit ] )
//  如果设置了 limit 参数并且是正数，则返回的数组包含最多 limit 个元素，而最后那个元素将包含 string 的剩余部分。
dump($impl_class);   // 打印：hello
dump($ext);          // 打印：class.php

// 5.标准对象
dump("======== 5.标准对象 ============");
$objitem = new stdClass();
$objitem->file = "test/hello.class.php";  // 动态的分配属相
$objitem->class = "hello";
dump($objitem);

// 6.从路径解析参数
dump("======== 6.从路径解析参数 ============");

if(DS=='\\')
{
    // windows 环境下
    $app_script_file = str_replace('/', DS, $_SERVER['SCRIPT_FILENAME']);
}
else
{
    $app_script_file = $_SERVER['SCRIPT_FILENAME'];
}
dump($app_script_file); // 打印：/Applications/XAMPP/xamppfiles/htdocs/web/camel/efast_wms/test/fun_test.php

// 这里将 $app_script_file 写死：
$app_script_file = "/Applications/XAMPP/xamppfiles/htdocs/web/camel/efast_test/efast_api/boot/req_init.php";

$cnt = strlen(ROOT_PATH);
$file_path = substr($app_script_file, $cnt, strlen($app_script_file) - $cnt);
list($app_name, $other) = explode(DS, $file_path, 2);
dump($app_name);  // test
dump($other);     // fun_test.php

// D:/xampp/php/php.exe -f D:/xampp/htdocs/efast/efast_api/webservice/web/index.php app_fmt=json app_act=taobao_api/taobao_trades_sold_get_all sd_id=4 start_modified="2012-09-01 00:00:00" end_modified="2012-09-11 00:00:00"
$cnt = strlen('web'.DS.'app'.DS);  // /etast/efast_api/webservice/web/app/taobao_api.php

// 7.路径数据过滤
dump("======== 7.路径数据过滤 ============");
$pathgrp = "/women/hek8dso*jid\/heloid.php";
$pathgrp = preg_replace('/[^a-z0-9_\/]+/i', '', $pathgrp);
dump($pathgrp);  // /women/hek8dsojid/heloidphp

$rpos=strrpos($pathgrp,'/');
dump($rpos);  // 17
if($rpos!==false){
    $path=substr($pathgrp, 0, ++$rpos);
    dump($rpos);
    dump($path); // /women/hek8dsojid/
    $grp=substr($pathgrp,$rpos,strlen($pathgrp)-$rpos);
} else $grp=$pathgrp;
dump($grp);  // heloidphp


// 8.用户定义的错误处理函数
dump("======== 8.用户定义的错误处理函数 ============");
// 用户定义的错误处理函数
function myErrorHandler($errno, $errstr, $errfile, $errline) {
    echo "<b>Custom error:</b> [$errno] $errstr<br>";
    echo " Error on line $errline in $errfile<br>";
}

// 设置用户定义的错误处理函数
set_error_handler("myErrorHandler");

$test=2;

// 触发错误
if ($test>1) {
    trigger_error("A custom error has been triggered");
}

// 打印：
/**

 Custom error: [1024] A custom error has been triggered
 Error on line 105 in /Applications/XAMPP/xamppfiles/htdocs/web/camel/efast_wms/test/fun_test.php
 */


// 9.调用 POST 方法
// efast_test/moudle/openapi/OpenAPIOperatingBase.php








