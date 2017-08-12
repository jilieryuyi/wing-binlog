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

        $winfo       = Worker::getWorkerProcessInfo();
        $deamon      = $winfo["daemon"];//$input->getOption("d");
        $debug       = $winfo["debug"];//$input->getOption("debug");
        $workers     = $winfo["workers"];//$input->getOption("n");

        $worker = new \Wing\Library\Worker([
            "daemon"  => !!$deamon,
            "debug"   => !!$debug,
            "workers" => $workers
        ]);
        $worker->start();
    }
}