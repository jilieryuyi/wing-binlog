<?php
/**
 * proc_open.php
 * User: huangxiaoan
 * Created: 2017/8/11 10:00
 * Email: huangxiaoan@xunlei.com
 */
//cmd为要执行的程序， timeout是超时时间


$processes = [];
$all_pipes = [];

for ($i = 0; $i < 10; $i++) {
	$descriptorspec = array(
		0 => array("pipe", "r"),
		1 => array("pipe", "w"),
		2 => array("pipe", "w")
	);
	$cmd = "php " . __DIR__ . "/run.php";
	$processes[$i] = proc_open($cmd, $descriptorspec, $pipes);
	$all_pipes[]   = $pipes[1];
	//$all_pipes[] = $pipes[2];
	stream_set_blocking($pipes[1], 0);
	//stream_set_blocking($pipes[2], 0);  //不阻塞
	fwrite($pipes[0], "hello proc_open_".$i."  ");
	fclose($pipes[0]);
	fclose($pipes[2]); //标准错误直接关闭 不需要

}
//$stdout_str = $stderr_str = $stdin_str ="";
$timeout = 100;



//if (is_resource($process))
{
	//执行成功


	//设置超时时钟
	$endtime = time() + $timeout;

	do {
		$read = $all_pipes;//array($pipes[1],$pipes[2]);
		$write = null;
			$except = null;
		$timeleft = 1;//$endtime - time();
		$ret = stream_select(
			$read,
			$write,// = null,
			$except,// = null,
			$timeleft
		);

		if ($ret === false) {
			$err = true;
			break;
		} else if ($ret === 0) {
			$timeleft = 0;
			break;
		} else {
			var_dump($ret);
			foreach ($read as $sock) {
				//if ($sock === $pipes[1]) {
					echo  fread($sock, 4096), "\r\n";
					$id = array_search($sock, $all_pipes);
					unset($all_pipes[$id]);
//				} else if ($sock === $pipes[2]) {
//					echo  fread($sock, 4096),"\r\n";
//				}
				fclose($sock);
			}
		}
	}while(count($all_pipes) > 0 && $timeleft > 0 );

//	fclose($pipes[1]);
//	fclose($pipes[2]);
	if($timeleft <= 0) {
		foreach ($processes as $process)
		proc_terminate($process);
		$stderr_str = "操作已超时(".$timeout."秒)";
	}

	if (isset($err) && $err === true){  //这种情况的出现是通过信号发送中断时产生
		foreach ($processes as $process)
		proc_terminate($process);
		$stderr_str = "操作被用户取消";
	}
	foreach ($processes as $process)
	proc_close($process);
	//return true;
}
//else {
//	return false;
//}