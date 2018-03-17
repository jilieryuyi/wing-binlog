<?php namespace Wing\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Wing\Library\Worker;

class ServerVersion extends ServerBase
{
    protected function configure()
    {
        $this
            ->setName('server:version')
            ->setAliases(["version"])
            ->setDescription('版本号');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        echo "wing-binlog版本号 : ",Worker::VERSION,"\r\n";
        echo "作者 : yuyi\r\n";
        echo "邮箱 : 297341015@qq.com\r\n";
        echo "QQ群 : 535218312\r\n";
    }
}
