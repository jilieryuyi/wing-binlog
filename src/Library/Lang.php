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
    /**
     * @语言包编译
     */
    public static function parse()
    {
        $langs  = include __APP_DIR__."/config/lang.php.inc";
        $path[] = __APP_DIR__.'/web/*';

        while (count($path) != 0) {
            $v = array_shift($path);
            foreach(glob($v) as $item) {
                if (is_dir($item)) {
                    if (strpos($item, "/lang") !== false) {
                        continue;
                    }
                    $path[] = $item . '/*';
                }
                elseif (is_file($item)) {
                    $info    = pathinfo($item);
                    $content = $en_content = $zh_content = file_get_contents($item);

                    if (!isset($info['extension'])) {
                        $info['extension'] = "";
                    }

                    if (in_array($info['extension'], ["php", "js", "html"])) {
                        preg_match_all("/__LANG\([\s\S]{1,}?\)/", $content, $matches);
                        if (count($matches[0]) > 0) {
                            echo $item, "\r\n";
                            var_dump($matches[0]);
                            foreach ($matches[0] as $_lang) {
                                $lang = substr($_lang, 7, strlen($_lang) - 8);//ltrim($_lang, "__LANG(");
                                $en   = $lang;
                                $zh   = $lang;

                                foreach ($langs as $_l) {
                                    foreach (self::$ltypes as $ltype) {
                                        if (!isset($_l[$ltype]))
                                            $_l[$ltype] = null;
                                    }
                                    if (strtolower(trim($_l["en"])) == strtolower(trim($lang)) ||
                                        strtolower(trim($_l["zh"])) == strtolower(trim($lang))
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

                    $target_file = __APP_DIR__ . "/web/lang/en" . str_replace(__APP_DIR__ . "/web", "", $item);
                    $wfile       = new WFile($target_file);
                    $wfile->touch();

                    file_put_contents($target_file, $en_content);

                    $target_file = __APP_DIR__ . "/web/lang/zh" . str_replace(__APP_DIR__ . "/web", "", $item);
                    $wfile       = new WFile($target_file);
                    $wfile->touch();
                    file_put_contents($target_file, $zh_content);
                    echo "编译完成：";
                    echo $target_file, "\r\n";
                }
            }
        }
    }
}