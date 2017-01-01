# Errorlet - a simple customer error library

A simple custom error library for you to use more than just an error message string, because a string sometimes just isn't enough.

# Install

    npm install errorlet
    
# Usage

    var errorlet = require('errorlet');
    
    // make a custom error and throw it.
    throw errorlet.create({error: 'http_error:not_found', message: 'this is the custom error message', httpCode: 404});

    // you can directly raise it as well instead of using throw.
    // the stack will be the same as if you call throw.
    errorlet.raise({error: 'error_code', message: 'this is the custom error message'});

    // you can also do it in async fashion.
    // the stack will be the same as if you call throw.
    errorlet.raiseAsync({error: 'async_error', message: 'you should see this error in the callback'}, function (err) {
      console.log(err);
    });

By default, you can set the name of the error object via `error` or `name` key. If you are used to constructing error objects for JSON API, chances are this approach will look familiar to you.

`message` field can be passed in, or if you leave it out, it will be constructed out of the JSON serialization of the object you passed in.


