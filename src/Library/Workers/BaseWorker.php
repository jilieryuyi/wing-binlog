<?php namespace Wing\Library\Workers;

/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/8/5
 * Time: 06:21
 */
abstract class BaseWorker
{
    protected $workers = 1;
    protected $task    = [];
    const USLEEP       = 10000;

    public static $event_times   = 0;
    public static $process_title = '';

    abstract public function start();
}