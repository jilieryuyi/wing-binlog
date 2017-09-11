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
	private $host;
    private $port;
    private $password;
    private $user;
    private $db;
    private $client;
    private $slave_server_id = 99999;
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

        $this->last_binlog_file = null;
		$this->last_pos 		= 0;

        $bin_file 	= HOME."/cache/slave/last_binlog_file";
		$pos_file	= HOME."/cache/slave/last_pos_file";

		$dir = new WDir(HOME."/cache/slave");
        $dir->mkdir();
        unset($dir);
        if (!file_exists($bin_file)) {
        	touch($bin_file);
		}

        if (!file_exists($pos_file)) {
        	touch($pos_file);
		}

		$this->last_binlog_file = file_get_contents($bin_file);
        $this->last_pos 		= file_get_contents($pos_file);
		$this->client 			= new \Wing\Bin\ClientNet($this->host, $this->port);

		//连接并认证mysql 然后后面注册为slave
		$this->client->auth($this->user, $this->password, $this->db);
		$this->client->asSlave($this->slave_server_id, $this->last_binlog_file, $this->last_pos);
    }

    /**
     * 获取事件
	 *
	 * @return array
	 */
    public function getEvent() {

    	try {
			$result = $this->client->getEvent();

			$binlog = \Wing\Bin\BinLogPack::getInstance();
			$file = HOME . "/cache/slave/last_binlog_file";
			$bin_file = $binlog->getLastBinLogFile();

			if ($bin_file) {
				if (0 >= file_put_contents($file, $bin_file))
					file_put_contents($file, $bin_file);
			}

			$file = HOME . "/cache/slave/last_pos_file";
			$pos = $binlog->getLastPos();

			if ($pos) {
				if (0 >= file_put_contents($file, $pos))
					file_put_contents($file, $pos);
			}

			return $result;
		} catch(\Exception $e) {
    		var_dump($e->getMessage());
		}

        return null;
    }

}