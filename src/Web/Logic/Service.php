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
            foreach ($groups as $session_id => $last_updated) {
                $_res[$session_id] = Node::getInfo($group_id, $session_id);
            }
            $res[$group_id] = $_res;
            unset($_res);
        }
        unset($services);
        return $res;
    }
}