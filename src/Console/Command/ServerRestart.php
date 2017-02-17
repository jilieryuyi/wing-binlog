<?php namespace Wing\Binlog\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Wing\FileSystem\WDir;
use Wing\FileSystem\WFile;
use Wing\Library\Module;
use Wing\Library\Modules;
use Wing\Library\WArray;

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