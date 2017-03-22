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

        $types = [
            "js"   => "application/javascript",
            "css"  => "text/css",
            "php"  => "text/x-php",
            "gif"  => "image/gif",
            "jpg"  => "image/jpeg",
            "jpeg" => "image/jpeg",
            "png"  => "image/png",
            "woff" => "application/font-woff",
            "svg"  => "image/svg+xml",
            "ttf"  => "application/octet-stream",
            "woff2"=> "font/woff2"
        ];

        $ext = strtolower(pathinfo($path,PATHINFO_EXTENSION));
        if (isset($types[$ext]))
            return $types[$ext];

        if (!class_exists("finfo") && !function_exists("mime_content_type")) {
            echo "Warning : class \"finfo\" and function \"mime_content_type\" are not found";
        }

        if( class_exists("finfo") ) {
            $fi = new \finfo(FILEINFO_MIME_TYPE);
            $mime_type = $fi->file($path);
            unset($fi);
            return $mime_type;
        }

        if( function_exists("mime_content_type") ) {
            $mime_type = mime_content_type($path);
            return $mime_type;
        }
        return $mime_type;
    }
}