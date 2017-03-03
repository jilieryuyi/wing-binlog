<?php namespace Seals\Library;
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/2/13
 * Time: 10:11
 *
 * 进程接口
 *
 */

interface Process
{

    /**
     * 启动进程
     */
    public function start();

    /**
     * 停止进程
     */
    public function stop();

    /**
     * 获取进程状态
     *
     * @return string
     */
    public function status();

    /**
     * 判断是否还在运行
     *
     * @return bool
     */
    public function isRunning();
}