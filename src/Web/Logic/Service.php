<?php namespace Seals\Web\Logic;
use Seals\Web\HttpResponse;

/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/3/16
 * Time: 16:48
 */
class Service
{
    public static function getAll(HttpResponse $response)
    {
        $services = \Seals\Library\Zookeeper::getServices();
        $res = [];
        foreach ($services as $group_id => $groups) {
            $_res = [];
            foreach ($groups as $session_id => $row) {
                //var_dump($row);
                $_res[$session_id] = Node::getInfo($group_id, $session_id);
               // $_res[$session_id]["created"]  = date("Y-m-d H:i:s", $row["created"]);
               // $_res[$session_id]["time_len"] = timelen_format(time()-$row["created"]);
            }
            $res[$group_id] = $_res;
            unset($_res);
        }
        unset($services);
        return $res;
    }
}