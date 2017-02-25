<?php namespace Seals\Console\Command;

use Seals\Library\Worker;
use Symfony\Component\Console\Command\Command;
use Wing\FileSystem\WFile;

class ServerBase extends Command{

    protected function start( $deamon, $workers, $debug = false ){

        $file = new WFile(__APP_DIR__."/seals.pid");
        $file->write( ($deamon?1:0).":".$workers.":".($debug?1:0), false );

        if( $debug )
        {
            $deamon = !$debug;
        }

        $worker    = new Worker();

        $worker->setWorkDir(__APP_DIR__);
        $worker->setLogDir(__APP_DIR__."/log");

        if( $workers > 0 )
            $worker->setWorkersNum($workers);

        $handlers_config = include __APP_DIR__."/config/notify.php";
        $handler_class = $handlers_config["handler"];
        if( !class_exists($handler_class) ){
            exit($handler_class." class not found");
        }

        $len = count($handlers_config["params"]);
        $handler = null;//new $handler_class;
        switch($len){
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

        if( $debug )
            $worker->enabledDebug();
        else
            $worker->disabledDebug();

        $worker->start($deamon);
    }

    protected function stop(){
        $worker    = new Worker();
        $worker->stop();
    }

    protected function restart(){
        $this->stop();

        $res = new WFile(__APP_DIR__."/seals.pid");

        list( $deamon, $workers, $debug ) = explode(":",$res->read());

        $deamon = $deamon == 1;
        $debug  = $debug == 1;

        $this->start( $deamon, $workers, $debug );
    }

    protected function status(){
        $worker = new Worker();
        return $worker->getStatus();
    }

}