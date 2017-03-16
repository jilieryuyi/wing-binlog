<?php namespace Seals\Web\Logic;
use Seals\Library\Zookeeper;
use Seals\Web\HttpResponse;

/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/3/16
 * Time: 15:35
 */
class Node
{
    public static function info(HttpResponse $response)
    {
        var_dump($response->postAll());
        $group_id   = $response->post("group_id");
        $session_id = $response->post("session_id");

        return self::getInfo($group_id, $session_id);

    }

    public static function getInfo($group_id, $session_id)
    {
        $last_binlog       = Zookeeper::getGroupLastBinlog($group_id);
        list(, $last_pos)  = Zookeeper::getGroupLastPost($group_id);
        $is_enable         = Zookeeper::isEnable($group_id, $session_id);
        $is_leader         = Zookeeper::getLeader($group_id) == $session_id ? 1 : 0;
        $last_report       = time() - Zookeeper::getLastReport($group_id, $session_id);

        return [
            "enable"       => $is_enable,   //node is enable group
            "is_leader"    => $is_leader,   //node is leader
            "last_updated" => $last_report, //node last report timestamp
            "last_binlog"  => $last_binlog, //the last read  mysqlbinlog filename
            "last_pos"     => $last_pos,    //the last reas postion from the last mysqlbinlog
            "is_down"      => 0,   //is the node is down, only from runtime
        ];
    }
}