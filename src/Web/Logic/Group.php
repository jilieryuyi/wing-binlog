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
class Group
{
    /**
     * set runtime config, include workers num and debug open or close
     */
    public static function setRuntimeConfig(HttpResponse $response)
    {
        $group_id   = $response->post("group_id");

        $nodes      = Zookeeper::getNodes($group_id);

        $workers    = $response->post("workers");
        if (intval($workers) <= 0) {
            $workers = 1;
        }

        $debug      = $response->post("debug");
        $debug      = intval($debug) == 1 ? 1 : 0;

        foreach ($nodes as $session_id) {
            RPC::call($session_id, "\\Seals\\Library\\Worker::setRuntimeConfig", [$workers, $debug]);
        }

        return 1;
    }

    public static function setNotifyConfig(HttpResponse $response)
    {
        $group_id   = $response->post("group_id");
        $nodes      = Zookeeper::getNodes($group_id);

        $class      = urldecode($response->post("class"));
        $param1     = urldecode($response->post("param1"));
        $param2     = urldecode($response->post("param2"));

        if ($param2)
            $params = [$class, [$param1, $param2]];
        else
            $params = [$class, [$param1]];

        foreach ($nodes as $session_id) {
            RPC::call($session_id, "\\Seals\\Library\\Worker::setNotifyConfig", [$class, $params]);
        }
        return 1;
    }

    public static function setRedisConfig(HttpResponse $response)
    {
        $group_id   = $response->post("group_id");
        $nodes      = Zookeeper::getNodes($group_id);

        $host      = urldecode($response->post("host"));
        $port      = urldecode($response->post("port"));
        $password  = urldecode($response->post("password"));

        foreach ($nodes as $session_id) {
            RPC::call($session_id, "\\Seals\\Library\\Worker::setRedisConfig", [$host, $port, $password]);
        }
        return 1;
    }

    public static function setRabbitmqConfig(HttpResponse $response)
    {
        $group_id   = $response->post("group_id");
        $nodes      = Zookeeper::getNodes($group_id);

        $host      = urldecode($response->post("host"));
        $port      = urldecode($response->post("port"));
        $user      = urldecode($response->post("user"));
        $password  = urldecode($response->post("password"));
        $vhost     = urldecode($response->post("vhost"));

        foreach ($nodes as $session_id) {
            RPC::call($session_id, "\\Seals\\Library\\Worker::setRabbitmqConfig", [$host, $port, $user, $password, $vhost]);
        }
        return 1;
    }

    public static function setZookeeperConfig(HttpResponse $response)
    {
        $group_id   = $response->post("group_id");
        $nodes      = Zookeeper::getNodes($group_id);

        $host      = urldecode($response->post("host"));
        $port      = urldecode($response->post("port"));
        $password  = urldecode($response->post("password"));

        foreach ($nodes as $session_id) {
            RPC::call($session_id, "\\Seals\\Library\\Worker::setZookeeperConfig", [$group_id, $host, $port, $password]);
        }
        return 1;
    }

    public static function setDbConfig(HttpResponse $response)
    {
        $group_id   = $response->post("group_id");
        $nodes      = Zookeeper::getNodes($group_id);

        $host      = urldecode($response->post("host"));
        $port      = urldecode($response->post("port"));
        $password  = urldecode($response->post("password"));
        $db_name   = urldecode($response->post("db_name"));
        $user      = urldecode($response->post("user"));

        foreach ($nodes as $session_id) {
            RPC::call($session_id, "\\Seals\\Library\\Worker::setDbConfig", [$db_name, $host, $user, $password, $port]);
        }
        return 1;
    }

    public static function offline(HttpResponse $response)
    {
        $group_id   = $response->post("group_id");
        $is_offline = $response->post("is_offline");

        $nodes      = Zookeeper::getNodes($group_id);
        foreach ($nodes as $session_id) {
            RPC::call($session_id, "\\Seals\\Library\\Worker::setNodeOffline", [$is_offline]);
        }
        return 1;
    }

    public static function openGenerallog(HttpResponse $response)
    {
        $group_id   = $response->post("group_id");
        $open       = $response->post("open");

        $open       = intval($open);
        $nodes      = Zookeeper::getNodes($group_id);

        foreach ($nodes as $session_id) {
            RPC::call($session_id, "\\Seals\\Library\\Worker::openGenerallog",[$open]);
        }

        return 1;

    }


}