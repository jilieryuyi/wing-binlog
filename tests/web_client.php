<?php
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/3/14
 * Time: 11:40
 */
$i = 0;
while( $i <= 1000000 ) {
    echo file_get_contents("http://127.0.0.1:9998/index.php?a=123");
}