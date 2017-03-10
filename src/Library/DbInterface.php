<?php namespace Seals\Library;
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/2/7
 * Time: 18:22
 * 数据库pdo实现接口
 */
interface DbInterface{
    public function query($sql);
    public function getDatabaseName();
    public function getTables();
    public function row($sql);
    public function getHost();
    public function getUser();
    public function getPassword();
    public function getPort();
}