<?php namespace Seals\Console\Command;

use Seals\Library\Context;
use Seals\Library\Worker;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
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