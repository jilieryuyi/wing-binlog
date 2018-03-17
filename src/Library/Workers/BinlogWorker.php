<?php namespace Wing\Library\Workers;

use Wing\Exception\NetCloseException;
use Wing\Library\Binlog;
use Wing\Library\PDO;
use \Wing\Bin\Auth\Auth;

/**
 * EventWorker.php
 * User: huangxiaoan
 * Created: 2017/8/4 12:26
 * Email: huangxiaoan@xunlei.com
 */
class BinlogWorker extends BaseWorker
{

    private $notify = [];
    private $daemon;

    /**
     * @var \Wing\Library\Binlog
     */
    private $binlog;

    public function __construct($daemon, $workers)
    {
        $config = load_config("app");

        $this->binlog = new Binlog(new PDO);
        $this->connect($config);

        if ($config
            && isset($config["subscribe"])
            && is_array($config["subscribe"])
            && count($config["subscribe"]) > 0
        ) {
            foreach ($config["subscribe"] as $class => $params) {
                $params["daemon"]  = $daemon;
                $params["workers"] = $workers;
                $this->notify[] = new $class($params);
            }
        }
    }
    protected function notice($result)
    {
        //通知订阅者
        if (is_array($this->notify) && count($this->notify) > 0) {
            $datas = $result["event"]["data"];
            foreach ($datas as $row) {
                $result["event"]["data"] = $row;
                var_dump($result);
                foreach ($this->notify as $notify) {
                    $notify->onchange($result);
                }
            }
        }
    }

    protected function connect($config)
    {
        try {
            //认证
            Auth::execute(
                $config["mysql"]["host"],
                $config["mysql"]["user"],
                $config["mysql"]["password"],
                $config["mysql"]["db_name"],
                $config["mysql"]["port"]
            );

            //注册为slave
            $this->binlog->registerSlave($config["slave_server_id"]);
        } catch (\Exception $e) {
            var_dump($e->getMessage());
        }
    }

    public function start()
    {
        $daemon = $this->daemon;

        if (!is_env(WINDOWS)) {
            $process_id = pcntl_fork();

            if ($process_id < 0) {
                wing_debug("创建子进程失败");
                exit;
            }

            if ($process_id > 0) {
                return $process_id;
            }

            if ($daemon) {
                reset_std();
            }
        }

        $process_name        = "wing php >> events collector process";
        self::$process_title = $process_name;

        //设置进程标题 mac 会有warning 直接忽略
        set_process_title($process_name);

        $times = 0;
        $start = time();

        while (1) {
            ob_start();

            try {
                pcntl_signal_dispatch();
                do {
                    $result = $this->binlog->getBinlogEvents();

                    if (!$result) {
                        break;
                    }

                    $times    += count($result["event"]["data"]);
                    $span_time = time() - $start;

                    if ($span_time > 0) {
                        echo $times, "次，", $times / ($span_time) . "/次事件每秒，耗时", $span_time, "秒\r\n";
                    }

                    //通知订阅者
                    $this->notice($result);
                } while (0);
            } catch (NetCloseException $e) {
                usleep(500000);
                $this->connect(load_config("app"));
            } catch (\Exception $e) {
                if (WING_DEBUG) {
                    var_dump($e->getMessage());
                }
                unset($e);
            }

            $output = ob_get_contents();
            ob_end_clean();

            if ($output && WING_DEBUG) {
                wing_debug($output);
            }
            unset($output);
            usleep(self::USLEEP);
        }

        return 0;
    }
}
