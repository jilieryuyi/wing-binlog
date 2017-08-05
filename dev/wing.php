#!/usr/bin/env php
<?php
//declare(ticks = 1);
require __DIR__.'/vendor/autoload.php';

define("HOME", __DIR__);

$worker = new \Wing\Library\Worker();
$worker->start();