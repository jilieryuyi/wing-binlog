<?php
/**
 * @author yuyi
 * @created 2016/12/4 9:13
 * @email 297341015@qq.com
 */

if (!function_exists("set_process_title")) {
	/**
	 * 设置进程名称
	 *
	 * @param string $title
	 */
	function set_process_title($title)
	{
		if (function_exists("setproctitle"))
			return setproctitle($title);
		if (function_exists("cli_set_process_title"))
			return cli_set_process_title($title);
		return null;
	}
}

if (!function_exists("get_current_processid")) {
	/***
	 * 获取当前进程id
	 */
	function get_current_processid()
	{
		if (function_exists("getmypid"))
			return getmypid();

		if (function_exists("posix_getpid"))
			return posix_getpid();
		return 0;
	}
}

if (!function_exists("enable_deamon")) {
	function enable_deamon()
	{
		if (!function_exists("pcntl_fork"))
			return;

		//修改掩码
		umask(0);

		//创建进程
		$pid = pcntl_fork();
		if (-1 === $pid) {
			throw new \Exception('fork fail');
		} elseif ($pid > 0) {
			//父进程直接退出
			exit(0);
		}

		//创建进程会话
		if (-1 === posix_setsid()) {
			throw new \Exception("setsid fail");
		}
	}
}

if (!function_exists("reset_std")) {
	function reset_std()
	{
        if (strtolower(substr(php_uname('s'),0,3)) == "win") {
            return;
        }

        global $STDOUT, $STDERR;

        $file       = HOME."/logs/wing.log";
        $obj_file   = new \Wing\FileSystem\WFile($file);
        $obj_file->touch();

        @fclose(STDOUT);
        @fclose(STDERR);
        $STDOUT = fopen($file, "a+");
        $STDERR = fopen($file, "a+");

	}
}

if (!function_exists("load_config")) {
	function load_config($name)
	{
		$config_file = HOME . "/config/" . $name . ".php";
		return include $config_file;
	}
}

if (!function_exists("try_lock")) {
    function try_lock($key)
    {
        $dir = HOME."/cache/lock";
        if (!is_dir($dir)) {
            $obj_dir = new \Wing\FileSystem\WDir($dir);
            $obj_dir->mkdir();
            unset($obj_dir);
        }

        $file = $dir."/".md5($key);
        if (file_exists($file))
            return false;

        touch($file);

        return file_exists($file);
    }
}

if (!function_exists("lock_free")) {
    function lock_free($key)
    {
        $dir = HOME."/cache/lock";
        if (!is_dir($dir)) {
            $obj_dir = new \Wing\FileSystem\WDir($dir);
            $obj_dir->mkdir();
            unset($obj_dir);
        }

        $file = $dir."/".md5($key);
        if (!file_exists($file))
            return true;

        return unlink($file);
    }
}