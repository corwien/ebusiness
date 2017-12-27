<?php

abstract class Config implements IConfig
{
	protected $data;
	public $app_name;
	public $default_ttl = 7200;
	public $cat_prefix = "req_conf_";
	public $cat_default = "app";

	public function readData($cat, $group, $var = NULL)
	{
		$conf = $GLOBALS["context"]->db->create_mapper($this->cat_prefix . $cat);
		$conf->cols("key_var,key_name,value,ttl")->where_col("key_group", $group)->order("key_index,id");

		if ($var == NULL) {
			$conf->order("key_var", true, false);
		}
		else {
			$conf->where_col("key_var", $var);
		}

		return $conf->find_all_by();
	}

	public function get($key, $default = NULL)
	{
		if (($this->data === NULL) || !$key) {
			return $default;
		}

		$cat = $group = $var = $name = NULL;
		$this->get_name($key, $cat, $group, $var, $name);
		$result = $this->get_group_var($cat, $group, $var);
		if ($result && isset($result[$name])) {
			return $result[$name];
		}
		else {
			return $default;
		}
	}

	public function get_var($varkey)
	{
		if (($this->data === NULL) || !$varkey) {
			return NULL;
		}

		$cat = $group = $var = $name = NULL;
		$varkey .= ".null";
		$this->get_name($varkey, $cat, $group, $var, $name);
		return $this->get_group_var($cat, $group, $var);
	}

	public function get_name($key, &$catlog, &$group, &$var, &$name)
	{
		$gvn = explode(".", $key);

		if (count($gvn) <= 1) {
			$group = $var = $name = $key;
			$catlog = $this->cat_default;
		}
		else if (count($gvn) == 2) {
			$group = $var = $gvn[0];
			$name = $gvn[1];
			$catlog = $this->cat_default;
		}
		else if (count($gvn) == 3) {
			$group = $gvn[0];
			$var = $gvn[1];
			$name = $gvn[2];
			$catlog = $this->cat_default;
		}
		else {
			$catlog = $gvn[0];
			$group = $gvn[1];
			$var = $gvn[2];
			$name = $gvn[3];
		}
	}

	abstract public function get_group_var($cat, $group, $var);
}

class EmptyCacheConfig extends Config
{
	public function __construct()
	{
		$this->data = array();
	}

	static public function register($prop)
	{
		return new EmptyCacheConfig();
	}

	public function get_group_var($cat, $group, $var)
	{
		$group_var = "$cat.$group.$var";

		if (isset($this->data[$group_var])) {
			return $this->data[$group_var];
		}

		$data = $this->readData($cat, $group, $var);
		$vardata = array();

		foreach ($data as $row ) {
			$key_name = $row["key_name"];
			$val_cur = $row["value"];
			$vardata[$key_name] = $val_cur;
		}

		$this->data[$group_var] = $vardata;
		return $vardata;
	}

	public function clearCache($key)
	{
	}
}

class FileConfig extends Config
{
	private $conf = "cache/conf";
	private $conf_path;

	public function __construct()
	{
		if (isset($GLOBALS["context"]->app_name)) {
			$this->app_name = $GLOBALS["context"]->app_name;
		}

		$this->conf_path = ROOT_PATH . "$this->app_name/$this->conf/";

		if (!file_exists($this->conf_path)) {
			mkdir($this->conf_path, 511, true);
		}

		if (file_exists($this->conf_path)) {
			$this->data = array();
		}
		else {
			$this->data = NULL;
		}
	}

	static public function register($prop)
	{
		$GLOBALS["context"]->log_debug("FileConfig create");
		return new FileConfig();
	}

	public function get_group_var($cat, $group, $var)
	{
		if ($this->data === NULL) {
			return NULL;
		}

		$group_var = "$cat.$group.$var";

		if (isset($this->data[$group_var])) {
			return $this->data[$group_var];
		}

		$conf_file = $this->conf_path . "$cat.$group.php";

		if (file_exists($conf_file)) {
			$$var = NULL;
			$_the_file_ttl = NULL;
			include ($conf_file);
			$data = $$var;
			if ($data && $_the_file_ttl && (time() < $_the_file_ttl)) {
				$this->data[$group_var] = &$data;
				return $data;
			}

			unlink($conf_file);
		}

		$lines = "<?php \n if (!defined('ROOT_PATH')) die('401,未授权访问 [Unauthorized]');\n";
		$data = $this->readData($cat, $group);
		$ttl_min = NULL;
		$i = 0;
		$key_var_old = "";
		$vardata = array();

		foreach ($data as $row ) {
			$key_name = $row["key_name"];
			$key_var = $row["key_var"];
			$val_cur = $row["value"];

			if (!empty($row["ttl"])) {
				$ttl_min = ($ttl_min == NULL ? $row["ttl"] : min($ttl_min, $row["ttl"]));
			}

			$group_var_cur = "$cat.$group.$key_var";

			if ($i++ == 0) {
				$key_var_old = $key_var;
			}

			$vardata[$key_name] = $val_cur;

			if ($key_var_old != $key_var) {
				$this->data[$group_var_cur] = $vardata;
				$lines = $lines . "\$" . $var . "=" . var_export($vardata, true) . ";\n";
				$key_var_old = $key_var;
				$vardata = array();
				$i = 0;
			}
		}

		if (0 < $i) {
			$this->data[$group_var_cur] = $vardata;
			$lines = $lines . "\$" . $var . "=" . var_export($vardata, true) . ";\n";
		}

		if ($lines) {
			if ($ttl_min == NULL) {
				$ttl_min = $this->default_ttl;
			}

			$lines .= "\$_the_file_ttl=" . ($ttl_min + time()) . ";";
			file_put_contents($conf_file, $lines);
		}

		return $vardata;
	}

	public function clearCache($key)
	{
		if ($this->data === NULL) {
			return NULL;
		}

		$cat = $group = $var = $name = NULL;
		$this->get_name($key, $cat, $group, $var, $name);
		$conf_file = $this->conf_path . "$cat.$group.php";

		if (file_exists($conf_file)) {
			unlink($conf_file);
		}
	}
}

class ApcConfig extends Config
{
	public function __construct()
	{
		if (extension_loaded("apc")) {
			$this->data = array();
		}
		else {
			$this->data = NULL;
		}

		if (isset($GLOBALS["context"]->app_name)) {
			$this->app_name = $GLOBALS["context"]->app_name;
		}
	}

	static public function register($prop)
	{
		if (extension_loaded("apc")) {
			$GLOBALS["context"]->log_debug("ApcConfig create");
			return new ApcConfig();
		}
		else {
			return FileConfig::register($prop);
		}
	}

	public function get_group_var($cat, $group, $var)
	{
		if ($this->data === NULL) {
			return NULL;
		}

		$group_var = "$cat.$group.$var";

		if (isset($this->data[$group_var])) {
			return $this->data[$group_var];
		}

		$var_key = "cf$this->app_name.$group_var";
		$exists = false;
		$data = apc_fetch($var_key, $exists);
		if (($exists === true) && $data) {
			$this->data[$group_var] = &$data;
			return $data;
		}

		$data = $this->readData($cat, $group, $var);
		$vardata = array();
		$ttl_min = NULL;

		foreach ($data as $row ) {
			$key_name = $row["key_name"];
			$key_var = $row["key_var"];
			$val_cur = $row["value"];

			if (!empty($row["ttl"])) {
				$ttl_min = ($ttl_min == NULL ? $row["ttl"] : min($ttl_min, $row["ttl"]));
			}

			$vardata[$key_name] = $val_cur;
		}

		if (0 < count($vardata)) {
			if ($ttl_min == NULL) {
				$ttl_min = $this->default_ttl;
			}

			apc_store($var_key, $vardata, $ttl_min);
			$this->data[$group_var] = $vardata;
		}

		return $vardata;
	}

	public function clearCache($key)
	{
		if ($this->data === NULL) {
			return NULL;
		}

		$cat = $group = $var = $name = NULL;
		$this->get_name($key, $cat, $group, $var, $name);
		apc_delete("cf$this->app_name.$cat.$group.$var");
	}
}

class MemCacheConfig extends Config
{
	private $memcache;

	public function __construct()
	{
		if (extension_loaded("memcache")) {
			$this->data = array();
		}
		else {
			$this->data = NULL;
		}

		$this->memcache = NULL;

		if (isset($GLOBALS["context"]->app_name)) {
			$this->app_name = $GLOBALS["context"]->app_name;
		}
	}

	static public function register($prop)
	{
		if (extension_loaded("memcache")) {
			$GLOBALS["context"]->log_debug("MemCacheConfig create");
			return new MemCacheConfig();
		}
		else {
			return FileConfig::register($prop);
		}
	}

	private function connect()
	{
		if ($this->memcache === NULL) {
			$app_conf_memcache_host = $GLOBALS["context"]->get_app_conf("memcache_host");

			if (!$app_conf_memcache_host) {
				$this->data = NULL;
				return NULL;
			}

			$GLOBALS["context"]->log_debug("MemCacheConfig connect");
			$this->memcache = new Memcache();

			foreach ($app_conf_memcache_host as $host ) {
				$this->memcache->addServer($host);
			}
		}
	}

	public function get_group_var($cat, $group, $var)
	{
		if ($this->data === NULL) {
			return NULL;
		}

		$group_var = "$cat.$group.$var";

		if (isset($this->data[$group_var])) {
			return $this->data[$group_var];
		}

		$var_key = "cf$this->app_name.$group_var";
		$this->connect();
		$data = $this->memcache->get($var_key);

		if ($data !== false) {
			$this->data[$group_var] = &$data;
			return $data;
		}

		$data = $this->readData($cat, $group, $var);
		$vardata = array();
		$ttl_min = NULL;

		foreach ($data as $row ) {
			$key_name = $row["key_name"];
			$key_var = $row["key_var"];
			$val_cur = $row["value"];

			if (!empty($row["ttl"])) {
				$ttl_min = ($ttl_min == NULL ? $row["ttl"] : min($ttl_min, $row["ttl"]));
			}

			$vardata[$key_name] = $val_cur;
		}

		if (0 < count($vardata)) {
			if ($ttl_min == NULL) {
				$ttl_min = $this->default_ttl;
			}

			$this->memcache->set($var_key, $vardata, 0, $ttl_min);
			$this->data[$group_var] = $vardata;
		}

		return $vardata;
	}

	public function clearCache($key)
	{
		if ($this->data === NULL) {
			return NULL;
		}

		$cat = $group = $var = $name = NULL;
		$this->get_name($key, $cat, $group, $var, $name);
		$this->connect();
		$this->memcache->delete("cf$this->app_name.$cat.$group.$var");
	}
}

require_once (ROOT_PATH . "boot/req_inc.php");

?>
