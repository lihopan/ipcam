# Loglet - A Simple Logger.

`Loglet` is a simple configurable logger that can be configured to conditionally log a particular output. This is especially useful to be combined with command line parameter (or configuration files) to determine the output you are logging. 

The scenario loglet is designed for is for people who use `console.log` to trace through the program and has to frequently comment/uncomment them to see the different parts. By using `loglet` you can leave the programs to be and just enable/disable the comments by passing in settings, which are treated as regular expressions. 

## Install

    npm install loglet 


## Usage 

    var logger = require('loglet');
    
    logger.setKeys(['main', 'test']); // used to determine what to log. 
    
    logger.debug('main.first', 'hello world - this will be logged'); 
    
    logger.debug('test.first', 'this will also be logged');
    
    logger.debug('skip.me', 'this will not be logged');
    
    logger.error({error: 'stuff_happens', description: 'Errors will also be logged'});
    


## Desciption 

Do you use `console.log` to trace and debug the output of the program? If you do you'll find `console.log` is littered throughout the code, but you'll have to constantly go back and forth to comment/uncomment out the logging. `Loglet` is a replacement for those logging calls that allows you to leave those code alone but you can turn them on/off based on settings you pass in for the program. 

Let's say you use either `optimist` or `yarg` to parse the command line. You can then collect the debug arguments as follows: 

    var argv = require('yargs')
      .alias('d', 'debug')
      .argv;
    
    var logger = require('loglet');
    
    logger.setKeys(argv.d);
    
    logger.debug(...); // logger.debug is the main function that you'll use for conditional logging. 
    
    ...
    


To change what's being logged, just pass in different sets of keys through the command line. The keys are regular expressions, so you can have fine control over what's actually logged. 

    program -d main -d test\\. 






