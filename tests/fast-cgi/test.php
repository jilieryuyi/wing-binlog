<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2017/6/21
 * Time: 16:23
 */
include __DIR__."/Tcp.php";

$tcp = new Tcp("127.0.0.1", 6998);



const FCGI_VERSION           = 1;
const FCGI_BEGIN_REQUEST     = 1;
const FCGI_ABORT_REQUEST     = 2;
const FCGI_END_REQUEST       = 3;
const FCGI_PARAMS            = 4;
const FCGI_STDIN             = 5;
const FCGI_STDOUT            = 6;
const FCGI_STDERR            = 7;
const FCGI_DATA              = 8;
const FCGI_GET_VALUES        = 9;


$tcp->on(Tcp::ON_RECEIVE, function($client, $buffer, $data){
	file_put_contents("D:/123.log", $data, FILE_APPEND);
	//var_dump(unpack("C",$data));

	$headerData = substr($data, 0, 8);

	$headerFormat = 'Cversion/Ctype/nrequestId/ncontentLength/CpaddingLength/x';

	$record = unpack($headerFormat, $headerData);
	var_dump($record);

	if (strlen($data) < 8) {
		return;
	}

	$start = 9;
	$c = 0;

	$back = "";
	while ($start < strlen($data)) {
		//$start += 1;
		//var_dump(unpack("C", substr($data, $start)));
		$flag   = unpack("C", substr($data, $start,1))[1];
		$flag   = ord($flag);
		$start += 2;
		echo "=-===>",( ($flag[1])); echo "\r\n";

		//var_dump($flag);
		//break;


		if ($flag == 1) {
			$namelen = unpack("C", substr($data, $start))[1];
			$namelen = ord($namelen);
			$start += 5;
		} else {
			$namelen = unpack("C", substr($data, $start))[1];
			$namelen = ord($namelen);
			$start += 2;
		}

		echo "namelen ===>",$namelen,"\r\n";

		$flag = unpack("C", substr($data, $start, 1))[1];
		$start += 1;

		if ($flag == 1) {

			$clen = unpack("C", substr($data, $start, 4))[1];
			$clen = ord($clen);
			$start += 5;
		} else {

			$clen = unpack("C", substr($data, $start, 1))[1];
			$clen = ord($clen);
			$start += 2;
		}

		echo "contentlen ===>",$clen,"\r\n";

		$name = substr($data, $start, $namelen);
		$start += $namelen+1;

		$content = substr($data, $start, $clen);
		$start += $clen + 1;



		//echo $namelen,"=>",$name ."=>",$clen,"====>". $content,"\r\n";

	}


	//var_dump($record);
	//var_dump(readNvpair(substr($data,9,strlen($data)-8)));



	$headerData1 = "Status: 200 OK\r\nContent-Type: text/html\r\nContent-Length:5\r\n\r\nhello";

	//$this->writeResponse($requestId, $headerData, $response->getBody());
    $resquestid = $record["requestId"];
	//event_buffer_write($buffer, "hello");// "HTTP/1.1 200 OK\n\nContent-type: text/html\n\nContent-length:5\n\n\n\nhello");


	$contentLength = strlen($headerData1);
	$headerData    = pack('CCnnxx', FCGI_VERSION, FCGI_STDOUT, $resquestid, $contentLength);

	event_buffer_write($buffer, $headerData.$headerData1);

});

$tcp->on(TCP::ON_WRITE,function($client, $buffer){
	fclose($client);
	event_buffer_free($buffer);
});

$tcp->start();