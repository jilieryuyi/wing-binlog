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
    public function __construct()
    {
        $cache = HOME."/cache/tcp";
        (new WDir($cache))->mkdir();
    }



    public function onchange($database_name, $table_name, $event)
    {

            $cache = HOME . "/cache/tcp";
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

            file_put_contents($cache_file, json_encode([
            	"database"=>$database_name,
				"table"=>$table_name,
				"event"=>$event
			]));
    }
}