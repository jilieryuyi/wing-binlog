<?php namespace Seals\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Wing\FileSystem\WDir;

class Config extends ServerBase
{
    protected function configure()
    {
        $this
            ->setName('config')
            ->setAliases(["init"])
            ->setDescription('自动初始化配置');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dir = new WDir(__APP_DIR__."/config");
        $files = $dir->scandir();
        foreach ($files as $file) {
            $info = pathinfo($file);
            $target_file = $info["dirname"]."/".$info["filename"];
            if ($info['extension'] != "php" && !file_exists($target_file)) {
                echo "copy ",$file," as ",$target_file,"\r\n";
                copy($file, $target_file);
            }
        }

        echo "init done\r\n";
    }
}