<?php
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/2/15
 * Time: 15:34
 */
//preg_match_all("/`[\s\S].*?`.`[\s\S].*?`/", "sdfv`abc`.`a`df`gf", $match_tables);
//var_dump($match_tables[0][0]);
//
//list($database_name,$table_name)=explode(".",$match_tables[0][0]);
//$database_name = trim($database_name,"`");
//$table_name = trim($table_name,"`");
//
//
//echo $database_name,"==>",$table_name;

preg_match_all("/a[\s\S]{1,}?c/","ab\nca1\n\nca\r\n\t3ccccc",$matches);
var_dump($matches);