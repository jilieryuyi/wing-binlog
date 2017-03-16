<?php namespace Seals\Web;
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/3/13
 * Time: 14:41
 */
final class MimeType
{
    public static function getMimeType($path)
    {
        $mime_type = "text/html";

//        if (!class_exists("finfo") && !function_exists("mime_content_type")) {
//            echo "Warning : class \"finfo\" and function \"mime_content_type\" are not found";
//        }
//
//        if( class_exists("finfo") ) {
//            $fi = new \finfo(FILEINFO_MIME_TYPE);
//            $mime_type = $fi->file($path);
//            unset($fi);
//            return $mime_type;
//        }
//
//        if( function_exists("mime_content_type") ) {
//            $mime_type = mime_content_type($path);
//            return $mime_type;
//        }
        $types = [
            "js"  => "application/javascript",
            "css" => "text/css",
            "php" => "text/x-php"
        ];

        $ext = strtolower(pathinfo($path,PATHINFO_EXTENSION));
        if (isset($types[$ext]))
            $mime_type = $types[$ext];

        return $mime_type;
    }
}