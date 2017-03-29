<?php
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/3/14
 * Time: 11:40
 */
$i = 0;
while( $i <= 1000000 ) {
    echo file_get_contents("http://114.55.56.167:9998/");
}