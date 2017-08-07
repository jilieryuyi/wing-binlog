<?php namespace Wing\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ServerStart extends ServerBase
{
    protected function configure()
    {
        $this
            ->setName('server:start')
            ->setAliases(["start"])
            ->setDescription('服务启动')
            ->addOption("d", null, InputOption::VALUE_NONE, "守护进程")
            ->addOption("debug", null, InputOption::VALUE_NONE, "调试模式")
            //->addOption("clear", null, InputOption::VALUE_NONE, "自动清理日志和缓存")
            ->addOption("n", null, InputOption::VALUE_REQUIRED, "进程数量", 4)
            ->addOption("with-websocket", null, InputOption::VALUE_NONE, "启用websocket服务")
            ->addOption("with-tcp", null, InputOption::VALUE_NONE, "启用tcp服务")
            ->addOption("with-redis", null, InputOption::VALUE_NONE, "启用redis队列服务")

        ;


    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $deamon      = $input->getOption("d");
        $debug       = $input->getOption("debug");
        $workers     = $input->getOption("n");
        $with_ws     = $input->getOption("with-websocket");
        $with_tcp    = $input->getOption("with-tcp");
        $with_redis  = $input->getOption("with-redis");

        $this->startTcpService();
        $this->startWebsocketService();

        $worker = new \Wing\Library\Worker(
            [
                "daemon"         => !!$deamon,
                "debug"          => !!$debug,
                "workers"        => $workers,
                "with_websocket" => $with_ws,
                "with_tcp"       => $with_tcp,
                "with_redis"     => $with_redis
            ]
        );
        $worker->start();
    }

    private function startWebsocketService()
    {
        $command = "php ".HOME."/websocket start";
        $handle  = popen("/bin/sh -c \"".$command."\" >>".HOME."/logs/websocket.log&","r");

        if ($handle) {
            pclose($handle);
        }
    }

    private function startTcpService()
    {
        $command = "php ".HOME."/tcp start";
        $handle  = popen("/bin/sh -c \"".$command."\" >>".HOME."/logs/websocket.log&","r");

        if ($handle) {
            pclose($handle);
        }
    }
}