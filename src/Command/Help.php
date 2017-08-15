<?php namespace Wing\Command;

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
        echo "执行 php wing start 开启服务进程，可选参数 --d 以守护进程执行， --debug 启用debug模式， --n 指定进程数量\r\n";
        echo "如：php wing start --d --debug --n 8 \r\n";
        echo "更多帮助加QQ群咨询 535218312 \r\n";
    }
}