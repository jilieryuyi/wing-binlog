<?php namespace Seals\Library;
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/2/13
 * Time: 10:11
 */

interface Process{
    public function start();
    public function stop();
    public function status();
    public function isRunning();
}