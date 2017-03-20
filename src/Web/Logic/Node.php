<?php namespace Seals\Web\Logic;
use Seals\Library\RPC;
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
        $group_id   = $response->post("group_id");
        $session_id = $response->post("session_id");

        return self::getInfo($group_id, $session_id);
    }

    public static function getInfo($group_id, $session_id)
    {
        $last_binlog       = Zookeeper::getGroupLastBinlog($group_id);
        $res               = Zookeeper::getGroupLastPost($group_id);
        $last_pos          = isset($res[1]) ? $res[1] : 0;
        $is_leader         = Zookeeper::getLeader($group_id) == $session_id ? 1 : 0;
        $res               =  Zookeeper::getLastReport($group_id, $session_id);
        $last_report       = time() - $res["updated"];

        return array_merge($res, [
            //"workers"      => $res["workers"],
            //"debug"        => $res["debug"],
            //"version"      => $res["version"],
            "created"      => date("Y-m-d H:i:s", $res["created"]),
            "time_len"     => timelen_format(time()-$res["created"]),
            "is_leader"    => $is_leader,           //node is leader
            "last_updated" => $last_report,         //node last report timestamp
            "last_binlog"  => $last_binlog,         //the last read  mysqlbinlog filename
            "last_pos"     => $last_pos,            //the last reas postion from the last mysqlbinlog
            //"is_offline"   => $res["is_offline"],   //is the node is offline, only from runtime
        ]);
    }

    public static function restart(HttpResponse $response)
    {
        $group_id   = $response->post("group_id");
        $session_id = $response->post("session_id");

        return RPC::call($session_id, "\\Seals\\Library\\Worker::restart");
    }

    public static function update(HttpResponse $response)
    {
        $group_id   = $response->post("group_id");
        $session_id = $response->post("session_id");

        return RPC::call($session_id, "\\Seals\\Library\\Worker::update");
    }

    public static function offline(HttpResponse $response)
    {
        $group_id   = $response->post("group_id");
        $session_id = $response->post("session_id");
        $is_offline = $response->post("is_offline");

        return RPC::call($session_id, "\\Seals\\Library\\Worker::setNodeOffline", [$is_offline]);
    }

    /**
     * set runtime config, include workers num and debug open or close
     */
    public static function setRuntimeConfig(HttpResponse $response)
    {
        $group_id   = $response->post("group_id");
        $session_id = $response->post("session_id");

        $workers    = $response->post("workers");
        if (intval($workers) <= 0) {
            $workers = 1;
        }

        $debug      = $response->post("debug");
        $debug      = intval($debug) == 1 ? 1 : 0;

        return RPC::call($session_id, "\\Seals\\Library\\Worker::setRuntimeConfig", [$workers, $debug]);

    }
}