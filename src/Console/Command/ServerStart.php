<?php namespace Seals\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ServerStart extends ServerBase{
    protected function configure()
    {
        $this
            ->setName('server:start')
            ->setDescription('服务启动')
            ->addOption("d",null,InputOption::VALUE_NONE,"守护进程")
            ->addOption("debug",null,InputOption::VALUE_NONE,"调试模式")
            ->addOption("clear",null,InputOption::VALUE_NONE,"自动清理日志和缓存")
            ->addOption("n",null,InputOption::VALUE_REQUIRED,"进程数量",0);

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $deamon      = $input->getOption("d");
        $debug       = $input->getOption("debug");
        $clear       = $input->getOption("clear");

        $workers     = $input->getOption("n");
        $this->start( $deamon, $workers, $debug, $clear );
    }
}