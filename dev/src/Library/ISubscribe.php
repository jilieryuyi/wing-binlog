<?php namespace Wing\Library;
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/8/4
 * Time: 22:54
 */
interface ISubscribe
{
    public function onchange($database_name, $table_name, $event);
}