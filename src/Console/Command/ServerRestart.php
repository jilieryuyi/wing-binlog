<?php namespace Seals\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ServerRestart extends ServerBase{
    protected function configure()
    {
        $this
            ->setName('server:restart')
            ->setDescription('重新启动');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->restart();
    }
}