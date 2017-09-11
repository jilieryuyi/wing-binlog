<?php namespace Wing\Library;
use Wing\Bin\RowEvent;
use Wing\FileSystem\WDir;

/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/9/8
 * Time: 22:57
 */
class Slave
{
    private $host;// = '127.0.0.1';
    private $port;// = 3306;
    private $password;// = '123456';
    private $user;// = 'root';
    private $db;// = 'xsl';

    private $client;

    private $slave_server_id = 99999;
    private $pdo;

    private $last_binlog_file;
    private $last_pos;

    public function __construct()
    {
        $config         = load_config("app");
        $this->host     = $config["mysql"]["host"];
        $this->port     = $config["mysql"]["port"];
        $this->password = $config["mysql"]["password"];
        $this->user     = $config["mysql"]["user"];
        $this->db       = $config["mysql"]["db_name"];
        $this->pdo      = RowEvent::$pdo = new PDO();

        $this->last_binlog_file = null;
        $file = HOME."/cache/slave/last_binlog_file";
        $dir  = new WDir(HOME."/cache/slave");
        $dir->mkdir();
        unset($dir);
        if (!file_exists($file)) {
        	touch($file);
		}
        $this->last_binlog_file = file_get_contents($file);



        $this->last_pos = 0;
        $file = HOME."/cache/slave/last_pos_file";
        if (!file_exists($file)) {
        	touch($file);
		}
        $this->last_pos = file_get_contents($file);

		$this->client = new \Wing\Bin\ClientNet($this->host, $this->port);
		$this->client->auth($this->user, $this->password, $this->db);
		$this->client->asSlave($this->slave_server_id, $this->last_binlog_file, $this->last_pos);
    }

    public function getEvent() {

        $result = $this->client->getEvent();

		$binlog = \Wing\Bin\BinLogPack::getInstance();
		$file = HOME."/cache/slave/last_binlog_file";
		$bin_file = $binlog->getLastBinLogFile();
		if (0 >= file_put_contents($file, $bin_file))
			file_put_contents($file, $bin_file);

		$file = HOME."/cache/slave/last_pos_file";
		$pos = $binlog->getLastPos();
		if (0 >= file_put_contents($file, $pos))
			file_put_contents($file, $pos);

        return $result;
    }

}