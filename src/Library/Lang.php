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
    static $lang = [
        ["en"=>"Welcome","zh"=>"欢迎"]
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
                    if (in_array($info['extension'],["php","js","html"])) {
                        preg_match_all("/__LANG\([\s\S]{1,}?\)/", $content, $matches);
                        if (count($matches[0]) > 0) {
                            echo $item, "\r\n";
                            var_dump($matches[0]);
                            foreach ($matches[0] as $_lang) {
                                $lang = ltrim($_lang, "__LANG(");
                                $lang = rtrim($lang, ")");
                                $lang = trim($lang, '"');
                                $lang = trim($lang, '\'');
                                $lang = trim($lang);

                                $en = $lang;
                                $zh = $lang;

                                foreach (self::$lang as $_l) {
                                    if ($_l["en"] == $lang ||
                                        $_l["zh"] == $lang
                                    ) {
                                        $en = $_l["en"];
                                        $zh = $_l["zh"];
                                        break;
                                    }
                                }

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
                echo $target_file,"\r\n";
                }
            }
        }
        //return $files;

    }
}