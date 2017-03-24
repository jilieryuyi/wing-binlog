<?php namespace Seals\Web\Logic;
use Seals\Library\Context;
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
        return Context::instance()->redis_zookeeper->incr("wing-binlog-logs-count");

    }

    public static function countDay($day)
    {
        return Context::instance()->redis_zookeeper->incr("wing-binlog-logs-count-".$day);
    }

    public static function get($session_id, $page, $limit)
    {
        //logs report
        $start = ($page-1) * $limit;
        $end   = $page * $limit;
        $data  = Context::instance()->redis_zookeeper->lrange(
            "wing-binlog-logs-content-".$session_id,
            $start,
            $end
        );
        $res = [];
        foreach ($data as $row) {
            $res[] = json_decode($row, true);
        }
        return $res;
    }
}