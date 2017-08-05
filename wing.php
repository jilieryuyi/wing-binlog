#!/usr/bin/env php
<?php
//declare(ticks = 1);
require __DIR__.'/vendor/autoload.php';

define("HOME", __DIR__);

date_default_timezone_set("PRC");

use Symfony\Component\Console\Application;

try {

    $application = new Application("wing-binlog");
    $application->setCatchExceptions(true);

    $commands = [
//        \Seals\Console\Command\UnitTest::class,
//        \Seals\Console\Command\ServerStop::class,
//        \Seals\Console\Command\ServerStatus::class,
        \Seals\Console\Command\ServerStart::class,
//        \Seals\Console\Command\ServerVersion::class,
//        \Seals\Console\Command\ServerRestart::class,
//        \Seals\Console\Command\Help::class,
//        \Seals\Console\Command\Config::class,
//        \Seals\Console\Command\MasterStart::class,
//        \Seals\Console\Command\MasterStop::class,
//        \Seals\Console\Command\MasterStatus::class,
//        \Seals\Console\Command\MasterRestart::class,
//        \Seals\Console\Command\User::class,
//        \Seals\Console\Command\Role::class,
//        \Seals\Console\Command\Lang::class

    ];
    foreach ($commands as $command) {
        $application->add(new $command);
    }

    $application->run();
} catch (\Exception $e){
    var_dump($e);
}