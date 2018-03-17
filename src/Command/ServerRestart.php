<?php namespace Wing\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Wing\Library\Worker;

class ServerRestart extends ServerBase
{
    protected function configure()
    {
        $this
            ->setName('server:restart')
            ->setAliases(["restart"])
            ->setDescription('重新启动');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        exec("php ".HOME."/services/tcp stop");
        exec("php ".HOME."/services/websocket stop");
        Worker::stopAll();

        $worker_info = Worker::getWorkerProcessInfo();
        $daemon      = $worker_info["daemon"];
        $debug       = $worker_info["debug"];
        $workers     = $worker_info["workers"];

        $worker = new Worker([
            "daemon"  => !!$daemon,
            "debug"   => !!$debug,
            "workers" => $workers
        ]);
        $worker->start();
    }
}
