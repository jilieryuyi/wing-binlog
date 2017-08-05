<?php namespace Seals\Web\Logic;
use Seals\Web\HttpResponse;

/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/3/22
 * Time: 10:12
 */
class Master
{
    public static function restart(HttpResponse $response)
    {
        \Seals\Library\Master::restart();
    }

    public static function update(HttpResponse $response)
    {
        \Seals\Library\Master::update();
    }
}