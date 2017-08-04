<?php
/**
 * redis消费端测试
 */
include __DIR__."/../../vendor/autoload.php";

define("__APP_DIR__",dirname(dirname(__DIR__)));

$list_name = "seals:event:list";

$queue     = new \Seals\Library\Queue($list_name,new \Seals\Library\Redis(
    \Seals\Library\Context::instance()->redis_config["host"],
    \Seals\Library\Context::instance()->redis_config["port"],
    \Seals\Library\Context::instance()->redis_config["password"]
));

while (1) {
    $event = $queue->pop();
    if (!$event) {
        sleep(1);
        continue;
    }

    echo "收到事件\r\n";
    var_dump($event);
    echo "\r\n";
}
