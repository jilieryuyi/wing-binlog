<?php
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/8/16
 * Time: 21:47
 */

preg_match_all('/[\d(\.\d)?]+|[a-zA-Z]{1,}/','100kk',$m);
var_dump($m);