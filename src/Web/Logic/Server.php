<?php namespace Seals\Web\Logic;
use Seals\Web\HttpResponse;

/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/3/30
 * Time: 07:59
 */
class Server
{
    public static function serversNum(HttpResponse $response = null)
    {
        $count = 0;
        $services = \Seals\Library\Zookeeper::getServices();
        foreach ($services as $service) {
            $count += is_array($service)?count($service):0;
        }
        return $count;
    }

    public static function totalEvents(HttpResponse $response = null)
    {
        return \Seals\Library\Report::getEventsCount();
    }
}