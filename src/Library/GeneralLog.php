<?php namespace Seals\Library;
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/3/17
 * Time: 13:04
 */
class GeneralLog
{
    protected $pdo;
    public function __construct(DbInterface $pdo)
    {
        $this->pdo = $pdo;
    }

    public function open()
    {
        $sql = 'set @@global.general_log=1';
        return $this->pdo->query($sql);
    }

    public function close()
    {
        $sql = 'set @@global.general_log=0';
        return $this->pdo->query($sql);
    }
    public function getLogPath()
    {
        $sql  = 'select @@general_log_file';
        $data = $this->pdo->row($sql);

        if (!isset($data["@@general_log_file"]))
            return null;

        return $data["@@general_log_file"];
    }
    public function isOpen()
    {
        $sql = 'select @@general_log';
        $data = $this->pdo->row($sql);

        return isset($data["@@general_log"]) && $data["@@general_log"] == 1;
    }

    //查询事件解析
    public function parse()
    {

    }
}