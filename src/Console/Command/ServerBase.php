<?php namespace Wing\Binlog\Console\Command;

use Wing\Binlog\Library\Context;
use Wing\Binlog\Library\Worker;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Wing\FileSystem\WFile;

class ServerBase extends Command{

    protected function start( $deamon, $debug = false ){

        $file = new WFile(__APP_DIR__."/app.pid");
        $file->write( ($deamon?1:0).":".($debug?1:0), false );

        if( $debug )
        {
            $deamon = !$debug;
        }

        $worker    = new Worker();

        $worker->setWorkDir(__APP_DIR__);
        $worker->setLogDir(__APP_DIR__."/log");

        $notify_config = include __DIR__."/../../../config/notify.php";
        $handler       = $notify_config["handler"];
        $params        = $notify_config["params"];
        $len           = is_array($params)?count( $params ):0;


        switch( $len ){
            case 0:
                $notify = new $handler;
                break;
            case 1:
                $notify = new $handler( $params[0] );
                break;
            case 2:
                $notify = new $handler( $params[0], $params[1] );
                break;
            case 3:
                $notify = new $handler( $params[0], $params[1], $params[2] );
                break;
            case 4:
                $notify = new $handler( $params[0], $params[1], $params[2], $params[3] );
                break;
            case 5:
                $notify = new $handler( $params[0], $params[1], $params[2], $params[3], $params[4] );
                break;
            case 6:
                $notify = new $handler( $params[0], $params[1], $params[2], $params[3], $params[4], $params[5] );
                break;
            default:
                $notify = new $handler;
        }

        $worker->setNotify( $notify );


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

        $res = new WFile(__APP_DIR__."/app.pid");

        list( $deamon, $debug ) = explode(":",$res->read());

        $deamon = $deamon == 1;
        $debug  = $debug == 1;

        $this->start( $deamon, $debug );
    }

    protected function status(){
        $worker = new Worker();
        return $worker->getStatus();
    }

}