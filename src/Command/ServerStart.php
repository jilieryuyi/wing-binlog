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

        if ($with_tcp) {
        	$this->startTcpService($deamon, $workers);
		}

		if ($with_ws) {
			$this->startWebsocketService($deamon, $workers);
		}

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

    private function startWebsocketService($deamon, $workers)
    {
        $config = load_config("app");
        $host = isset($config["websocket"]["host"])?$config["websocket"]["host"]:"0.0.0.0";
        $port = isset($config["websocket"]["port"])?$config["websocket"]["port"]:9998;

        $command = "php ".HOME."/services/websocket start --host=".$host." --port=".$port." --workers=".$workers;
        if ($deamon) {
        	$command .= " -d";
		}
		echo $command,"\r\n";
        $handle  = popen("/bin/sh -c \"".$command."\" >>".HOME."/logs/websocket.log&","r");

        if ($handle) {
            pclose($handle);
        }
    }

    private function startTcpService($deamon, $workers)
    {
        $config = load_config("app");
        $host = isset($config["tcp"]["host"])?$config["tcp"]["host"]:"0.0.0.0";
        $port = isset($config["tcp"]["port"])?$config["tcp"]["port"]:9997;

        $command = "php ".HOME."/services/tcp start --host=".$host." --port=".$port." --workers=".$workers;
		if ($deamon) {
			$command .= " -d";
		}
        echo $command,"\r\n";

        $handle  = popen("/bin/sh -c \"".$command."\" >>".HOME."/logs/tcp.log&","r");

        if ($handle) {
            pclose($handle);
        }
    }
}