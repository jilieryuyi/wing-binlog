<?php namespace Wing\Binlog\Library;
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/2/7
 * Time: 18:22
 */
interface DbInterface{
    public function query( $sql );
    public function getDatabaseName();
    public function getTables();
    public function row( $sql );
}