<?php namespace Seals\Console\Command;

use Seals\Library\Worker;
use Symfony\Component\Console\Command\Command;
use Wing\FileSystem\WDir;
use Wing\FileSystem\WFile;

class ServerBase extends Command
{

    protected function clear($log_dir, $binlog_cache_dir)
    {
        $dir   = new WDir($binlog_cache_dir);
        $files = $dir->scandir();
        array_map(function($file) {
            unlink($file);
        }, $files);

        $dir   = new WDir($log_dir);
        $files = $dir->scandir();
        array_map(function($file) {
            unlink($file);
        }, $files);
    }
    protected function start($deamon, $workers, $debug = false, $clear = false)
    {

        $file = new WFile(__APP_DIR__."/seals.pid");
        $file->write( ($deamon?1:0).":".$workers.":".($debug?1:0).":".($clear?1:0), false );

        $app_config = include __APP_DIR__."/config/app.php";


        if ($clear)
            $this->clear($app_config["log_dir"],$app_config["binlog_cache_dir"]);


        $worker    = new Worker(
            $app_config["app_id"],
            $app_config["memory_limit"],
            $app_config["log_dir"],
            $app_config["process_cache_dir"],
            $app_config["binlog_cache_dir"]
        );

        if ($workers > 0)
            $worker->setWorkersNum($workers);

        $handlers_config = include __APP_DIR__."/config/notify.php";
        $handler_class = $handlers_config["handler"];
        if (!class_exists($handler_class)) {
            exit($handler_class." class not found");
        }

        $len = count($handlers_config["params"]);
        $handler = null;//new $handler_class;
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

        $worker->setNotify( $handler );

        if ($debug)
            $worker->enabledDebug();
        else
            $worker->disabledDebug();

        if ($deamon)
            $worker->enableDeamon();

        $worker->start();
    }

    protected function stop()
    {
        $worker    = new Worker();
        $worker->stop();
    }

    protected function restart()
    {
        $this->stop();

        $res = new WFile(__APP_DIR__."/seals.pid");

        list($deamon, $workers, $debug, $clear) = explode(":",$res->read());

        $deamon = $deamon == 1;
        $debug  = $debug == 1;
        $clear  = $clear == 1;

        $this->start($deamon, $workers, $debug, $clear);
    }

    protected function status()
    {
        $worker = new Worker();
        return $worker->getStatus();
    }

}