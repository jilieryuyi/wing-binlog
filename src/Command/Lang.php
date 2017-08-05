<?php namespace Seals\Console\Command;

use Seals\Cache\File;
use Seals\Library\Master;
use Seals\Web\Route;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Command\Command;

class Lang extends Command
{
    protected function configure()
    {
        $this
            ->setName('lang:parse')
            ->setDescription('编译语言包');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        \Seals\Library\Lang::parse();
    }
}