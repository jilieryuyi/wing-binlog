<?php
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/6/24
 * Time: 10:18
 */
class Header 
{
    protected $version;
    protected $type;
    protected $requestId;
    protected $contentLength;
    protected $paddingLength;

    public function __construct($raw)
    {
        $headerFormat = 'Cversion/Ctype/nrequestId/ncontentLength/CpaddingLength/x';
        list($this->version, $this->type,
            $this->requestId, $this->contentLength,
            $this->paddingLength) = unpack($headerFormat, $raw);

    }

    public function getVersion()
    {
        return $this->version;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getRequestId()
    {
        return $this->requestId;
    }

    public function getContentLength()
    {
        return $this->requestId;
    }

    public function getPaddingLength()
    {
        return $this->paddingLength;
    }
}