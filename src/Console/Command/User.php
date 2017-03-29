<?php namespace Seals\Console\Command;

use Seals\Cache\File;
use Seals\Library\Master;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Command\Command;

class User extends Command
{
    protected function configure()
    {
        $this
            ->setName('user:add')
            ->setAliases(["user:update"])
            ->addOption("name", null, InputOption::VALUE_REQUIRED, "用户名")
            ->addOption("password", null, InputOption::VALUE_REQUIRED, "密码")
            ->addOption("role", null, InputOption::VALUE_REQUIRED, "角色")
            ->setDescription('添加/更新用户，如果添加的用户已经存在则会被覆盖更新');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $name     = $input->getOption("name");
        $password = $input->getOption("password");
        $role     = $input->getOption("role");

        $name     = trim($name);
        $password = trim($password);
        $role     = trim($role);

        if (!$name || !$password || !$role) {
            echo "params error\r\n";
            exit;
        }

        $roles = \Seals\Web\Logic\User::getAllRoles();
        if (!in_array($role, $roles)) {
            echo "warning: role <".$role."> does not exists\r\n";
            echo "system roles:\r\n";
            foreach ($roles as $role) {
                echo "===> ",$role["name"],"\r\n";
            }
           // exit;
        }

        \Seals\Web\Logic\User::add($name, $password, $role);
        echo "--".$name."--\r\n";
        echo "--".$password."--\r\n";
        echo "--".$role."--\r\n";

    }
}