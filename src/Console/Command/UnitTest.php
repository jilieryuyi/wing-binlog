<?php namespace Wing\Binlog\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
/**
 * @author yuyi
 * @created 2016/12/2 22:11
 * @email 297341015@qq.com
 */
class UnitTest extends Command{
    protected function configure()
    {
        $this
            ->setName('unit:test')
            ->setDescription('单元测试')
            ->addOption("file",null,InputOption::VALUE_REQUIRED,"进程数量",1);

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        if (!ini_get('date.timezone')) {
            ini_set('date.timezone', 'PRC');
        }
        $file = $input->getOption("file");
        $_SERVER["argv"] = ["vendor/phpunit/phpunit/phpunit",$file];

        \PHPUnit_TextUI_Command::main();
    }

}