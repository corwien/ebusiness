<?php
/***模块注册、重要常量配置，请谨慎更改***/

/***设置运行模式和重要常量***/
	define('DEBUG',false); 				//是否调试态，调试态可产生调试日志，关闭权限处理等

	define('RUN_SAFE',false);			//是否设置安全运行模式，如必须使用cookie输入app_efid等.
	define('RUN_FAST',false);			//是否设置快速运行模式，如忽略检查timezone等，相信系统已经配置好参数.
	define('RUN_SESSPUB',false);		//是否设置session是否全局，各webapp共享session，其cookie的path为/
	define('RUN_ACC_CTL',true); 		//是否激活用户访问控制模块
	define('RUN_USER_DEBUG',false); 	//是否激活按用户设置调试态模块
	define('RUN_CONTROL',true); 		//是否激活控件支持
	define('RUN_WIDGET',false); 			//是否激活配件支持
	define('RUN_COMBILE_TPL',true); 	//是否激活tpl文件组合装配缓存支持
	define('RUN_WEB_LOG',false); 		//是否激活网页显示log记录
	define('RUN_MLANG_VIEW',true); 		//是否支持多语言view

	define('APP_SALT','EfasT'); 		//数据加密或签名用的附加文字
	
/***end 设置运行模式和重要常量***/	
	
	
/**系统设置:注册tools，请小心更改**/
     // 先注释掉这些没有用的类方法【2017-1226】
	$context->register_tool('log','lib/tool/Log_api.class.php');   // $impl_class = Log_api, $ext = class.php
  
	//$context->register_tool('db','lib/db/Mysql.class.php');
  	// $context->register_tool('db','lib/db/PDODB.class.php');
  	
	//$context->register_tool('conf','lib/tool/Config.class.php','FileConfig');
	//$context->register_tool('conf','lib/tool/Config.class.php','ApcConfig');
	//$context->register_tool('conf','lib/tool/Config.class.php','MemCacheConfig');
	//$context->register_tool('conf','lib/tool/Config.class.php','EmptyCacheConfig');
  
	//$context->register_tool('cache','lib/tool/Cache.class.php','FileCache');
	//$context->register_tool('cache','lib/tool/Cache.class.php','ApcCache');
	//$context->register_tool('cache','lib/tool/Cache.class.php','MemCacheCache');
	// $context->register_tool('cache','lib/tool/Cache.class.php','EmptyCache');
  	
/**end 系统设置**/

// 创建一个匿名函数
// create_function();
/*
// string create_function ( string $args , string $code )
$newfunc = create_function('$a,$b', 'return "ln($a) + ln($b) = " . log($a * $b);');
echo "New anonymous function: $newfunc\n";
echo $newfunc(2, M_E) . "\n";
// outputs
// New anonymous function: lambda_1
// ln(2) + ln(2.718281828459) = 1.6931471805599
 */
  
/**系统设置: 注册filters和renders，请小心更改**/
	//request filter

/*
	if(defined('RUN_ACC_CTL') && RUN_ACC_CTL) $context->register_request_filter('UserAccessFilter','lib/filter/UserAccessFilter.class.php',
		create_function('$app','return isset($app["mode"]) && $app["mode"]=="func";'));		//添加访问是否非法检查，增加create_function('$app','return $app["mode"]=="func";'仅对func有效。
  		
	if(defined('RUN_USER_DEBUG') && RUN_USER_DEBUG) $context->register_request_filter('UserDebugFilter','lib/filter/UserDebugFilter.class.php',
		create_function('$app','return !( defined("DEBUG") && DEBUG) && isset($app["user_debug"]);'));//function($app){return !( defined("DEBUG") && DEBUG) && isset($app["user_debug"]);});

    // 1、此类会被调用
	if(defined('RUN_CONTROL') && RUN_CONTROL)  $context->register_request_filter('ControlFilter','lib/ctl/Control.class.php',
		create_function('$app','return $app["fmt"]=="html" && $app["err_no"]==0;'));

	if(defined('RUN_WIDGET') && RUN_WIDGET) $context->register_request_filter('DBWidgetAddFilter','lib/filter/DBWidgetFilter.class.php',
		create_function('$app','return $app["fmt"]=="html" && $app["err_no"]==0;'));//从widget_opt数据库配置表添加配件参数
    
	//reponse filter
	if(defined('RUN_WIDGET') && RUN_WIDGET) $context->register_response_filter('CacheWidgetCallFilter','lib/filter/DBWidgetFilter.class.php',
		create_function('$app','return $app["fmt"]=="html" && $app["err_no"]==0;'));//调用配件app函数 ，并缓存组合 Widget action	文件
	//if(defined('RUN_WIDGET') && RUN_WIDGET) $context->register_response_filter('WidgetCallFilter','lib/filter/DBWidgetFilter.class.php',
	//	create_function('$app','return $app["fmt"]=="html" && $app["err_no"]==0;'));//直接调用配件Widget action函数,不缓存组合

     // 2、此文件也会被调用
	if(defined('RUN_COMBILE_TPL') && RUN_COMBILE_TPL) $context->register_response_filter('CombineTPLFilter','lib/filter/CombineTPLFilter.class.php',
		create_function('$app','return $app["fmt"]=="html" && $app["err_no"]==0;'));//装配并缓存模板文件
  */

	//renderers,此方法会返回数据给 webservice/web/index.php 页面输出，然后 curl_post 获得数据
	$context->register_renderer('JsonRenderer','lib/filter/JsonRenderer.class.php',
		create_function('$app','return isset($app["mode"]) && ($app["mode"]=="func" || $app["fmt"]=="json");'));//function($app){return $app["mode"]=="func" || $app["fmt"]=="json";});
  		
	$context->register_renderer('CsvRenderer','lib/filter/CsvRenderer.class.php',
		create_function('$app','return $app["fmt"]=="csv";'));//function($app){return $app["fmt"]=="csv";});
  		
	$context->register_renderer('HtmlRenderer','lib/filter/HtmlRenderer.class.php',
		create_function('$app','return $app["fmt"]=="html";'));
/**end 系统设置**/
