<?php
error_reporting(E_ALL);

echo "TCP/IP Connection\n";

/* Get the port for the rtsp service. */
$service_port = getservbyname('rtsp', 'tcp');

$address = '1.36.35.222';
$url = 'rtsp://1.36.35.222/11';

/* Create a TCP/IP socket. */
$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
if ($socket === false) {
    echo "socket_create() failed: reason: " . socket_strerror(socket_last_error()) . "\n";
} else {
    echo "OK.\n";
}

echo "Attempting to connect to '$address' on port '$service_port'...";
$result = socket_connect($socket, $address, $service_port);
if ($result === false) {
    echo "socket_connect() failed.\nReason: ($result) " . socket_strerror(socket_last_error($socket)) . "\n";
} else {
    echo "OK.\n";
}

$in = "OPTIONS ".$url." RTSP/1.0\r\n";
$in .= "CSeq: 1\r\n";
$in .= "User-Agent: GStreamer/1.6.2\r\n";
$in .= "ClientChallenge: 9e26d33f2984236010ef6253fb1887f7\r\n";
$in .= "CompanyID: KnKV4M4I/B2FjJ1TToLycw==\r\n";
$in .= "GUID: 00000000-0000-0000-0000-000000000000\r\n";
$in .= "Date: Web, 31 Aug 2016 16:34:12 GMT\r\n\r\n";

$out = '';

echo "Sending RTSP HEAD request...";
socket_write($socket, $in, strlen($in));
echo "OK.\n";

echo "Reading response:\n\n";
$out = socket_read($socket, 2048);
echo $out;

$in = "DESCRIBE ".$url." RTSP/1.0\r\n";
$in .= "CSeq: 2\r\n";
$in .= "User-Agent: GStreamer/1.6.2\r\n";
$in .= "Accept: application/sdp\r\n";
$in .= "Date: Web, 31 Aug 2016 16:34:12 GMT\r\n\r\n";
$out = '';

echo "Sending RTSP HEAD request...";
socket_write($socket, $in, strlen($in));
echo "OK.\n";

echo "Reading response:\n\n";
$out = socket_read($socket, 2048);
echo $out;

$realm = substr($out,strpos($out,'realm="')+7);
$realm = substr($realm,0,strpos($realm,'"'));
echo "realm : ".$realm."\n";

$nonce = substr($out,strpos($out,'nonce="')+7);
$nonce = substr($nonce,0,strpos($nonce,'"'));
echo "nonce : ".$nonce."\n";

$ha1 = md5("admin:".$realm.":admin");
$ha2 = md5("DESCRIBE:".$url);
$response = md5($ha1.":".$nonce.":".$ha2);
echo "response : ".$response."\n";

$in = "DESCRIBE ".$url." RTSP/1.0\r\n";
$in .= "CSeq: 3\r\n";
$in .= "User-Agent: GStreamer/1.6.2\r\n";
$in .= "Accept: application/sdp\r\n";
$in .= "Authorization: Digest username=\"admin\", ";
$in .= "realm=\"".$realm."\", ";
$in .= "nonce=\"".$nonce."\", ";
$in .= "url=\"".$url."\", ";
$in .= "response=\"".$response."\"\r\n";
$in .= "Date: Web, 31 Aug 2016 16:34:12 GMT\r\n\r\n";

echo "Write $in";

echo "Sending RTSP HEAD request...";
socket_write($socket, $in, strlen($in));
echo "OK.\n";

echo "Reading response:\n\n";
$out = socket_read($socket, 2048);
echo $out;

echo "Closing socket...";
socket_close($socket);
echo "OK.\n\n";
?>
