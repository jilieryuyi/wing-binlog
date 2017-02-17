<?php namespace Wing\Binlog\Library;
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/2/9
 * Time: 15:48
 *
 * @mysql 日志处理
 */
class MysqlGeneralLog{

    /**
     *  SELECT @@general_log;   0 关闭
        SELECT @@general_log_file;
        set GLOBAL general_log=1;
     */

    private $db_handler;

    public function __construct( DbInterface $db_handler )
    {
        $this->db_handler = $db_handler;
    }
    /**
     * @判断是否已经开启了日志
     *
     * @return bool true 已开启
     */
    public function isOpen(){
        $sql  = 'SELECT @@general_log';
        $data = $this->db_handler->row( $sql );
        return isset($data["@@general_log"]) && $data["@@general_log"] == 1;
    }

    /**
     * @开启全局日志
     *
     * @return bool
     */
    public function open(){
        $sql = 'set GLOBAL general_log=1';
        return !!$this->db_handler->query( $sql );
    }

    /**
     * @关闭全局日志
     *
     * @return bool
     */
    public function close(){
        $sql = 'set GLOBAL general_log=0';
        return !!$this->db_handler->query( $sql );
    }


    public function getLogFile(){
        $sql  = 'SELECT @@general_log_file';
        $data = $this->db_handler->row( $sql );
        return $data["@@general_log_file"];
    }

    public function onChange($callback){

        if( !$this->isOpen() )
        {
            exit("请使用如下sql开启日志：set GLOBAL general_log=1\r\n");
        }

        $file_name      = $this->getLogFile();
        $max_show       = 8912;
        $file_size      = 0;
        $file_size_new  = 0;
        $add_size       = 0;
        $ignore_size    = 0;
        $fp             = fopen($file_name, "r");


        while(1){
            clearstatcache();
            $file_size_new  = filesize($file_name);
            $add_size       = $file_size_new - $file_size;
            if( $add_size > 0 ){
                if( $add_size > $max_show ){
                    $ignore_size    = $add_size - $max_show;
                    $add_size       = $max_show;
                    fseek($fp, $file_size + $ignore_size);
                }

                $new_lines       = fread($fp, $add_size);
                $new_lines       = preg_replace("/[0-9]{4}\-[0-9]{2}[\s\S].*?\s[0-9]{1,}\s/","",$new_lines);
                $new_lines       = explode("\n",$new_lines);
                $prev_words      = ["Connect","Query","Quit"];
                $lines_format    = [];
                $lines_format[0] = array_shift( $new_lines );
                $nli             = 0;

                //第一行是否为标准sql 如果不是，置为空值
                preg_match("/[\w]{1,}/",$lines_format[0],$temp);
                if( !isset($temp[0]) )
                    $lines_format[0] = "";
                else {
                    if (!in_array($temp[0], $prev_words)) {
                        $lines_format[0] = "";
                    }
                }

                //对所有的行进行格式化处理结果存储到$lines_format
                foreach ( $new_lines  as $key => $new_line ){

                    $new_line = trim( $new_line );
                    preg_match("/[\w]{1,}/",$new_line,$temp);

                    //这行没有单词 一般是运算符号或者空行
                    if( !isset($temp[0]) )
                    {
                        $lines_format[$nli] .= " ".$new_lines[$key];
                        continue;
                    }

                    //不是指定前置字符开头的行，追加到上一行sql的结尾，作为一个sql
                    if( !in_array($temp[0],$prev_words) )
                    {
                        $lines_format[$nli] .= " ".trim($new_lines[$key]);
                    }
                    else
                    {
                        //增加新行
                        $nli++;
                        $lines_format[$nli] = $new_lines[$key];
                    }
                }

                unset($new_lines);

                foreach ( $lines_format  as $key => $new_line )
                {
                    if($new_line)
                    {
                        //去除前缀的"Connect","Query","Quit"
                        $new_line = trim($new_line);
                        foreach ( $prev_words as $prev_word )
                            $new_line = ltrim( $new_line, $prev_word );
                        $new_line = trim($new_line);

                        //这里得到的$new_line只是单纯的一个sql
                        echo "new line====>",$new_line,"<====\r\n\r\n";
                    }
                }

                $file_size  = $file_size_new;
            }
            usleep(50000);
        }

        fclose($fp);
    }


}