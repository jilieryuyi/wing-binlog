<?php namespace Wing\Subscribe;
use Wing\FileSystem\WDir;
use Wing\Library\ISubscribe;

/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/8/4
 * Time: 22:58
 */
class Tcp implements ISubscribe
{
    private $processes = [];
    public function __construct()
    {
        $cache = HOME."/cache/tcp";
        (new WDir($cache))->mkdir();
        $this->processes = self::tcpStatus();
    }

    public static function tcpStatus()
    {
        $command = "php ".HOME."/tcp status";
        $output = $return = null;
        exec($command,$output,$return);

//var_dump($output, $return);

        $start = false;
        $processes = [];
        foreach ($output as $row) {
            if (substr($row, 0, 3) == "pid") {
                $start = true;
                continue;
            }
            if ($start) {
                list($process_id,) = preg_split("/\D/", $row, 2);
                $processes[]= $process_id;//,"\r\n";
            }
        }
    }


    public function onchange($database_name, $table_name, $event)
    {
        if (!$this->processes) {
            if (!file_exists(HOME."/tcp.pid")) {
                return;
            }
            $str = file_get_contents(HOME."/tcp.pid");
            $arr = preg_split("/\D/", $str);
            foreach ($arr as $i) {
                if (intval($i) > 0)
                    $this->processes[] = intval($i);
            }
            return;
        }

        foreach ($this->processes as $process) {
            $cache = HOME . "/cache/tcp/".$process;
            $odir = new WDir($cache);
            $odir->mkdir();
            unset($odir);
            $str1 = md5(rand(0, 999999));
            $str2 = md5(rand(0, 999999));
            $str3 = md5(rand(0, 999999));


            $cache_file = $cache . "/__" . time() .
                substr($str1, rand(0, strlen($str1) - 16), 8) .
                substr($str2, rand(0, strlen($str2) - 16), 8) .
                substr($str3, rand(0, strlen($str3) - 16), 8);

            file_put_contents($cache_file, json_encode([$database_name, $table_name, $event]));
        }
    }
}