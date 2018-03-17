<?php namespace Wing\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Wing\Library\Worker;

class ServerStatus extends ServerBase
{
    protected function configure()
    {
        $this
            ->setName('server:status')
            ->setAliases(["status"])
            ->setDescription('服务状态');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        Worker::showStatus();
        sleep(1);
        echo file_get_contents(HOME."/logs/status.log");
    }
}
