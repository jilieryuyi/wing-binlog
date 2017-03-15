<?php namespace Seals\Console\Command;

use Seals\Library\Master;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/3/13
 * Time: 21:05
 */
class MasterStatus extends ServerBase
{
    protected function configure()
    {
        $this
            ->setName('master:status')
            ->setDescription('master服务运行状态');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $http   = new Master();

        echo $http->status();
    }
}