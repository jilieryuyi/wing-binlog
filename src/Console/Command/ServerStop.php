<?php namespace Seals\Console\Command;

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

class ServerStop extends ServerBase{
    protected function configure()
    {
        $this
            ->setName('server:stop')
            ->setDescription('停止服务');

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->stop();
    }
}