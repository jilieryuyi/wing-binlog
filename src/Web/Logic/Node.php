<?php namespace Seals\Web\Logic;
use Seals\Library\Context;
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

        if (!is_array($res))
            $res = [];

        if (!isset($res["created"]))
            $res["created"] = time();

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

        return RPC::call($session_id, "\\Seals\\Library\\Worker::restart", [], 1, true);
    }

    public static function update(HttpResponse $response)
    {
        $group_id   = $response->post("group_id");
        $session_id = $response->post("session_id");

        return RPC::call($session_id, "\\Seals\\Library\\Worker::update", [], 1, true);
    }

    public static function offline(HttpResponse $response)
    {
        $group_id   = $response->post("group_id");
        $session_id = $response->post("session_id");
        $is_offline = $response->post("is_offline")?1:0;

        return RPC::call($session_id, "\\Seals\\Library\\Worker::setNodeOffline", [$is_offline], 1, true);
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

        return RPC::call($session_id, "\\Seals\\Library\\RpcApi::setRuntimeConfig", [$workers, $debug], 1, true);

    }

    public static function setNotifyConfig(HttpResponse $response)
    {
        $group_id   = $response->post("group_id");
        $session_id = $response->post("session_id");

        $class      = urldecode($response->post("class"));
        $param1     = urldecode($response->post("param1"));
        $param2     = urldecode($response->post("param2"));

        if ($param2)
            $params = [$param1, $param2];
        else
            $params = [$param1];

        return RPC::call($session_id, "\\Seals\\Library\\RpcApi::setNotifyConfig", [$class, $params], 1, true);
    }

    public static function setLocalRedisConfig(HttpResponse $response)
    {
        $group_id   = $response->post("group_id");
        $session_id = $response->post("session_id");

        $host      = urldecode($response->post("host"));
        $port      = urldecode($response->post("port"));
        $password  = urldecode($response->post("password"));

        return RPC::call($session_id, "\\Seals\\Library\\RpcApi::setLocalRedisConfig", [$host, $port, $password], 1, true);
    }

    public static function setRedisConfig(HttpResponse $response)
    {
        $group_id   = $response->post("group_id");
        $session_id = $response->post("session_id");

        $host      = urldecode($response->post("host"));
        $port      = urldecode($response->post("port"));
        $password  = urldecode($response->post("password"));

        return RPC::call($session_id, "\\Seals\\Library\\RpcApi::setRedisConfig", [$host, $port, $password], 1, true);
    }

    public static function setRabbitmqConfig(HttpResponse $response)
    {
        $group_id   = $response->post("group_id");
        $session_id = $response->post("session_id");

        $host      = urldecode($response->post("host"));
        $port      = urldecode($response->post("port"));
        $user      = urldecode($response->post("user"));
        $password  = urldecode($response->post("password"));
        $vhost     = urldecode($response->post("vhost"));

        return RPC::call($session_id, "\\Seals\\Library\\RpcApi::setRabbitmqConfig", [$host, $port, $user, $password, $vhost], 1, true);
    }

    public static function setZookeeperConfig(HttpResponse $response)
    {
        $group_id   = $response->post("group_id");
        $session_id = $response->post("session_id");

        $host      = urldecode($response->post("host"));
        $port      = urldecode($response->post("port"));
        $password  = urldecode($response->post("password"));

        return RPC::call($session_id, "\\Seals\\Library\\RpcApi::setZookeeperConfig", [$group_id, $host, $port, $password], 1, true);
    }

    public static function setDbConfig(HttpResponse $response)
    {
        $group_id   = $response->post("group_id");
        $session_id = $response->post("session_id");

        $host      = urldecode($response->post("host"));
        $port      = urldecode($response->post("port"));
        $password  = urldecode($response->post("password"));
        $db_name   = urldecode($response->post("db_name"));
        $user      = urldecode($response->post("user"));

        return RPC::call($session_id, "\\Seals\\Library\\RpcApi::setDbConfig", [$db_name, $host, $user, $password, $port], 1, true);
    }

    public static function getDatabases($session_id)
    {
        return RPC::call($session_id, "\\Seals\\Library\\RpcApi::getDatabases");
    }

    public static function openGenerallog(HttpResponse $response)
    {
        $group_id   = $response->post("group_id");
        $session_id = $response->post("session_id");
        $open       = $response->post("open");

        $open       = intval($open);
        return RPC::call($session_id, "\\Seals\\Library\\RpcApi::openGenerallog",[$open], 1, true);
    }


    public static function getHistoryReadMax($session_id)
    {
        $num = RPC::call($session_id, "\\Seals\\Library\\RpcApi::getHistoryReadMax");
        if (!$num)
            $num = 0;
        return $num;
    }

    public static function getHistoryWriteMax($session_id)
    {
        $num = RPC::call($session_id, "\\Seals\\Library\\RpcApi::getHistoryWriteMax");
        if (!$num)
            $num = 0;
        return $num;
    }

    public static function getDayReadMax($session_id, $day)
    {
        $num = RPC::call($session_id, "\\Seals\\Library\\RpcApi::getDayReadMax",[$day]);
        if (!$num)
            $num = 0;
        return $num;
    }

    public static function getDayWriteMax($session_id, $day)
    {
        $num = RPC::call($session_id, "\\Seals\\Library\\RpcApi::getDayWriteMax",[$day]);
        if (!$num)
            $num = 0;
        return $num;
    }

    public static function getTotalQueryCount()
    {
        $services = \Seals\Library\Zookeeper::getServices();
        $count    = 0;
        foreach ($services as $group_id => $groups) {
            foreach ($groups as $session_id => $row) {
                $count += RPC::call($session_id, "\\Seals\\Library\\RpcApi::getTotalQueryCount");
            }
        }
        return $count;
    }

    public static function getDayQueryCount($day)
    {
        $services = \Seals\Library\Zookeeper::getServices();
        $count    = 0;
        foreach ($services as $group_id => $groups) {
            foreach ($groups as $session_id => $row) {
                $count += RPC::call($session_id, "\\Seals\\Library\\RpcApi::getDayQueryCount", [$day]);
            }
        }
        return $count;
    }

    public static function getDayEvents($session_id, $day, $event)
    {
        $num =  RPC::call($session_id, "\\Seals\\Library\\RpcApi::getDayEvents", [$day, $event]);
        if (!$num)
            return 0;
        return $num;
    }

    public static function getHourReadEvents($session_id)
    {
        $start = strtotime(date("Y-m-d 00:00:00"));
        $end   = strtotime(date("Y-m-d H:00:00"));
        $res   = [];

        $reads = RPC::call($session_id, "\\Seals\\Library\\RpcApi::getTodayHoursReadEvents");

        for ($time = $start; $time <= $end; $time += 3600) {
            $num = is_array($reads) ? array_shift($reads) : 0;
            $res[] = [intval(date("H", $time)), $num];
        }
        return $res;
    }

    public static function getHourWriteEvents($session_id)
    {
        $start  = strtotime(date("Y-m-d 00:00:00"));
        $end    = strtotime(date("Y-m-d H:00:00"));
        $res    = [];

        $writes = RPC::call($session_id, "\\Seals\\Library\\RpcApi::getTodayHoursWriteEvents");

        for ($time = $start; $time <= $end; $time += 3600) {
            $num = is_array($writes) ? array_shift($writes) : 0;
            $res[] = [intval(date("H", $time)), $num ];
        }
        return $res;
    }

    public static function getNodeDayReport($session_id, $start_day, $end_day)
    {
        $report = RPC::call($session_id, "\\Seals\\Library\\RpcApi::getDayReport",[$start_day, $end_day]);
        return $report;
    }

    public static function getDayReport(HttpResponse $response)
    {
        $session_id = $response->post("session_id");
        $start_day  = $response->post("start_day");
        $end_day    = $response->post("end_day");
        return self::getNodeDayReport($session_id, $start_day, $end_day);
    }

    public static function getDayDetailReport(HttpResponse $response)
    {
        $session_id = $response->post("session_id");
        $day        = $response->post("day");
        $report     = RPC::call($session_id, "\\Seals\\Library\\RpcApi::getDayDetailReport",[$day]);
        $res        = [];
        foreach ($report as $hour => $item) {
            $time = strtotime($hour."0000");
            $hour = date("H:00", $time)."-".date("H:00",$time+3600);
            $res[$hour] = $item;
        }
        unset($report);
        return $res;
    }


}