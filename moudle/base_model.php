<?php

define('EFAST_SYSTEM_ROOT', str_replace('moudle/base_model.php', '', str_replace('\\', '/', __FILE__)));

class Base_model {

	/*
	 * 在构造函数中初始化
	 */
	protected $db = NULL;

	/**
	 * @var int $err_code 错误码，0：没有错误，默认0
	 */
	public $err_code = 0;

	/**
	 * @var string $err_msg 错误提示
	 */
	public $err_msg = '';

	//系统日志
	protected $log_params = array();
	protected $_log = null;

	function __construct() {

		$this->db = $GLOBALS['db'];
		@$this->db_r = $GLOBALS['db_r'];

		@$this->DbApi = $GLOBALS['DbApi'];  //双十一分离淘宝库

	}

	/**
	 * 设置错误码，错误提示
	 * @param int	$err_code	 错误码，0：没有错误
	 * @param string	$err_msg	错误提示
	 */
	function put_error ( $err_code, $err_msg, $return_err = FALSE ) {
		$this->err_code = $err_code;
		$this->err_msg = $err_msg;

		if($return_err)
			return $this->get_error();

		return FALSE;
	}

	/**
	 * 得到错误码、错误提示组成的数组
	 * array( "code"=>$this->err_code, "msg"=>$this->err_msg );
	 */
	function get_error () {
		return array ( "code" => $this->err_code, "msg" => $this->err_msg );
	}

	/**
	 * 错误继承
	 * @param unknown_type $error
	 */
	function extend_error($error)
	{
		$this->set_error($error['code'], $error['msg']);
		return FALSE;
	}

	/**
	 * 是否有错误
	 */
	function is_err ($ret = NULL)
	{
		if(isset($ret['code']) && !empty($ret['code']))
		{
			$this->err_code = $ret['code'];
			$this->err_msg = $ret['msg'];

			return TRUE;
		}
		else
		{
			return $this->err_code !== 0;
		}
	}

	function set_error($err_code,$err_msg)
	{
		$this->err_code = $err_code;
		$this->err_msg = $err_msg;

		return $this->get_error();
	}

	/**
	 * 是否没有错误
	 */
	function is_ok ()
	{
		return $this->err_code === 0;
	}

	function clear_error()
	{
		$this->err_code = 0;
		$this->err_msg = NULL;
	}

	/**
	 * 日志记录,按月生成
	 * @param string $msg
	 */
	function write_log($msg,$type='error')
	{
		$dir = EFAST_SYSTEM_ROOT."temp/log/";

		if (!file_exists($dir))
		{
			mkdir($dir, 0777);
		}

		$filepath = $dir.$type."_log_".date("Ym");

		$message = "[".date('Y-m-d H:i:s')."]\t{$msg}\n";

		//flock ( $fp, LOCK_EX)

		file_put_contents($filepath,$message,FILE_APPEND|LOCK_EX);
	}

	/**
	 * @Action   循环把对象转化成数组
	 * @Param    $e   对象
	 * @Return   array
	 */
	function objectToArray($e)
	{
		$e = (array )$e;
		foreach ($e as $k => $v) {
			if (gettype($v) == 'resource')
				return;
			if (gettype($v) == 'object' || gettype($v) == 'array')
				$e[$k] = (array )$this->objectToArray($v);
		}
		return $e;
	}
}

class Enum_log_type
{
	public static $ERROR = 'error';
	public static $ACTRION = 'action';
	public static $DEBUG = 'debug';
}
