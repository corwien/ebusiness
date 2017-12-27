<?php

class Cache implements ICache
{
	public $default_ttl = 300;

	public function __construct()
	{
		$t = $GLOBALS["context"]->get_app_conf("default_ttl");

		if ($t) {
			$this->default_ttl = $t;
		}
	}
}

class EmptyCache implements IRequestTool
{
	static public function register($prop)
	{
		$GLOBALS["context"]->log_debug("EmptyCache create");
		return new EmptyCache();
	}

	public function get($key)
	{
		return NULL;
	}

	public function set($key, $value, $ttl = NULL)
	{
	}

	public function delete($key)
	{
	}

	public function clear()
	{
	}
}

class FileCache extends Cache implements IRequestTool
{
	private $cache = "cache/data";
	private $cache_path;
	private $enable = false;

	static public function register($prop)
	{
		$GLOBALS["context"]->log_debug("FileCache create");
		return new FileCache();
	}

	public function get($key)
	{
		if (!$this->enable) {
			return NULL;
		}

		$file_path = $this->cache_path . $key . ".php";

		if (DS === "\\") {
			$file_path = str_replace("/", DS, $file_path);
		}

		if (!file_exists($file_path)) {
			return NULL;
		}

		$data = NULL;
		$_the_file_ttl = NULL;
		include ($file_path);
		if (($data !== NULL) && $_the_file_ttl && (time() < $_the_file_ttl)) {
			return $data;
		}

		unlink($file_path);
		return NULL;
	}

	public function set($key, $value, $ttl = NULL)
	{
		if (!$this->enable) {
			return false;
		}

		if (!$ttl) {
			$ttl = $this->default_ttl;
		}

		$rpos = strrpos($key, "/");

		if ($rpos !== false) {
			$key_path = $this->cache_path . substr($key, 0, $rpos);

			if (!file_exists($key_path)) {
				$GLOBALS["context"]->log_debug("mkdir data cache :" . $key_path);
				mkdir($key_path, 511, true);
			}
		}

		$file_path = $this->cache_path . $key . ".php";
		$lines = "<?php \n if (!defined('ROOT_PATH')) die('401,未授权访问 [Unauthorized]');\n";
		$lines = $lines . "\$data=" . var_export($value, true) . ";\n";
		$lines .= "\$_the_file_ttl=" . ($ttl + time()) . ";";
		file_put_contents($file_path, $lines);
		return true;
	}

	public function delete($key)
	{
		if (!$this->enable) {
			return false;
		}

		$file_path = $this->cache_path . $key . ".php";
		clearstatcache();

		if (file_exists($file_path)) {
			unlink($file_path);
		}
	}

	public function clear()
	{
		if (!$this->enable) {
			return false;
		}

		require_lib("util/file_util", true);
		@rmdir_r($this->cache_path);
	}
}

class ApcCache extends Cache implements IRequestTool
{
	private $enable = false;

	static public function register($prop)
	{
		if (extension_loaded("apc")) {
			$GLOBALS["context"]->log_debug("ApcCache create");
			return new ApcCache();
		}
		else {
			return FileCache::register($prop);
		}
	}

	public function get($key)
	{
		if (!$this->enable) {
			return NULL;
		}

		$exists = false;
		$data = apc_fetch($key, $exists);

		if (!$exists) {
			return NULL;
		}
		else {
			return $data;
		}
	}

	public function set($key, $value, $ttl = NULL)
	{
		if (!$this->enable) {
			return false;
		}

		if (!$ttl) {
			$ttl = $this->default_ttl;
		}

		apc_store($key, $value, $ttl);
	}

	public function delete($key)
	{
		if (!$this->enable) {
			return false;
		}

		apc_delete($key);
	}

	public function clear()
	{
		if (!$this->enable) {
			return false;
		}

		apc_clear_cache("user");
	}
}

class MemCacheCache extends Cache implements IRequestTool
{
	private $enable = false;
	private $memcache;

	static public function register($prop)
	{
		if (extension_loaded("memcache")) {
			$GLOBALS["context"]->log_debug("MemCacheCache create");
			return new MemCacheCache();
		}
		else {
			return FileCache::register($prop);
		}
	}

	public function get_cache()
	{
		if ($this->enable && ($this->memcache === NULL)) {
			$app_conf_memcache_host = $GLOBALS["context"]->get_app_conf("memcache_host");

			if (!$app_conf_memcache_host) {
				return NULL;
			}

			$GLOBALS["context"]->log_debug("MemCacheCache connect");
			$this->memcache = new Memcache();

			foreach ($app_conf_memcache_host as $host ) {
				$this->memcache->addServer($host);
			}

			return $this->memcache;
		}
	}

	public function get($key)
	{
		$this->get_cache();

		if ($this->memcache === NULL) {
			return NULL;
		}

		$val = $this->memcache->get($key);
		return $val === false ? NULL : $val;
	}

	public function set($key, $value, $ttl = NULL)
	{
		$this->get_cache();

		if ($this->memcache === NULL) {
			return false;
		}

		if ($value === false) {
			$value = 0;
		}

		if (!$ttl) {
			$ttl = $this->default_ttl;
		}

		return $this->memcache->set($key, $value, 0, $ttl);
	}

	public function delete($key)
	{
		$this->get_cache();

		if ($this->memcache === NULL) {
			return false;
		}

		$this->memcache->delete($key);
	}

	public function clear()
	{
		$this->get_cache();

		if ($this->memcache === NULL) {
			return false;
		}

		$this->memcache->flush();
	}
}

require_once (ROOT_PATH . "boot/req_inc.php");

?>
