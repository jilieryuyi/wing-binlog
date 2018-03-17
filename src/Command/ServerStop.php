<?php namespace Wing\Command;

use Wing\Library\Worker;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ServerStop extends ServerBase
{
    protected function configure()
    {
        $this
            ->setName('server:stop')
            ->setAliases(["stop"])
            ->setDescription('停止服务');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        exec("php ".HOME."/services/tcp.php stop");
        exec("php ".HOME."/services/websocket.php stop");
        Worker::stopAll();
    }
}
