<?php
/**
 * queue.php
 * User: huangxiaoan
 * Created: 2017/8/4 14:43
 * Email: huangxiaoan@xunlei.com
 */
$start = time();
include_once  __DIR__."/../src/Library/Queue.php";

$queue = new \Wing\Library\Queue("wing");
for($i=0;$i<1000000;$i++)
$queue->push(rand(0,999999));

$queue->save();

while($data = $queue->pop()) {
	//echo $data,"\r\n";
}

$queue->save();

echo "耗时：",time()-$start,"秒\r\n";