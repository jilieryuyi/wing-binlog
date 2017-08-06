<?php namespace Wing\Library;
use Wing\FileSystem\WDir;

/**
 * Signal.php
 * User: huangxiaoan
 * Created: 2017/8/4 13:10
 * Email: huangxiaoan@xunlei.com
 */
class Signal
{
    private $process_id;
    private $file = null;
    private $dir = null;
	public function __construct($process_id, $dir = 'signal')
	{
	    $dir = $this->dir = HOME."/cache/".$dir;
        (new WDir($dir))->mkdir();
        $this->process_id = $process_id;
        $this->file = $dir."/".$this->process_id;
        echo $this->process_id,"退出信号被生成==============\r\n";
	}

	public function clear()
    {
        $path[] = $this->dir."/*";
        while (count($path) != 0) {
            $v = array_shift($path);
            foreach (glob($v) as $item) {
                if (is_file($item)) {
                    unlink($item);
                }
            }
        }
    }

	public function kill()
	{
        $file = $this->file;//HOME."/cache/signal/".$this->process_id;

        if (!file_exists($file)) {
            touch($file);
            if (!file_exists($file)) {
                touch($file);
                if (!file_exists($file)) {
                    touch($file);
                }
            }
        }

        //if (file_exists($file))
            echo $this->process_id,"退出信号被生成==============被创建\r\n";
//        $handle = fopen($file,"w+");
//        if (!$handle) {
//            return false;
//        }
//        if (!flock($handle, LOCK_EX) ) {
//            flock($handle, LOCK_UN);
//            fclose($handle);
//            return false;
//        }
//        $res = fwrite($handle, 1);
//        flock($handle, LOCK_UN);
//        fclose($handle);
//
//        if(!file_put_contents($file, 1))
//            if(!file_put_contents($file, 1))
//                if(!file_put_contents($file, 1))
//                    if(!file_put_contents($file, 1))
//                        file_put_contents($file, 1);
	}

//	public function clear(){
//        $file = HOME."/cache/signal/".$this->process_id;
//        unlink($file);
//        if (file_exists($file))
//            unlink($file);
//    }

	public function checkStopSignal()
    {
        //echo $this->process_id,"检测退出信号";
        $file = $this->file;//HOME."/cache/signal/".$this->process_id;
        //return file_exists($file);

        if (file_exists($file)) {
           // echo "文件不存在\r\n";
            unlink($file);
            if (file_exists($file)){
                unlink($file);
                if (file_exists($file)){
                    unlink($file);
                    if (file_exists($file)){
                        unlink($file);
                        if (file_exists($file)){
                            unlink($file);
                        }
                    }
                }
            }
            if (file_exists($file)) {
                file_put_contents($file."_unlink_fialure", 1);
                echo "----------------------------------删除文件失败\r\n";
            } else {
                file_put_contents($file."_unlink_success", 1, FILE_APPEND);
            }
            echo $this->process_id,"退出信号被生成==============被删除\r\n";

            return true;
        }
//        $res = file_get_contents($file) == 1;
//        if ($res) {
//            if(!unlink($file)) if(!unlink($file)) unlink($file);
//        }
//        if (file_exists($file)) {
//
//        }
         //var_dump($res);echo "\r\n";
        return false;
    }
}