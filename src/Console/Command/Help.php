<?php namespace Seals\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Help extends ServerBase
{
    protected function configure()
    {
        $this
            ->setName('help')
            ->setDescription('帮助信息');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        echo "启动服务：php seals server:start\r\n";
        echo "以守护进程方式启动服务：php seals server:start --d\r\n";
        echo "重启服务：php seals server:restart\r\n";
        echo "停止服务：php seals server:stop\r\n";
        echo "服务状态：php seals server:status\r\n";
    }
}