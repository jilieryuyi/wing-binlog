<?php namespace Seals\Library;
use Wing\FileSystem\WFile;


/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/4/7
 * Time: 12:15
 */
class Lang
{
    static $ltypes = ["zh","en"];
    static $lang = [
        ["en" => "Welcome",  "zh" => "欢迎"],
        ["en" => "General",  "zh" => "日常"],
        ["en" => "Home",     "zh" => "首页"],
        ["en" => "Servers",  "zh" => "服务器列表"],
        ["en" => "Users",    "zh" => "用户"],
        ["en" => "Roles",    "zh" => "角色"],
        ["en" => "Other",    "zh" => "其他"],
        ["en" => "Logs",     "zh" => "日志"],
        ["en" => "Doing",    "zh" => "正在操作"],
        ["en" => "Success",  "zh" => "成功"],
        ["en" => "Help",     "zh" => "帮助"],
        ["en" => "Total Servers", "zh" => "服务器数量"],
        ["en" => "Total Events", "zh" => "事件数量"],
        ["en" => "Total Logs", "zh" => "日志数量"],
        ["en" => "From last Day", "zh" => "相比昨天"],
        ["en" => "Groups And Servers", "zh" => "服务器列表"],
        ["en" => "manager", "zh" => "管理"],
        ["en" => "Restart Master Process", "zh" => "重启Master进程"],
        ["en" => "Update Master", "zh" => "更新Master"],
        ["en" => "Group", "zh" => "群组"],
        ["en" => "Nodes Count", "zh" => "节点数量"],
        ["en" => "Operate", "zh" => "操作"]

    ];

    public static function parse()
    {
        $path[] = __APP_DIR__.'/web/*';
        $files  = [];
        while (count($path) != 0) {
            $v = array_shift($path);
            foreach(glob($v) as $item) {
                if (is_dir($item)) {
                    if(strpos($item,"/lang") !== false //||
                      //  strpos($item,"/vendors") !== false
                    ) {
                        //echo $item,"\r\n";
                        continue;
                    }
                    $path[] = $item . '/*';
                } elseif (is_file($item)) {
                    $info = pathinfo($item);
                       // continue;
                    //$files[] = $item;
                    $content = $en_content = $zh_content = file_get_contents($item);
                    if (!isset($info['extension']))
                        $info['extension'] = "";
                    if (in_array($info['extension'],["php","js","html"])) {
                        preg_match_all("/__LANG\([\s\S]{1,}?\)/", $content, $matches);
                        if (count($matches[0]) > 0) {
                            echo $item, "\r\n";
                            var_dump($matches[0]);
                            foreach ($matches[0] as $_lang) {



                                $lang = substr($_lang,7,strlen($_lang)-8);//ltrim($_lang, "__LANG(");
                                $en   = $lang;
                                $zh   = $lang;

                                foreach (self::$lang as $_l) {
                                    if ($_l["en"] == $lang ||
                                        $_l["zh"] == $lang
                                    ) {
                                        $en = $_l["en"];
                                        $zh = $_l["zh"];
                                        break;
                                    }
                                }

//                                echo $en,$zh,"\r\n";
//                                if (strpos($_lang,"Logs") !== false)
//                                    exit;
                                $en_content = str_replace($_lang, $en, $en_content);
                                $zh_content = str_replace($_lang, $zh, $zh_content);
                            }
                        }
                    }

                    $target_file = __APP_DIR__."/web/lang/en".str_replace(__APP_DIR__."/web","",$item);
                    $wfile = new WFile($target_file);
                    $wfile->touch();

                    file_put_contents($target_file, $en_content);

                    $target_file = __APP_DIR__."/web/lang/zh".str_replace(__APP_DIR__."/web","",$item);
                    $wfile = new WFile($target_file);
                    $wfile->touch();
                    file_put_contents($target_file, $zh_content);
                    echo "编译完成：";
                    echo $target_file,"\r\n";
                }
            }
        }
        //return $files;

    }
}