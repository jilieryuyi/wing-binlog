<?php namespace Seals\Console\Command;

use Seals\Cache\File;
use Seals\Library\Master;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Command\Command;

/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/3/13
 * Time: 21:05
 */
class MasterStart extends Command
{
    protected function configure()
    {
        $this
            ->setName('master:start')
            ->addOption("d", null, InputOption::VALUE_NONE, "守护进程")
            ->setDescription('启动master服务')
            ->addOption("debug", null, InputOption::VALUE_NONE, "调试模式")
            ->addOption("with-websocket", null, InputOption::VALUE_NONE, "启用websocket服务")
            ->addOption("with-tcp", null, InputOption::VALUE_NONE, "启用tcp服务")
            ->addOption("with-redis", null, InputOption::VALUE_NONE, "启用redis队列服务")
        ;

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $deamon     = $input->getOption("d");
        $debug      = $input->getOption("debug");
        $with_ws    = $input->getOption("with-websocket");
        $with_tcp   = $input->getOption("with-tcp");
        $with_redis = $input->getOption("with-redis");

        $http    = new Master();

        if ($deamon) {
            $http->enableDeamon();
        }

        if ($debug) {
            $http->enabledDebug();
        }

        $file = new File(__APP_DIR__);
        $file->set("master.info",[$deamon,$debug]);
        unset($file);

        $http->start();
    }
}