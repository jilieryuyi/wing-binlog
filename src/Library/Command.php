<?php namespace Seals\Library;
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 16/11/17
 * Time: 16:51
 * 执行命令并返回结果
 */

class Command
{
    /**
     * @var string
     */
    private  $command;

    /**
     * 构造函数
     *
     * @param string $command
     */
    public function __construct($command)
    {
        $this->command = $command;
    }

    /***
     * 检测是否支持命令
     *
     * @return bool 支持，返回true
     */
    public function check()
    {
        $res = $this->run();
        return strpos($res, "command not found") === false;
    }

    /**
     * 执行指令
     *
     * @return string
     */
    public function run(){

        $handle = popen($this->command ,"r");

        if (!$handle)
            return null;

        $result = '';

        while (1) {
            $res = fgets($handle, 1024);
            if ($res) {
                $result.=$res;
            }
            else {
                break;
            }
        }

        pclose($handle);
        return $result;
    }
}