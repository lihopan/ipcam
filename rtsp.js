var mb5 = require('js-md5');

var net = require('net');
var client = new net.Socket();
var url = "rtsp://1.36.35.222/11";

console.log('Connecting');

client.on('data',function(data){
	console.log('Data: ' + data);
	if(data.indexOf("CSeq: 1") > -1){
		describe1();
	} else if(data.indexOf("CSeq: 2") > -1){
		describe2(data);
	} else if(data.indexOf("CSeq: 3") > -1){
		client.destroy();
	}
});

client.connect(554,'1.36.35.222',function(){
		options();
});

function options() {

    var req = "OPTIONS "+url+" RTSP/1.0\r\n"
                +"CSeq: 1\r\n\r\n";
    client.write(req);
} 

function describe1() {
	
    var req = "DESCRIBE "+url+" RTSP/1.0\r\n"
                +"CSeq: 2\r\n\r\n";
	client.write(req);
}

function describe2(data) {
	data = String(data);
	
	var realm = data.substr(data.indexOf("realm=\"")+7);
	realm = realm.substr(0,realm.indexOf("\""));
	console.log("realm : " + realm);

	var nonce = data.substr(data.indexOf("nonce=\"")+7);
	nonce = nonce.substr(0,nonce.indexOf("\""));
	console.log("nonce : " + nonce);

	var ha1 = mb5("admin:"+realm+":admin");
	var ha2 = mb5("DESCRIBE:"+url);
	var response = mb5(ha1+":"+nonce+":"+ha2);
	console.log("HA1 : "+ha1);
	console.log("HA2 : "+ha2);
	console.log("response : "+response);
	var req2 = "DESCRIBE "+url+" RTSP/1.0\r\n"	
			+"CSeq: 3\r\n"
			+"User-Agent: Videos/3.18.1\r\n"
			+"Accept: application/sdp\r\n"
			+"Authorization: Digest username=\"admin\","
			+" realm=\""+realm
			+"\", nonce=\""+nonce
			+"\", url=\""+url
			+"\", response=\""+response+"\"\r\n"
			+"\r\n";
	console.log(req2);
	client.write(req2);
}

