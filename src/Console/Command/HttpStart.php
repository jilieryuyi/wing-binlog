<?php namespace Seals\Console\Command;

use Seals\Library\Http;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/3/13
 * Time: 21:05
 */
class HttpStart extends ServerBase
{
    protected function configure()
    {
        $this
            ->setName('http:start')
            ->addOption("d", null, InputOption::VALUE_NONE, "守护进程")
            ->setDescription('启动http服务');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $deamon = $input->getOption("d");
        $http   = new Http();

        if ($deamon) {
            $http->enableDeamon();
        }

        $http->start();
    }
}