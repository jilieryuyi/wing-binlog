<?php namespace Seals\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ServerVersion extends ServerBase
{
    protected function configure()
    {
        $this
            ->setName('server:version')
            ->setDescription('版本号');

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        echo "wing-binlog version ",$this->version(),"\r\n";
    }
}