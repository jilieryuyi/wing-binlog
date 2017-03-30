<?php namespace Seals\Console\Command;

use Seals\Web\Route;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Command\Command;

class Role extends Command
{
    protected function configure()
    {
        $this
            ->setName('role:admin')
            ->addOption("name", null, InputOption::VALUE_REQUIRED, "角色名称")
            ->setDescription('将角色设置为全部权限');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $name     = $input->getOption("name");

        $name     = trim($name);

        if (!$name) {
            echo "params error\r\n";
            exit;
        }

        $pages = Route::getAll();


        \Seals\Web\Logic\User::roleAdd($name, $pages);

    }
}