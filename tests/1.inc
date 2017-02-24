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

//preg_match_all("/a[\s\S]{1,}?c/","ab\nca1\n\nca\r\n\t3ccccc",$matches);
//var_dump($matches);

$items = preg_split("/#[\s]{1,}at[\s]{1,}[0-9]{1,}/","# at 166208909
#170223 15:29:53 server id 2  end_log_pos 166208986 CRC32 0xcddec847 	Table_map: `---`.`---` mapped to number 76
# at 166208986
#170223 15:29:53 server id 2  end_log_pos 166209091 CRC32 0x82a5ec93 	Write_rows: table id 76 flags: STMT_END_F
### INSERT INTO `--`.`----`
### SET
###   @1=11503214
###   @2=1116474
###   @3=0
###   @4=1487834993
###   @5=12.23
###   @6=7976.00
###   @7=1
###   @8=1
###   @9=107
###   @10='宝付充值#123'
# at 166209091
#170223 15:29:53 server id 2  end_log_pos 166209183 CRC32 0x15a9cbbe 	Table_map: `---`.`-----` mapped to number 77
# at 166209183
#170223 15:29:53 server id 2  end_log_pos 166209331 CRC32 0xfb260044 	Update_rows: table id 77 flags: STMT_END_F
### UPDATE `----`.`-----`
### WHERE
###   @1=116179
###   @2=1116474
###   @3=12.23
###   @4=0.00
###   @5=0.00
###   @6=0.00
");
echo "new-----items---";
var_dump($items);