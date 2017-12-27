<?php 
/*
 * 本文件是所有request处理的前置入口，请小心修改此文件。
 * <br/>用于建立应用运行的环境，主要针对MVC的C，即控制层。
 * date:2011-1-4
 * author:alex zengjf(alex_zengjf@sina.com)
 */
//***初始化代码
if (strpos($_SERVER['PHP_SELF'], '.php/') !== false){	//invalid path for php
    header("Location:" . substr(PHP_SELF, 0, strpos(PHP_SELF, '.php/') + 4) . "\n");
    exit();
}	
define('ROOT_PATH',substr(__FILE__,0,strlen(__FILE__)-17)); // 应用根目录  rtrim 'boot/req_init.php'
// print(ROOT_PATH) 打印： /etast_test/efast_api/   为efast_api根目录

set_include_path(get_include_path(). PATH_SEPARATOR . ROOT_PATH);		//设置include path，包含文件可忽略ROOT_PATH部分

define('DS', DIRECTORY_SEPARATOR);

define('RUN_LIB_FIRST_APP',true);
define('RUN_MDL_FIRST_APP',true);
define('RUN_LANG_FIRST_APP',true);

$app_debug=false;	
//***初始化代码

/**
 * RequestContext用于建立应用上下文，是singleton类，可通过$GLOBALS['context']、CTX()、RequestContext::instance()方法 得到此对象 
 * @property PDODB  db		数据库对象
 * @property ILog   log		日志对象
 * @property ICache cache	缓存对象
 * @property IConf  conf	配置对象
 */
class RequestContext {
	public 	$app_script;
	public 	$app_name;
	public 	$app_path;
	public 	$app_lang='zh_cn';
	
	public	$from_index=false;
	public 	$theme='default';
	
	public  $action=NULL;
	
	public	$wlog=false;	//是否在网页上显示日志，仅用于调试
	private	$wlog_text='';
	
	public 	$app=NULL;
	public 	$request=NULL;
	public 	$response=NULL;
	public 	$lang=array();
	private $app_script_file=NULL;
	
	private $objlist=array();	//已经注册接口对象配置参数列表
	private $renderlist=array();	//已经注册的ReponseRenderer对象配置参数列表
	private $reqlist=array();	//已经注册的RequestFilter对象配置参数列表
	private $resplist=array();	//已经注册的ReponseFilter对象配置参数列表
	
	/**
	 * 注册工具的接口对象到应用上下文，工具对象通过attach方法注入到request对象中，工具对象必须实现@see IRequestTool接口
	 * @param string $prop			特性property  可通过context得到此property的值，last bind方式
	 * @param string $impl_file		实现接口对象的文件名
	 * @param string $impl_class	实现接口对象的类名称，如果为空，为去了扩展的文件名，实现接口对象的类必须实现@see IRequestTool接口
	 */
	function register_tool($prop,$impl_file,$impl_class=NULL){
		if(! $prop  || ! $impl_file) throw new Exception("register [{$prop}] to RequestContext fail,paramter error");
		if($impl_class==NULL) list($impl_class,$ext) = explode('.',basename($impl_file), 2);
		
		$obj=array('file'=>$impl_file,'class'=>$impl_class,'obj'=>NULL);
		$this->objlist[$prop]=$obj;
	}
	private function register_filter($type,& $impl_class,& $impl_file,$need_callback=NULL){
		if(! $impl_class  || ! $impl_file ){
			if($type==0)  $hint='request filter';
			else if($type==1) $hint='response filter';
			else $hint='renderer';
			throw new Exception("register {$hint} [ {$impl_class} ] to RequestContext fail,paramter error");
		} 	
		$objitem=new stdClass();
		$objitem->file=$impl_file;$objitem->class=$impl_class;$objitem->need=$need_callback;$objitem->obj=NULL;
		if($type==0)			$this->reqlist[$impl_class]=$objitem;	
		else if($type==1)			$this->resplist[$impl_class]=$objitem;
		else		   $this->renderlist[$impl_class]=$objitem;	
		return  $objitem;
	}

	/**
	 *  获取对象
	 * @param $type
	 * @param $impl_class
	 * @param $force
	 *
	 * @return null
	 * @throws \Exception
	 */
	private function get_filter_obj($type,$impl_class,$force){
		if(! $impl_class )	throw new Exception("get filter to RequestContext fail,impl_class null");
		if($type==0 )			$objitem=isset($this->reqlist[$impl_class]) ? $this->reqlist[$impl_class] :NULL;	
		else if($type==1)		$objitem=isset($this->resplist[$impl_class]) ? $this->resplist[$impl_class] :NULL;
		else		   			$objitem=isset($this->renderlist[$impl_class]) ? $this->renderlist[$impl_class] :NULL;	
		$n=NULL;
		if(! $objitem) return $n;
		return $this->new_filter_obj($objitem,$objitem->class,$this->app,$n);  // 返回一个对象
	}

	/**
	 * 注册请求对象配置参数列表
	 * @param      $impl_class
	 * @param      $impl_file
	 * @param null $need_callback
	 *
	 * @return \stdClass
	 * @throws \Exception
	 */
	function register_request_filter($impl_class,$impl_file,$need_callback=NULL){
		return $this->register_filter(0,$impl_class,$impl_file,$need_callback);		
	}

	/**
	 * 获取请求对象
	 * @param      $impl_class
	 * @param bool $force
	 *
	 * @return null
	 * @throws \Exception
	 */
	function get_request_filter_obj($impl_class,$force=false){
		return $this->get_filter_obj(0,$impl_class,$force);
	}

	/**
	 *  注册响应对象列表
	 * @param      $impl_class
	 * @param      $impl_file
	 * @param null $need_callback
	 *
	 * @return \stdClass
	 * @throws \Exception
	 */
	function register_response_filter($impl_class,$impl_file,$need_callback=NULL){
		return $this->register_filter(1,$impl_class,$impl_file,$need_callback);		
	}

	/**
	 * 获取响应对象
	 * @param      $impl_class
	 * @param bool $force
	 *
	 * @return null
	 * @throws \Exception
	 */
	function get_response_filter_obj($impl_class,$force=false){
		return $this->get_filter_obj(1,$impl_class,$force);
	}

	/**
	 *  注册渲染对象集
	 * @param      $impl_class
	 * @param      $impl_file
	 * @param null $need_callback
	 *
	 * @return \stdClass
	 * @throws \Exception
	 */
	function register_renderer($impl_class,$impl_file,$need_callback=NULL){
		return $this->register_filter(2,$impl_class,$impl_file,$need_callback);
	}

	/**
	 * 获取渲染对象
	 * @param      $impl_class
	 * @param bool $force
	 *
	 * @return null
	 * @throws \Exception
	 */
	function get_renderer_obj($impl_class,$force=false){
		return $this->get_filter_obj(2,$impl_class,$force);
	}

	/**
	 * 判断程序是否在cli终端运行
	 * @return bool
	 */
	static function is_in_cli(){
		return isset($GLOBALS ['argc']) && $GLOBALS ['argc']>0;
		//return 0==strncasecmp(PHP_SAPI,'cli',3);
	}

	/**
	 * 解析请求
	 * @desc 加地址符&的作用是返回变量的地址给新的接收值  $a = &parse_request();
	 * @example http://www.jb51.net/article/51985.htm
	 * @return array
	 */
	private function &parse_request() {

		// 判断是否通过终端传参 /var/html/www/bin/php   test_console.php name=2 age=3
		if (isset($GLOBALS ['argc']) && $GLOBALS ['argc'] > 0 && count($_REQUEST)===0) {
			$request = array ();
			$argv = $GLOBALS ['argv'];
			for($i = 1; $i < $GLOBALS ['argc']; $i ++) {
				list ( $key, $val ) = explode ( '=', $argv [$i], 2 );
				$request [$key] = $val;
			}
			return $request;
		} else{
			return $_REQUEST;
		}
	}

	/**
	 * 从路径解析参数
	 *
	 */
	private function parse_params_from_path(){
		if(DS=='\\')	$this->app_script_file =str_replace('/',DS,$_SERVER['SCRIPT_FILENAME']);   // windows 环境下
		else $this->app_script_file=$_SERVER['SCRIPT_FILENAME'];
		
		$cnt=strlen(ROOT_PATH);
		$file_path=substr($this->app_script_file,$cnt,strlen($this->app_script_file)-$cnt);
		list($this->app_name,$other) =explode(DS, $file_path, 2);
		$cnt=strlen('web'.DS.'app'.DS);
		$app_child=substr($other,$cnt,strlen($other)-$cnt);
		$other=basename($app_child);
		$this->app_path = substr($app_child,0,strlen($app_child)-strlen($other));
		if(DS==='\\') $this->app_path = str_replace('\\','/',$this->app_path);
		list($this->app_script, $other) =explode('.', $other, 2);
	}

	/**
	 * @param array $app
	 */
	private function parse_grp_path(array & $app){
		$path=$grp='';
		$this->get_grp_path($app ['grp'],$path,$grp);
		$app['grp']=$grp;
		if($path=='/') 	$app ['path']='';
		else  			$app ['path']=$path;
	}

	/**
	 * 获取路径
	 *
	 * @param $pathgrp
	 * @param $path
	 * @param $grp
	 */
	private function get_grp_path($pathgrp,&$path,&$grp){
		if(! $pathgrp) return;
		$pathgrp=str_replace('\\','/',$pathgrp);
		if(! defined('RUN_SAFE') || RUN_SAFE) $pathgrp = preg_replace('/[^a-z0-9_\/]+/i', '', $pathgrp);
		$rpos=strrpos($pathgrp,'/');	
		if($rpos!==false){
			$path=substr($pathgrp,0,++$rpos);
			$grp=substr($pathgrp,$rpos,strlen($pathgrp)-$rpos);
		}else $grp=	$pathgrp;
	}

	/**
	 * 获取动作路径
	 *
	 * @param $action
	 * @param $path
	 * @param $grp
	 * @param $act
	 */
	function get_path_grp_act($action,&$path,&$grp,&$act){
		if(! $action) return;
		$action = str_replace('\\','/',$action);

		//如果是openapi接口
		if(strpos($action, 'efast') === 0) {
			$action = preg_replace('/[^a-z0-9_\.]+/i', '', $action);
			$action = str_replace('..','.',$action);
			$action = preg_replace('/\./','/',$action,1);
			$action = str_replace('.','_',$action);
		} else {
			$action = preg_replace('/[^a-z0-9_\/]+/i', '', $action);
		}

		$path=$grp=NULL;
		$rpos=strrpos($action,'/');	
		if($rpos!==false){
			$pathgrp=substr($action,0,$rpos);
			$act=substr($action,$rpos+1,strlen($action)-$rpos);
			$rpos=strrpos($pathgrp,'/');	
			if($rpos!==false){
				$path=substr($pathgrp,0,++$rpos);
				$grp=substr($pathgrp,$rpos,strlen($pathgrp)-$rpos);
			}else $grp=	$pathgrp;		
		}else	$act=$action;
	}

	/**
	 * 获取动作
	 *
	 * @param        $grp
	 * @param string $act
	 * @param null   $path
	 * @exmple app_act=taobao_api/taobao_trades_sold_get_all
	 *
	 * @return mixed
	 */
	function get_action($grp,$act='do_index',$path=NULL){
		$action=$grp.'/'. ($act ? $act : 'do_index');
		if($path)	$action = str_replace('\\','/',$path) . $action;
		return  preg_replace('/[^a-z0-9_\/]+/i', '', $action);
	}

	/**
	 * 处理请求
	 */
	function prepare_request_handle(){
		$this->request = & $this->parse_request ();
		$this->parse_params_from_path();
		
		$this->response=array();
		$this->app=array();
		
		reset($this->request);
		for($i=Count($this->request)-1;$i>=0;$i--){
			list($key,$val)=each($this->request);
			if(strncasecmp($key,'app_',4)===0){
				unset($this->request[$key]);
				$akey=substr($key,4,strlen($key)-4);
				$akey= preg_replace('/[^a-z0-9_]+/i', '',$akey);	//common key name
				$this->app[$akey]=$val;
			}
		}

		reset($this->request);	
		
		//set var default
		if(! isset($this->app ['name'])) $this->app ['name']=$this->app_name; //默认应用名
		else $this->app_name=$this->app ['name'];
		
		//set lang for app_mode=func
		if(isset($this->app ['mode']) && $this->app ['mode']==='func' && isset($this->app ['lang'])){
			$l=$this->app ['lang'];
			if($l==='en'|| $l==='zh_cn') $this->app_lang=$l;
			else{
				$l=preg_replace('/[^a-z0-9_]+/i', '',$l);		//common lang name
				if(file_exists(ROOT_PATH .'/lib/lang/'.$l)) $this->app_lang=$l;
			} 
				
			unset($this->app ['lang']);
		}
		if(isset($this->app['page']) && $this->app['page']!=='NULL')
			 $this->app['page']=preg_replace('/[^a-z0-9_\/\\\]+/i', '',$this->app['page']);	//for safe,other char omit	
		if(isset($this->app['tpl'])) preg_replace('/[^a-z0-9_\/\\\]+/i', '',$this->app['tpl']);	//for safe,other char omit
		
		//get act, grp,path by parse app_act; 	app_grp :obsolete
		//if(isset($this->app ['grp']))	$this->parse_grp_path($this->app);
		if(isset($this->app ['act'])){
			$path=$grp=$act=NULL;
			$this->get_path_grp_act($this->app ['act'],$path,$grp,$act);
			$this->app['act']=$act;
			if($this->from_index){
				$this->app ['grp']=$grp;
				$this->app ['path']=$path;
			}else{
				if($grp) $this->app ['grp']=$grp;
				if($path) $this->app ['path']=$path;
			}
		}

	}

	/**
	 * 对外请求处理方法
	 */
	function fire_request_handle(){
		require_lang('req',false);
		
		$app=& $this->app; 
		$request=& $this->request;
		$response=& $this->response;
		
		/*init error handle*/
		if($GLOBALS['app_debug'])
			set_error_handler(array($this,'log_php_error_handler'));	//,E_ALL);
		else 
			set_error_handler(array($this,'log_php_error_handler'),
					E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR |E_USER_ERROR |E_RECOVERABLE_ERROR ); //E_WARNING
		set_exception_handler(array($this,'log_php_exception_handler'));
		/*end init*/

		//set default value
		if(! isset($this->app ['act']) || ! $this->app ['act']) $this->app ['act']='do_index';  			//默认处理函数
		if($this->from_index){
			$this->app_path = isset($this->app['path']) ? $this->app['path'] : NULL; 
			$this->app_script= isset($this->app ['grp']) ? $this->app ['grp'] : NULL; 
			
			if (!isset( $this->app ['grp']) || ! $this->app ['grp']) $this->app ['grp'] = $this->app_script = 'index';
		}else{
			if(RUN_SAFE) {	//get grp,path from url path,canot modify by app_act
				 $this->app ['grp']=$this->app_script;
				 $this->app ['path']=$this->app_path;
			}else{			
				if(! isset($this->app ['grp']) ) $this->app ['grp']=$this->app_script; 	//默认类名
				else $this->app_script=$this->app ['grp'];
			
				if(! isset($this->app ['path'])) $this->app ['path']=$this->app_path;   //默认类路径
				else $this->app_path=$this->app['path'];
			}
		}		
		if(! isset($this->app ['title'])) $this->app ['title']='';		
		
		$this->action=$this->get_action($this->app_script,$this->app['act'],$this->app_path);

		// D:/xampp/htdocs/efast/efast_api/webservice/web/index.php app_fmt=json app_act=taobao_api/taobao_trades_sold_get_all
		//如果是openapi接口
        if(strpos($this->action, 'efast') === 0) {
			if(! isset($this->app ['fmt'])) $this->app ['fmt']='json';
		} else {
			if(! isset($this->app ['fmt'])) $this->app ['fmt']='html';	
		}

		//if(! isset($this->app ['fmt'])) $this->app ['fmt']='html';	// 响应格式,默认格式html,other json,csv
		if(RUN_SAFE===true && $this->is_in_cli()) $this->app ['mode']='cli'; 	//应用类型:cli,web,func -批处理，web应用，func方法.
		if(! isset($this->app ['mode']) || ($this->app ['mode']!='cli' && $this->app ['mode']!='func') )
			 $this->app ['mode']='web';	//默认类型
		$this->wlog=$this->wlog && defined('RUN_WEB_LOG') && RUN_WEB_LOG  && $app['mode']=='web' && $app['fmt']=='html';
						
		$app ['step']='seq';//执行顺序,default seq, 1.seq:squence,2.call:goto call,3.resp:goto response filter,4.rend:goto renderer
							//req:goto request filter  ,return: end//not mean
		$app ['err_no']=0;
		
		if($this->from_index){
			$this->app_script_file=ROOT_PATH . "{$this->app_name}/web/app/{$this->app_path}{$this->app_script}.php";
			if(! file_exists($this->app_script_file)){
					$this->log_error ( "call handle func,file {$this->app_script_file} not found");
					$GLOBALS['context']->put_error(404,lang('req_err_404')."[{$this->app_path}{$this->app_script}.php]");
					$app['step']='rend';					
			}else	include_once $this->app_script_file;
		}		
			
		//call request filter
		if(!isset($app['step']) || ($app['step']=='seq'))
		foreach($this->reqlist as $classid=>$objitem)
		try {
			$obj=$this->new_filter_obj($objitem,$classid,$app,$this->resplist);
			if($obj && $obj instanceof IRequestFilter)
				if($obj->handle_before($request,$response,$app)===true) 	break;	
		} catch ( Exception $e ) {
				$this->log_error ( "call request filter [{$classid}] fail," . $e->getMessage () );
				$GLOBALS['context']->put_error(500,lang('req_err_500'));
		}
		if($app['step']=='return')	 return;
		
		//call handle func
		if(!isset($app['step']) || $app['step']=='seq' || $app['step']=='call')
			$this->do_step_call($request,$response,$app);	
		if($app['step']=='return')	 return;
		
		//call response filter
		if(!isset($app['step']) || $app['step']=='seq' || $app['step']=='resp')
		foreach($this->resplist as $classid=>$objitem)
		try {
			$obj=$this->new_filter_obj($objitem,$classid,$app,$this->reqlist);
			if($obj && $obj instanceof IReponseFilter)
				if($obj->handle_after($request,$response,$app)===true) 	break;				
		} catch ( Exception $e ) {
				$this->log_error ( "call response filter [{$classid}] fail," . $e->getMessage () );
				$GLOBALS['context']->put_error(500,lang('req_err_500'));
		}
		if($app['step']=='return')	 return;
				
		//call render handle
		if(!isset($app['step']) || $app['step']=='seq' || $app['step']=='rend')
		foreach($this->renderlist as $classid=>$objitem)
		try {
			$list=NULL;
			$obj=$this->new_filter_obj($objitem,$classid,$app,$list);
			if($obj && $obj instanceof IReponseRenderer)
				if($obj->render($request,$response,$app)===true) 	break;			
		} catch ( Exception $e ) {
				$this->log_error ( "call response renderer [{$classid}] fail," . $e->getMessage () );
		}		
	}

	/**
	 *  创建对象
	 * @param $objitem
	 * @param $classid
	 * @param $app
	 * @param $list
	 *
	 * @return null
	 */
	private function new_filter_obj($objitem,$classid,&$app,&$list){
		$obj=NULL;
		$need=$objitem->need;   // 回调函数
		if($need===NULL || $need($app)){
			 if(($obj=$objitem->obj)===NULL){
			    include_once ROOT_PATH.$objitem->file;  // 引入文件
			    $class=$objitem->class; // 获取类名
			    $objitem->obj=$obj=new $class(); // 实例化对象
			    if($list && isset($list[$classid])) 		$list[$classid]->obj=$obj;
			 }	
		}
		return $obj;		
	}

	/**
	 * @param $grp
	 * @param $path
	 *
	 * @return null
	 */
	static function get_obj_from_grp($grp,$path){
		$clazz=$grp;
		if(class_exists( $clazz)) return new $clazz ();	//not check is action controller class or file
		else if(strcmp($clazz,'index')===0 && $path){ 
			$len=strlen($path)-1;
			if($path[$len]=='/') $path=substr($path,0,$len);
			$clazz=basename($path);
			if($clazz && class_exists($clazz)) return new $clazz ();
		}
		return 	NULL;
	}

	/**
	 * @param array $request
	 * @param array $response
	 * @param array $app
	 */
	private function do_step_call(array & $request,array & $response,array & $app){
		$class_name  = $app ['grp'];
		$method_name = $app ['act'] ;
		$callback = NULL;
		$obj=self::get_obj_from_grp($class_name,$this->app_path);
		if ($obj) {
			if (method_exists ( $obj, $method_name )) {
				if(RUN_SAFE){
					$func = new ReflectionMethod($class_name,$method_name);
					if(!$func->isPublic() || $func->getFileName() !== str_replace('/',DS,$this->app_script_file)
						|| $func->isInternal() || (PHP_VERSION_ID > 50300 && $func->isClosure()) || $func->isAbstract() || $func->isConstructor() ){
						$this->log_error ( "forbid handle func {$method_name} in {$func->getFileName()} " );
						$GLOBALS['context']->put_error(403,lang('req_err_403').' ['.$method_name.']');						
					}
				}				
				$callback = array ($obj, $method_name );
			}
		} else {
			if (function_exists ( $method_name )){
				if(RUN_SAFE){
					$func = new ReflectionFunction($method_name);
					if($func->getFileName() !== str_replace('/',DS,$this->app_script_file)
						 || $func->isInternal()  || (PHP_VERSION_ID > 50300 && $func->isClosure())  ){
						$this->log_error ( "invalid call handle func {$method_name} in {$func->getFileName()} " );
						$GLOBALS['context']->put_error(403,lang('req_err_403').' ['.$method_name.']');						
					}
				}
				$callback = $method_name;
			}
			else{
				unset($app ['act']);
				unset($app ['grp']);
			} 
		}
		if (! $callback) {
			$this->log_error ( "call handle func {$class_name}[{$method_name}] fail in {$this->action}" );
			$GLOBALS['context']->put_error(501,lang('req_err_501')."[{$this->action}]");
		}		
		//call handle func
		//@ $obj->$method_name ( $request ); //isok ?
		if(!isset($app['step']) || $app['step']=='seq' || $app['step']=='call')
		try {
				if(is_array($callback))
					$result=$callback[0]->$callback[1]( $request,  $response,  $app );
				else
					$result=$callback( $request,  $response,  $app );
		} catch ( Exception $e ) {
				$this->log_error ( "call [{$class_name}->{$method_name}] fail," . $e->getMessage () );
				$GLOBALS['context']->put_error(500,lang('req_err_500'));
		}
	}

	/**
	 *
	 */
	function do_app_init(){
		$app_init='app_init';
		$app_init_file=ROOT_PATH . "/{$this->app_name}/boot/{$app_init}.php";
		if(file_exists($app_init_file)){
			include_once $app_init_file;
			if (function_exists ($app_init))	$app_init();
		}
	}
	//magic property for last include file.
	public function get_property($prop){	//last bind property
		$obj=& $this->objlist[$prop];
		if(! isset($obj)) return NULL;
		
		if(! isset($obj['obj'])){
			require_once ROOT_PATH.$obj['file'];
			
			$class_name=$obj['class'];
			if(class_exists($class_name)){
				try{
					$tmpobj= call_user_func(array($class_name,'register'),$prop);
					if( isset($tmpobj) && is_object($tmpobj) ){
						$obj['obj']=$tmpobj;
						return $tmpobj;
					}
					else
					   $this->log_error("register [{$prop}] to RequestContext fail,register function return null");
				}catch(Exception $e){
					$this->log_error("register [{$prop}] to RequestContext fail,call register function exception ". $e->getMessage());
				}
			}
			return NULL;
		}
		else return $obj['obj'];
	}
	function __get($name){
		return $this->get_property($name);
	}
	function __isset($name){
		return isset($this->objlist[$name]);
	}

	/**
	 * 自定义异常处理方法
	 * @param $errno
	 * @param $errstr
	 * @param $errfile
	 * @param $errline
	 *
	 * @return bool
	 */
	function log_php_error_handler($errno , $errstr , $errfile , $errline){
		if($errno==E_USER_NOTICE){		//module trigger error
			 $this->log_error('USER'.lang('req_log_info') ."[{$errno}] : {$errstr},in file {$errfile}[{$errline}]");
			 return true;
		}else if($errno==E_ERROR || $errno== E_USER_ERROR || $errno==E_CORE_ERROR || $errno==E_COMPILE_ERROR || $errno=E_WARNING)
			$this->log_error('PHP'.lang('req_log_error') ."[{$errno}] : {$errstr},in file {$errfile}[{$errline}]");
		else if($errno != E_STRICT)
			$this->log_debug('PHP'.lang('req_log_debug') ."[{$errno}] : {$errstr},in file {$errfile}[{$errline}]");
		if(DEBUG)	return false;
	}

	/**
	 * @param $e
	 */
	function log_php_exception_handler($e){
		$this->log_error('PHP'.lang('req_log_except'). "[{$e->getCode()}] : {$e->getMessage()},in file {$e->getFile()}[{$e->getLine()}]");
	}

	private $app_conf=NULL;

	/**
	 * @param $_FASTAPP_NAME_a3ap3p4o_8na6me_
	 *
	 * @return mixed
	 */
	function get_app_conf($_FASTAPP_NAME_a3ap3p4o_8na6me_){
		if($this->app_conf===NULL){
			include ROOT_PATH. $this->app_name."/boot/app_conf.php";
			$this->app_conf=get_defined_vars();
			unset($this->app_conf['_FASTAPP_NAME_a3ap3p4o_8na6me_']);
			if(! defined('RUN_FAST') || ! RUN_FAST){
				if(! isset($this->app_conf['base_url']) && isset($_SERVER['PHP_SELF'])){
					$b=str_replace(array('<', '>', '*', '\'', '"'), '', $_SERVER['PHP_SELF']);
					$r=strpos($b,'/app/'); 
					$this->app_conf['base_url']= $r ? substr($b,0,$r+1):'/';	
				}
				if(! isset($this->app_conf['app_url']))	$this->app_conf['app_url']=	$this->app_conf['base_url'];
			}	
		}
		if(isset($this->app_conf[$_FASTAPP_NAME_a3ap3p4o_8na6me_]))
			return $this->app_conf[$_FASTAPP_NAME_a3ap3p4o_8na6me_];
	}

	private  $action_list=array();
	function call_action($actions){
		$action_list=explode(',',$actions);
		foreach ($action_list as $action){
			if(empty($action) || $action==$this->action ) continue;
			$path=$act=$grp=NULL;
			$this->get_path_grp_act($action,$path,$grp,$act);
			if(! $grp)	$grp=$this->app_script;				//default current app settings
			if(! $path)	$path=$this->app_path;
			
			$id=strtolower("{$path}{$grp}");
			if(isset($this->action_list[$id])){
				$obj=$this->action_list[$id];	//only get controller obj
				if (method_exists( $obj, $act )) $obj->$act($this->request,$this->response,$this->app);
				continue;
			}
			if($grp)	include_once ROOT_PATH . "/{$this->app_name}/web/app/{$path}{$grp}.php";
			$obj=self::get_obj_from_grp($grp,$path);
			if ($obj){
				$this->action_list[$id]=$obj;
				if (method_exists( $obj, $act )) $obj->$act($this->request,$this->response,$this->app);
			}else if(function_exists( $act )) $act($this->request,$this->response,$this->app);
		}
	}


	private $sess_init=false;
	function init_session(){
		if(isset($this->app['mode']) && $this->app['mode']==='func'){
			$this->sess_init=false;
			return;
		}
		if($this->sess_init) return;
		if(defined('RUN_SESSPUB') && RUN_SESSPUB){
			session_name('fastappsid');
			$cookie_path='/';
			$cookie_lifetime = $this->conf->get('cookie.alive',3600);
			session_set_cookie_params($cookie_lifetime,$cookie_path);
		}else{
			session_name($this->app_name.'sid');
			if($this->get_app_conf('base_url'))	$cookie_path = $this->get_app_conf('base_url');
			else	$cookie_path = $this->conf->get('cookie.path','/');
			$cookie_lifetime = $this->conf->get('cookie.alive',3600);
			$cookie_domain= $this->conf->get('cookie.domain','');
			if($cookie_domain) session_set_cookie_params($cookie_lifetime,$cookie_path);
			else session_set_cookie_params($cookie_lifetime,$cookie_path,$cookie_domain);	//server time must ok
		}	
		@session_start();	//see:clean_session
		$this->sess_init=true;
	}
	function get_session($name,$pub=false){
		$this->init_session();
		if(! $this->sess_init) return NULL;
		if(! $pub) $name='fAp'.$this->app_name . $name;
		return  isset($_SESSION[$name]) ? $_SESSION[$name] : NULL;
	}
	function set_session($name,$value,$pub=false){
		$this->init_session();
		if(! $this->sess_init) return ;
		if(! $pub) $name='fAp'.$this->app_name . $name;
		$_SESSION[$name]=$value;
	}
	function set_cookie($name,$value,$ttl=NULL,$pub=false){		//see:del_cookie
		if($pub){
			$cookie_path='/';
			$cookie_domain=false;
		}else{
			if($this->get_app_conf('base_url'))	$cookie_path = $this->get_app_conf('base_url');
			else	$cookie_path = $this->conf->get('cookie.path','/');
			$cookie_domain= $this->conf->get('cookie.domain','');
		}	
		if($ttl===NULL) $ttl = $this->conf->get('cookie.alive',1800)+time ()+5;
		else	$ttl = $ttl+time ();
		if($cookie_domain) setcookie ($name, $value,  $ttl, $cookie_path,$cookie_domain);
		else setcookie ($name, $value,  $ttl, $cookie_path );				
	}

	function forward($action){
		if(empty($action)  || $action==$this->action ) return;
		$path=$grp=$act=NULL;
		$this->get_path_grp_act($action,$path,$grp,$act);
		$this->app['act']=$act;
		if($grp){
			$this->app_script=$this->app['grp']=$grp;			//default current settings;
			$this->app_path=$this->app['path']=$path;
			include_once ROOT_PATH . "/{$this->app_name}/web/app/{$this->app_path}{$this->app_script}.php";	
		}
		$this->action=$action;
		$this->do_step_call($this->request,$this->response,$this->app);	
	}
	function redirect($action,$options=NULL,$relay=0,$relay_msg=''){
		$url=get_app_url($action,$options);
		if(! headers_sent()){
			if($relay>0){
				header('Content-Type: text/html;charset='.$this->get_app_conf('charset'));
				header("Refresh:{$relay};url={$url}"); 
				if($relay_msg)	echo $relay_msg;
				else echo lang('req_redirect_msg');
			} 
			else  header("Location:{$url}");
			exit;
		}else{
			$reply    = "<meta http-equiv='Refresh' content='{$relay};url={$url}'>";
        	if($relay>0)  $reply   .=   $relay_msg;			
			exit($reply);
		}
	}
	//优化应用性能，请主要使用下面log方法
	private $_log;
	function log_error($msg){
		if(! isset($this->_log)) $this->_log=$this->get_property('log');
		$this->_log->error($msg);	
		if($this->wlog && $GLOBALS['app_debug'])	$this->wlog_text .= $msg."\n";
	}
	function is_debug(){ return $GLOBALS['app_debug']; }
	function log_debug($msg){
		if(! $GLOBALS['app_debug']) return;
		if(! isset($this->_log)) $this->_log=$this->get_property('log');
		$this->_log->debug($msg);	
		if($this->wlog)	$this->wlog_text .= $msg."\n";
	}	
	function put_wlog(){
		if($this->wlog && $GLOBALS['app_debug'])	echo "\n<br/><b>web log:</b><pre>".$this->wlog_text."</pre>\n";
	}
	function put_error($errno,$errmsg){
		if(! $this->app['err_no'])	$this->app['err_no']=$errno;
		if(isset($this->app['err_msg']) && $this->app['err_msg'])	$this->app['err_msg'] .= " ; " . $errmsg ;
		else $this->app['err_msg']=$errmsg;
		$this->app['step']='rend';	
		$this->log_error(lang('req_err_title'). "[{$errno}],{$errmsg}");	
	}
	//以下实现singleton
	private static $context;
	private function __construct(){
		$this->from_index=defined('RUN_FROM_INDEX') && RUN_FROM_INDEX; 
		if(self::$context) throw new Exception("RequestContext CANNOT create! use 'instance' method");
	}
	public static function instance(){
		if(! isset(self::$context))	self::$context=new RequestContext();
		return self::$context;
	}	
}
//public function
function try_class_file_ext($fname,$ext='class'){
	if(file_exists($fname.'.'.$ext.'.php')) return	'.'.$ext.'.php';
	else if(file_exists($fname.'.php')) return '.php';
	return false;
}

/**
 * @param      $clazzs
 * @param bool $first_app
 */
function require_lib($clazzs,$first_app=RUN_LIB_FIRST_APP){
	global $context;
	$clazzlist=explode(',',$clazzs);
	if(! isset($context->_require_lib_list)) {
              $context->_require_lib_list=array();
        }

	foreach ($clazzlist as $clazz){
		$clazz=trim($clazz);
		$clsid=strtolower($clazz);
		if(in_array($clsid,$context->_require_lib_list)) continue;
		if($first_app){	//from app lib dir
			$clazz_file=ROOT_PATH. $context->app_name ."/lib/{$clazz}";
			$ext=try_class_file_ext($clazz_file);
			if(! $ext){
				$clazz_file=ROOT_PATH. "lib/{$clazz}";
				$ext=try_class_file_ext($clazz_file);
				if(! $ext)	$context->log_error('load_lib fail,['.$clazz_file .'.php] not found');
			}
		}else{	//from root lib dir
			$clazz_file=ROOT_PATH. "lib/{$clazz}";
			$ext=try_class_file_ext($clazz_file);
			if(! $ext){
				$clazz_file=ROOT_PATH. $context->app_name ."/lib/{$clazz}";
				$ext=try_class_file_ext($clazz_file);
				if(! $ext)	$context->log_error('load_lib fail,['.$clazz_file .'.php] not found');
			}
		}
		if($ext){
			$context->_require_lib_list[]=$clsid;
			require_once $clazz_file.$ext;
		}
	}
}

function require_model($clazzs,$first_app=RUN_MDL_FIRST_APP){

	global $context;
	$clazzlist=explode(',',$clazzs);
	if(! isset($context->_require_model_list)){
		$context->_require_model_list=array();
		require_once ROOT_PATH. "lib/models/AbstractModel.mdl.php";
	} 
	foreach ($clazzlist as $clazz){
		$clazz=trim($clazz);
		$clsid=strtolower($clazz);
		if(in_array($clsid,$context->_require_model_list)) continue;
		if($first_app){	//from app model dir
			$clazz_file=ROOT_PATH.$context->app_name ."/models/{$clazz}";
			$ext=try_class_file_ext($clazz_file,'mdl');
			if(! $ext){
				$clazz_file=ROOT_PATH. "lib/models/{$clazz}";
				$ext=try_class_file_ext($clazz_file,'mdl');
				if(! $ext)	$context->log_error('load_model fail,['.$clazz_file .'.php] not found');
			}
		}else{	//from root model dir
			$clazz_file=ROOT_PATH. "lib/models/{$clazz}";
			$ext=try_class_file_ext($clazz_file,'mdl');
			if(! $ext){
				$clazz_file=ROOT_PATH.$context->app_name ."/models/{$clazz}";
				$ext=try_class_file_ext($clazz_file,'mdl');
				if(! $ext)	$context->log_error('load_model fail,['.$clazz_file .'.php] not found');
			}			
		}
		if($ext){
			$context->_require_model_list[]=$clsid;
			require_once $clazz_file.$ext;	
		}
	}
}

function require_moudle($clazzs){
	
	global $context;
	$clazzlist=explode(',',$clazzs);
	if(! isset($context->_require_moudle_list)){
		$context->_require_moudle_list=array();
		require_once ROOT_PATH. "lib/models/base_model.php";
	} 
	
	foreach ($clazzlist as $clazz)
	{
		$clazz=trim($clazz);
		$clsid=strtolower($clazz);
		if(isset($GLOBALS['_require_moudle_list']) && is_array($GLOBALS['_require_moudle_list'])
			&& in_array($clsid,$GLOBALS['_require_moudle_list'])) continue;

		$clazz_file=ROOT_PATH."../moudle/{$clazz}";
		$ext=try_class_file_ext($clazz_file,'mdl');
		if(! $ext){
			$context->log_error('load_moudle fail,['.$clazz_file .'.php] not found');
		}
		if($ext){
			$context->_require_moudle_list[]=$clsid;
			require_once $clazz_file.$ext;
		}
	}
}

	

function require_lang($pkgs,$first_app=RUN_LANG_FIRST_APP){
	global $context;
	if(! isset($context->_require_model_list)) $context->_require_lang_list=array();
	$pkgs=explode(',',$pkgs);
	$clazz_files=array();
	foreach($pkgs as $pkg){
		$pkg=trim($pkg);
		$pkgid=strtolower($pkg);
		if(in_array($pkgid,$context->_require_lang_list)) continue; 
		if($first_app){
			$clazz_file=ROOT_PATH."{$context->app_name}/lang/{$context->app_lang}/{$pkg}.php";
			if(! file_exists($clazz_file)) $clazz_file=ROOT_PATH. "lib/lang/{$context->app_lang}/{$pkg}.php";
		}else{
			$clazz_file=ROOT_PATH. "lib/lang/{$context->app_lang}/{$pkg}.php";
			if(! file_exists($clazz_file)) $clazz_file=ROOT_PATH."{$context->app_name}/lang/{$context->app_lang}/{$pkg}.php";
		}
		$clazz_files[]=	$clazz_file;
	}
	foreach($clazz_files as $clazz_file){
		if(file_exists($clazz_file)){
			include $clazz_file;
			$context->lang=array_merge($context->lang,$$pkg);
			$context->_require_lang_list[]=$pkgid;
		}else $context->log_error("require_lang fail,language file [{$clazz_file}] NOT FOUND");
	}	
}
function lang($key){
	global $context;
	if(isset($context->lang[$key]))	return $context->lang[$key];
	else{	
		//$context->log_error("language key [{$key}] NOT FOUND");
		return $key;
	}
}
function get_tpl_path($tplname) {
	global $context;
	$views_path=ROOT_PATH . $context->app_name . DS.'views'. DS ;
	if($context->theme) $views_path.=$context->theme .DS ;
	if( defined('RUN_MLANG_VIEW') && RUN_MLANG_VIEW) $views_path.=$context->app_lang .DS ;
	if('/'!==DS) $tplname=str_replace('/',DS,$tplname);
	return $views_path.$tplname.'.tpl.php';
}

function get_url($url_part,$is_pub=false,$is_theme=false,$mutil_lang=true){
	$url_part=trim($url_part);
	global $context;
	$ver=$context->get_app_conf('url_version');
	if(isset($ver) && $ver===1){
		if (! isset ( $context->_url_ver )) {
			$file = ROOT_PATH . $context->app_name . "/boot/url_ver.php";
			if (file_exists ( $file )) {
				include $file;
				$context->_url_ver = isset ( $url_var ) && $url_var ? $url_var : NULL;
			}else $context->_url_ver =NULL;
		}
		if($context->_url_ver){
			$file=basename($url_part);
			if(isset($context->_url_ver[$file])) $url_part .="?f={$context->_url_ver[$file]}";
		}
	}
	if($is_pub) $prefix= $GLOBALS['context']->get_app_conf('pub_url');
	else $prefix= $GLOBALS['context']->get_app_conf('base_url');
	if($is_theme){
		$prefix .= "theme/{$GLOBALS['context']->theme}/";
		if($mutil_lang && defined('RUN_MLANG_VIEW') && RUN_MLANG_VIEW) $prefix .= $context->app_lang .'/'; 
	}
	return $prefix .$url_part;
}
function echo_url($url_part,$is_pub=false,$is_theme=false,$mutil_lang=true){
	echo get_url($url_part,$is_pub,$is_theme,$mutil_lang);
}
function get_theme_url($url_part,$mutil_lang=true){
	return get_url($url_part,false,true,$mutil_lang);
}
function echo_theme_url($url_part,$mutil_lang=true){
	echo_url($url_part,false,true,$mutil_lang);
}

function get_app_url($action,$options=NULL,$is_control=false){
	$ctx=$GLOBALS['context'];
	if($is_control){
		if(! defined('RUN_SAFE') || RUN_SAFE) $action = preg_replace('/[^a-z0-9_\/]+/i', '', $action);
		if($ctx->from_index)	$loc=$ctx->get_app_conf('base_url')."?app_act=ctl/index/do_index&app_ctl={$action}";
		else  $loc=$ctx->get_app_conf('base_url')."app/ctl/?app_ctl={$action}";			
	}else{
	
		if($ctx->from_index){
			if(! defined('RUN_SAFE') || RUN_SAFE) $action = preg_replace('/[^a-z0-9_\/]+/i', '', $action);
			if($action[strlen($action)-1]==='/') $action .='do_index';
			$loc=$ctx->get_app_conf('base_url')."?app_act={$action}";
		}else{
			$path=$grp=$act=NULL;
			if($action[strlen($action)-1]==='/') $action .='do_index';
			$ctx->get_path_grp_act($action,$path,$grp,$act);
			if(! $act) 	$ctx->put_error(1500,"function get_app_url error:grp not found,action param is ".$action);
			if(! $grp) {
				$grp=$ctx->app_script;
				$path=$ctx->app_path;
			}			
			$loc=$ctx->get_app_conf('base_url')."app/{$path}{$grp}." . $ctx->get_app_conf('php_ext');
			if($act) $loc .="?app_act={$act}";
		}  
	}
	if($options && is_array($options))
		foreach($options as $key=>$val)			$loc .='&'. trim($key) .'=' . trim($val);
	else {
		$options=trim($options);
		if(strlen($options)>0) $loc .='&' . $options; 
	}
	return 	$loc;	
}

function echo_app_url($action,$options=NULL,$is_control=false){
	echo get_app_url($action,$options,$is_control);
}

// 实例化对象
$context=RequestContext::instance();
$context->prepare_request_handle();   // 初始化请求


function CTX(){return $GLOBALS['context'];}		//shortcut for $context
function call_action($actions){ return $GLOBALS['context']->call_action( $actions );}   //shortcut for $context->call_action

// 注册工具使用 register tool,filter,render;init app run mode and const.
if(file_exists(ROOT_PATH.$context->app_name.'/boot/app_reg.php'))	//webapp private register
	include ROOT_PATH.$context->app_name.'/boot/app_reg.php';
else	include ROOT_PATH.'boot/req_reg.php';

if(! defined('DEBUG')) define('DEBUG',false);
if(! defined('RUN_SAFE')) define('RUN_SAFE',true);
if(! defined('RUN_FAST')) define('RUN_FAST',false);
$app_debug=defined('DEBUG') && DEBUG;
if(! defined('RUN_FAST') || !  RUN_FAST){
	date_default_timezone_set('Asia/Shanghai');	//设置时区
	if(DEBUG){
		@ini_set('display_errors',1); 
		error_reporting(E_ALL);
	}	
	else{
		@ini_set('display_errors',0); 
		error_reporting(E_ALL & ~(E_STRICT|E_NOTICE|E_WARNING));
	} 
} 
//app common init 
$context->do_app_init();


