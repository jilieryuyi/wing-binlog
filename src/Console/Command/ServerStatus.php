<?php namespace Seals\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ServerStatus extends ServerBase
{
    protected function configure()
    {
        $this
            ->setName('server:status')
            ->setDescription('服务状态');

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        echo $this->status();
    }
}