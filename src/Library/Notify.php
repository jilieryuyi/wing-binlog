<?php namespace Seals\Library;
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/2/18
 * Time: 10:10
 */
interface Notify{
    /**
     * @return bool
     */
    public function send( $database_name, $table_name, array $event_data);
}