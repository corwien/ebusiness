<?php
interface IRequestTool{
	/**
	 * 注册工具类，可注册到context.
	 * @param string $prop context的property
	 * @return object 返回工具类对象
	 * <br/>如果返回数组，tool_obj为工具类对象，
	 * 需要使用工具类对象的类必须实现attach_method方法，attach_method为需要注入工具类对象的目标需要实现的方法名称，
	 * 详见@see  RequestContext，attach_method的方法原型为：function [attach_method]([interface] $prop)，
	 * 默认为function set_$property($get_prop_func)，如set_log($log_call)。
	 */
	static function register($prop); //return 
}

//return mean stop handle,true,stop handle chain,
interface IRequestFilter{
	function handle_before(array & $request,array & $response,array & $app);
}
interface IReponseFilter{
	function handle_after(array & $request,array & $response,array & $app);
}
interface IReponseRenderer{
	function render(array & $request,array & $response,array & $app);
}
//control
interface IControl {
	/**
	 * @param string $id 组件id 在html中实际组件以ctrl_$id为前缀。
	 * @param array $options  参数  render参数
	 */
	function render($clazz,$id,array $options);
	function handle($clazz,$id,$options,array & $request,array & $app);
}
//widget action 函数原型
interface IWidget {
	function do_index(array & $request,array & $response,array & $app);
}
/**
 *IConfig类用于配置参数加载。配置参数来源默认是config_app表<br/>
 *配置参数采用file，apc，memcache多种方式缓存配置参数，在同一request中使用对象变量缓存配置参数。
 *配置参数通过catalog,group,var,name（通过.连接）组成的key来找到value，如app.cookie.cookie.path。<br/>
 *get返回key对应的value，如果没有找到返回$default。<br/>
 *get_var返回group,var组成的$varkey对应的value数组（var中的name=>value），如果没有找到返回NULL。<br/>
 *clearCache用于清除缓存，file方式将清除group对应的所有的缓存，其它清除$varkey对应的所有的缓存。
 */
interface IConfig{
	function 	get($key,$default=NULL);
	function 	get_var($varkey);
	function 	clearCache($key);
}
/**
 *ICache类用于数据缓存。<br/>
 *采用file，apc，memcache多种方式缓存数据。
 *get 返回通过set缓存的数据，如果没有找到，或者过期，返回NULL。
 *set 设置数据缓存值，采用memcache时$value如果为FALSE，将转换为0，$ttl为缓存有效时间，单位秒second
 *clear用于清除缓存。
 */
interface ICache{
	function 	get($key);
	function 	set($key,$value,$ttl=NULL);
	function 	delete($key);
	function 	clear();
}

interface IDB{
	 //function connect($dbhost, $dbuser, $dbpw, $dbname, $charset = 'utf8', $pconnect = 0, $quiet = 0);
	 //function query($sql, $args=array(), $type = '');
	 //function insert_id();
	 //function table($table_name);
	 function query($sql, $args=array());
	 function insert_id($sequence=null);
	 function getOne($sql, $limited = false);
	 function getAll($sql, $key_index='');
	 function getRow($sql, $limited = false);
	 function getCol($sql);
	 function autoExecute($table, $field_values, $mode = 'INSERT', $where = '', $querymode = '');
	 function autoReplace($table, $field_values, $update_values, $where = '', $querymode = '');
	 function table($table_name,$region_value=NULL);
	 
	 public function trans_begin();
	 public function trans_commit();
	 public function trans_rollback();
	 
	 public function insert($table_name, $data);
	 public function update($table_name, $data, $where='');
}


/**
 *日志文件类，建议使用$context全局对象的log_error，log_debug方法
 */
interface ILog{
	function error($msg);
	function warn($msg);
	function debug($msg);
	function info($msg);
}
