<?php

require_once(dirname(__FILE__).'/../OpenAPIOperatingBase.php');

class BizTaobao extends OpenAPIOperatingBase
{
	//参数设置
	private $params = array();

	protected $_type = 'taobao';

	function __construct()
	{
		parent::__construct();

		$this->params['app_fmt'] = 'json';
		$this->params['app_act'] = 'taobao_api';
	}

	/**
	 * 淘宝业务统一调用，可识别错误信息
	 * @param unknown_type $params
	 */
	function invoke($params,$retry = true)
	{
		$this->clear_error();

		$ret = $this->execute($params);

		if(isset($ret['resp_error']) && !empty($ret['resp_error']))
		{
			if($retry)
				return $this->invoke($params,false);

			return $this->put_error(-1,'framework is error.');
		}

		if(!isset($ret['resp_data']) && empty($ret['resp_data']))
		{
			if($retry)
				return $this->invoke($params,false);

			return $this->put_error(-1,$params['app_act'].' error.');
		}

		$ret = $ret['resp_data'];

		$date = date("Y-m-d H:i:s");
		error_log("\n--res[{$date}]--\n".var_export($ret,true) . "--invoke--END--\n", 3, "/Applications/XAMPP/xamppfiles/htdocs/web/camel/efast_wms/log/curl_log.log");

		/*
		if($this->is_err($ret))
			return FALSE;
		*/

		return $ret;
	}

	/**
	 * sku库存同步
	 * Enter description here ...
	 * @param unknown_type $sd_id
	 * @param unknown_type $sku
	 * @param unknown_type $num
	 */
	function update_sku_inventory($sd_id, $sku, $num,$checked)
	{
		$params = $this->params;
		$params['app_act'] .= '/item_quantity_sync';
		$params['sd_id'] = $sd_id;
		$params['sku'] = $sku;
		$params['num'] = $num;
		$params['checked'] = $checked;
		//加系统日志
		/*
		$log_arr = $this->log_params;
		$log_arr['log_info']= "sku:{$sku}";
		$this->_log->add_update_inventory_log($log_arr,$this->_type);
		*/

		return $this->invoke($params);
	}

	function test()
	{
		$params = $this->params;
		$params['app_act'] .= '/item_quantity_sync';   // taobao_api/test
		$ret = $this->invoke($params);

		$date = date("Y-m-d H:i:s");
		error_log("\n--res[{$date}]--\n".var_export($ret,true) . "--test()--END--\n", 3, "/Applications/XAMPP/xamppfiles/htdocs/web/camel/efast_wms/log/curl_log.log");

		return $ret;
	}


}

?>
