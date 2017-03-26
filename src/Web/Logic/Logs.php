<?php namespace Seals\Web\Logic;
use Seals\Library\Context;
use Seals\Logger\Local;

/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/3/24
 * Time: 23:44
 */
class Logs
{
    public static function countAll()
    {
        //logs count
        return Local::countAll();
    }

    public static function countDay($day)
    {
        return Local::countDay($day);
    }

    public static function get($session_id, $page, $limit)
    {
        return Local::get($session_id, $page, $limit);
    }
}