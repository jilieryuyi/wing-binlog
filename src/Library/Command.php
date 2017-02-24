<?php namespace Seals\Library;
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 16/11/17
 * Time: 16:51
 */

class Command
{
    private  $command;
    public function __construct( $command )
    {
        $this->command = $command;
    }

    /***
     * @检测是否支持命令
     *
     * @return bool
     */
    public function check(){
        $res = $this->run();
        if( strpos( $res, "command not found" ) !== false )
        {
            return false;
        }
        return true;
    }

    public function run(){
        $handle = popen( $this->command ,"r");

        $result = '';
        while(1){
            $res = fgets($handle, 1024);
            if( $res )
                $result.=$res;
            else break;
        }

        pclose($handle);
        return $result;
    }
}