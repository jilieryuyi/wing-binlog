<?php namespace Wing\Binlog\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Help extends ServerBase{
    protected function configure()
    {
        $this
            ->setName('help')
            ->setDescription('帮助信息');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        echo "启动服务：php app server:start\r\n";
        echo "以守护进程方式启动服务：php app server:start --d\r\n";
        echo "重启服务：php app server:restart\r\n";
        echo "停止服务：php app server:stop\r\n";
        echo "服务状态：php app server:status\r\n";
    }
}