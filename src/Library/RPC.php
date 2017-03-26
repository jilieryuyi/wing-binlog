<?php namespace Seals\Library;
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/3/17
 * Time: 14:45
 *
 * simple rpc with redis
 */
class RPC
{
    const RPC_LIST = "wing-binlog-rpc-list";

    protected static function createEventId()
    {
        $str1 = md5(rand(0,999999));
        $str2 = md5(rand(0,999999));
        $str3 = md5(rand(0,999999));

        return time()."-".
        substr($str1,rand(0,strlen($str1)-16),16).
        substr($str2,rand(0,strlen($str2)-16),16).
        substr($str3,rand(0,strlen($str3)-16),16);
    }

    /**
     * rpc call
     *
     * @param string $func only support static func, like \\Seals\\Test::a
     * @param array $params
     * @param int $timeout seconds from timeout wait
     * @return mixed
     */
    public static function call($session_id, $func, $params = [], $timeout = 3, $async = false)
    {
        if (!Context::instance()->redis_zookeeper)
            return null;
        $event_id = self::createEventId();
        $success  = Context::instance()->redis_zookeeper->rPush(
            self::RPC_LIST.":".$session_id,
            json_encode([
                "event_id"   => $event_id,
                "func"       => $func,
                "params"     => $params
            ])
        );
        if (!$success)
            return null;

        if ($async)
            return $success;

        $start = time();
        //wait for result
        while (1) {
            if ((time()-$start) > 3)
                break;
            if (Context::instance()->redis_zookeeper->exists($event_id))
                break;
        }
        //wait timeout
        if (!Context::instance()->redis_zookeeper->exists($event_id)) {
            return null;
        }

        return Context::instance()->redis_zookeeper->get($event_id);
//        if ($json === 0)
//            return $json;
//
//        if (!$json)
//            return null;
//
//        return json_decode($json, true);
    }

    /**
     * run rpc
     */
    public static function run()
    {
        if (!Context::instance()->redis_zookeeper) {
            return;
        }
        $name = self::RPC_LIST.":".Context::instance()->session_id;
        while (1) {
            $len = Context::instance()->redis_zookeeper->lLen($name);
            if ($len <= 0) {
                unset($len);
                break;
            }

            $data = Context::instance()->redis_zookeeper->lPop($name);
            if (!$data) {
                unset($data);
                continue;
            }

            $data     = json_decode($data, true);
            $event_id = $data["event_id"];
            $func     = $data["func"];
            $params   = $data["params"];
            unset($data);

            if (is_callable($func)) {
                $res = call_user_func_array($func, $params);
                if (is_array($res))
                    $res = json_encode($res);
            } else {
                $res = "";
            }

            unset($func, $params);

            Context::instance()->redis_zookeeper->set($event_id, $res);
            Context::instance()->redis_zookeeper->expire($event_id, 12);
            unset($res, $event_id);
        }
    }
}