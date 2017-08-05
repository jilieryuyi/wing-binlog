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
		if (strtolower(substr(php_uname('s'), 0, 3)) == "win") {
			return;
		}

		$process_id = get_current_processid();
		//file_put_contents(HOME."/logs/".get_current_processid().".log", "1");
        $file = new \Wing\FileSystem\WDir(HOME."/logs");
        $file->mkdir();
        unset($file);
        $std = fopen(HOME."/logs/wing.log", "a+");

		global $STDOUT, $STDERR;
//
		if ($std) {
		    fclose($std);
		    unset($std);
            //$std = fopen(HOME."/logs/wing.log", "a+");
            @fclose(STDOUT);
            @fclose(STDERR);
            $STDOUT = fopen(HOME."/logs/wing.log", "a+");
            $STDERR = fopen(HOME."/logs/wing.log", "a+");
        }

	}
}

if (!function_exists("load_config")) {
	function load_config($name)
	{
		$config_file = HOME . "/config/" . $name . ".php";
		return include_once $config_file;
	}
}