<?php

function rmdir_r($path)
{
	if (!file_exists($path)) {
		return NULL;
	}

	if (!is_dir($path)) {
		@unlink($path);
		return NULL;
	}

	$handle = @opendir($path);

	while (($file = @readdir($handle)) !== false) {
		if (($file != ".") && ($file != "..")) {
			$dir = $path . "/" . $file;

			if (is_dir($dir)) {
				rmdir_r($dir);
			}
			else {
				@unlink($dir);
			}
		}
	}

	closedir($handle);
	rmdir($path);
}

function clear_cache_folder()
{
	$context = $GLOBALS["context"];
	$path = ROOT_PATH . $context->app_name . DS . "cache";
	rmdir_r($path);
}

function cp_r($src, $dest)
{
	if (is_dir($src) == false) {
		return NULL;
	}

	if (is_dir($dest) == false) {
		mkdir($dest, 448);
	}

	$handle = @opendir($src);

	while (false !== $file = @readdir($handle)) {
		if (($file != ".") && ($file != "..")) {
			if (is_dir($src . DS . $file)) {
				cp_r($src . DS . $file, $dest . DS . $file);
			}
			else {
				copy($src . DS . $file, $dest . DS . $file);
			}
		}
	}

	@closedir($handle);
}


?>
