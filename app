#!/usr/bin/env php
<?php
require __DIR__.'/vendor/autoload.php';
define("__APP_DIR__",__DIR__);

use Symfony\Component\Console\Application;

try {

    $application = new Application("seals-analysis", "1.0.0");

    $application->setCatchExceptions(true);

    $commands = [
        \Seals\Console\Command\UnitTest::class,
        \Seals\Console\Command\ServerStop::class,
        \Seals\Console\Command\ServerStatus::class,
        \Seals\Console\Command\ServerStart::class,
        \Seals\Console\Command\ServerRestart::class,
        \Seals\Console\Command\Help::class
    ];
    foreach ($commands as $command) {
        $application->add(new $command);
    }

    $application->run();
}catch( \Exception $e )
{
    var_dump($e);
}