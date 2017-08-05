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
	public function __construct($process_id)
	{
	    $dir = HOME."/cache/signal";
        (new WDir($dir))->mkdir();
        $this->process_id = $process_id;
	}

	public function kill()
	{
        $file = HOME."/cache/signal/".$this->process_id;

        if (!file_exists($file)) {
            touch($file);
        }
        $handle = fopen($file,"w+");
        if (!$handle) {
            return false;
        }
        if (!flock($handle, LOCK_EX) ) {
            flock($handle, LOCK_UN);
            fclose($handle);
            return false;
        }
        $res = fwrite($handle, 1);
        flock($handle, LOCK_UN);
        fclose($handle);

        return $res;
	}

	public function checkStopSignal()
    {
        $file = HOME."/cache/signal/".$this->process_id;
        if (!file_exists($file)) {
            return false;
        }
        $res = file_get_contents($file) == 1;
        if ($res) {
            if(!unlink($file)) if(!unlink($file)) unlink($file);
        }
        return $res;
    }
}