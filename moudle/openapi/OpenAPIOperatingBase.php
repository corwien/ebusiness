<?php

require_once(dirname(__FILE__).'/../base_model.php');
class OpenAPIOperatingBase extends Base_model
{
	public $openapi_url = '';
	public $exist_sdk_file = NULL;
	function __construct()
	{
        parent::__construct();
        $this->openapi_url = 'http://localhost/web/camel/efast_wms/efast_api/webservice/index.php';
	}

	/*执行*/
	public function execute($param)
	{

		$result = $this->curlPost($this->openapi_url, $param,'json');

		//去除垃圾信息({"resp_)
		$pos = strpos($result,'{"resp_');
		if ($pos == false) {

		} else {
			$result = substr($result,$pos);
		}
		$date = date("Y-m-d H:i:s");
		error_log("\n--res[{$date}]--\n".var_export($result,true) . "--execute--END--\n", 3, "/Applications/XAMPP/xamppfiles/htdocs/web/camel/efast_wms/log/curl_log.log");
		return json_decode($result,true);
	}

	/**
     * @Action   Curl Post请求
     * @Param    $url    访问url
     *           $param  数据或xml字符串
     *           $type   类型：json/xml
     * @Return   json ,xml
     */
	public function curlPost($url, $param, $type = 'json')
    {
		$date = date("Y-m-d H:i:s");
		error_log("\n--param[{$date}]--\n".var_export($param,true), 3, "/Applications/XAMPP/xamppfiles/htdocs/web/camel/efast_wms/log/curl_log.log");
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS,$param);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($ch);
        curl_close($ch);
		error_log("\n--res[{$date}]--\n".var_export($result,true) . "--res--END--\n", 3, "/Applications/XAMPP/xamppfiles/htdocs/web/camel/efast_wms/log/curl_log.log");
        return $result;
    }

    
    /**
     * @Action   Curl Post请求
     * @Param    $url    访问url
     *           $param  数据或xml字符串
     *           $type   类型：json/xml 支持多维数组
     * @Return   json ,xml
     */
	public function mengdian_curlPost($url, $param, $type = 'json')
    {
		//error_log("\n--url##--\n".var_export($url,true),3,"E:/baisonwork/stand_efast_bata/data/jd.log");
		//error_log("\n--param##--\n".var_export($param,true),3,"E:/data/jd.log");
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS,http_build_query($param));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }
    
	public function soap_post($url,$param)
    {
				//error_log("\n--url##--\n".var_export($url,true),3,"E:/baisonwork/stand_efast_bata/data/jd.log");
				//error_log("\n--param##--\n".var_export($param,true),3,"E:/baisonwork/stand_efast_bata/data/jd.log");
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
				curl_setopt($ch, CURLOPT_POSTFIELDS,$param);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_HTTPHEADER,array("Content-Type:text/xml;charset=UTF-8"));
        $result = curl_exec($ch);
        curl_close($ch);
				//error_log("\n--result##--\n".var_export($result,true),3,"E:/baisonwork/stand_efast_bata/data/jd.log");
        return $result;
    }

	/**
	 * @Action    调试打印
	 * @Param     $var      需要打印的值
	 *            $method   需要打印的方式
	 *            $exit     是否停止程序继续执行
	 * @Return    void
	 */
	function debug($var, $method = true, $exit = false)
	{
		echo ' <pre>';
		$method ? print_r($var) : var_dump($var);
		echo '</pre> ';
		if ($exit) {
			exit;
		}
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


	/**
     * @Action   Curl Post请求
     * @Param    $url    访问url
     *           $param  数据或xml字符串
     *           $type   类型：json/xml
     * @Return   json ,xml
     */
	public function curlSend($param)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->openapi_url);
        curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS,$param);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }
}

