<?php namespace Wing\Binlog\Console\Command;

use Wing\Binlog\Library\Worker;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
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