#!/usr/bin/env php
<?php
require __DIR__.'/vendor/autoload.php';
define("__APP_DIR__",__DIR__);

use Symfony\Component\Console\Application;

try {

    $application = new Application("seals-analysis", "1.0.0");

    $application->setCatchExceptions(true);

    $commands = [
        \Wing\Binlog\Console\Command\UnitTest::class,
        \Wing\Binlog\Console\Command\ServerStop::class,
        \Wing\Binlog\Console\Command\ServerStatus::class,
        \Wing\Binlog\Console\Command\ServerStart::class,
        \Wing\Binlog\Console\Command\ServerRestart::class,
        \Wing\Binlog\Console\Command\Help::class
    ];
    foreach ($commands as $command) {
        $application->add(new $command);
    }

    $application->run();
}catch( \Exception $e )
{
    var_dump($e);
}