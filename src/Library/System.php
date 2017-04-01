<?php namespace Seals\Library;
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/4/1
 * Time: 09:20
 */
class System
{
    //ip
    protected $ip = [];

    public function __construct()
    {
        $this->getIp();
    }

    protected function getIp()
    {
        $command = new \Seals\Library\Command("ifconfig");
        $res = $command->run();
        preg_match_all("/[\d]{1,3}.[\d]{1,3}.[\d]{1,3}.[\d]{1,3}/",$res,$m);
        $this->ip = $m[0];
    }
}