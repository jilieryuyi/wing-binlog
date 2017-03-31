<?php namespace Seals\Console\Command;

use Seals\Cache\File;
use Seals\Library\Cpu;
use Seals\Library\Worker;
use Symfony\Component\Console\Command\Command;
use Wing\FileSystem\WDir;

/**
 * @author yuyi
 * @email 297341015@qq.com
 *
 * worker console control
 */

class ServerBase extends Command
{

    const PROCESS_INFO = "seals.info";
    /**
     * clear binlog cache and logs
     *
     * @param string $log_dir
     * @param string $binlog_cache_dir
     */
    protected function clear($log_dir, $binlog_cache_dir)
    {
        if ($binlog_cache_dir) {
            $dir   = new WDir($binlog_cache_dir);
            $files = $dir->scandir();

            array_map(function ($file) {
                unlink($file);
            }, $files);
        }

        if ($log_dir) {
            $dir   = new WDir($log_dir);
            $files = $dir->scandir();

            array_map(function ($file) {
                unlink($file);
            }, $files);
        }
    }

    /**
     * get app configs
     *
     * @return array
     */
    protected function getAppConfig()
    {
        $app_config = include __APP_DIR__."/config/app.php";


        if (!isset($app_config["mysqlbinlog_bin"])) {
            $app_config["mysqlbinlog_bin"] = "";
        }

        if (!isset($app_config["binlog_cache_dir"])) {
            $app_config["binlog_cache_dir"] = "";
        }

        if (!isset($app_config["memory_limit"])) {
            $app_config["memory_limit"] = 0;
        }

        if (!isset($app_config["log_dir"])) {
            $app_config["log_dir"] = "";
        }

        if (!isset($app_config["process_cache_dir"])) {
            $app_config["process_cache_dir"] = "";
        }

        return $app_config;
    }

    /**
     * start process
     *
     * @param bool $deamon
     * @param int $workers
     * @param bool $debug
     * @param bool $clear
     */
    protected function start($deamon, $workers, $debug = false, $clear = false)
    {

        $app_config = $this->getAppConfig();
        $cache      = new File(__APP_DIR__);
        $worker     = new Worker($app_config["app_id"]);

        if ($workers <= 0) {
            $cpu = new Cpu();
            $workers = $cpu->cpu_num ;
            unset($cpu);
        }

        $cache->set(self::PROCESS_INFO, [$deamon, $workers, $debug, $clear]);

        if ($clear) {
            $this->clear($app_config["log_dir"], $app_config["binlog_cache_dir"]);
        }

        $worker->setWorkersNum($workers);

        $handlers_config = include __DIR__."/../../../config/notify.php";
        $handler_class   = $handlers_config["handler"];

        if (!class_exists($handler_class)) {
            exit($handler_class." class not found");
        }

        $len     = count($handlers_config["params"]);
        $handler = null;

        switch ($len) {
            case 0:
                $handler = new $handler_class;
                break;
            case 1:
                $handler = new $handler_class($handlers_config["params"][0]);
                break;
            case 2:
                $handler = new $handler_class(
                    $handlers_config["params"][0],
                    $handlers_config["params"][1]
               );
                break;
            case 3:
                $handler = new $handler_class(
                    $handlers_config["params"][0],
                    $handlers_config["params"][1],
                    $handlers_config["params"][2]
               );
                break;
            case 4:
                $handler = new $handler_class(
                    $handlers_config["params"][0],
                    $handlers_config["params"][1],
                    $handlers_config["params"][2],
                    $handlers_config["params"][3]
               );
                break;
            case 5:
                $handler = new $handler_class(
                    $handlers_config["params"][0],
                    $handlers_config["params"][1],
                    $handlers_config["params"][2],
                    $handlers_config["params"][3],
                    $handlers_config["params"][4]
               );
                break;
            case 6:
                $handler = new $handler_class(
                    $handlers_config["params"][0],
                    $handlers_config["params"][1],
                    $handlers_config["params"][2],
                    $handlers_config["params"][3],
                    $handlers_config["params"][4],
                    $handlers_config["params"][5]
               );
                break;
            case 7:
                $handler = new $handler_class(
                    $handlers_config["params"][0],
                    $handlers_config["params"][1],
                    $handlers_config["params"][2],
                    $handlers_config["params"][3],
                    $handlers_config["params"][4],
                    $handlers_config["params"][5],
                    $handlers_config["params"][6]
               );
                break;
            case 8:
                $handler = new $handler_class(
                    $handlers_config["params"][0],
                    $handlers_config["params"][1],
                    $handlers_config["params"][2],
                    $handlers_config["params"][3],
                    $handlers_config["params"][4],
                    $handlers_config["params"][5],
                    $handlers_config["params"][6],
                    $handlers_config["params"][7]
               );
                break;
            default:
                $handler = new $handler_class;
            break;
        }

        $worker->setNotify($handler);

        if ($debug) {
            $worker->enabledDebug();
        } else {
            $worker->disabledDebug();
        }

        if ($deamon) {
            $worker->enableDeamon();
        }

        $worker->start();
    }

    /**
     * stop process
     */
    protected function stop()
    {
        Worker::stopAll();
    }

    /**
     * restart process
     *
     * @return bool
     */
    protected function restart()
    {
        return !!Worker::restart();
    }

    /**
     * get process running status
     *
     * @return string
     */
    protected function status()
    {
        $worker     = new Worker();
       // $app_config = $this->getAppConfig();

        //$worker->setProcessCache(new \Seals\Cache\File($app_config["process_cache_dir"]));
        return $worker->getStatus();
    }

    /**
     * get version
     *
     * @return string
     */
    protected function version()
    {
        return Worker::version();
    }


}