AppError = require '../src/index'
{ assert } = require 'chai'
Promise = require 'bluebird'

describe 'app error test', ->

  it 'can create app error', ->
    err = new AppError { foo: 1, bar: 2 }

  it 'can change name', ->
    err = new AppError { error: 'unauthorized', message: 'You are not authorized' }
    assert.equal err.name, 'unauthorized'

  it 'can ensure right stack level', ->
    foo = (context = foo) ->
      new AppError { error: 'top-level', message: 'should not see foo or bar in stack' }, context
    bar = () ->
      foo bar
    err = bar()
    stack = err.stack.split('\n')
    assert.notOk stack[1].match /at foo/
    assert.notOk stack[1].match /at bar/
    assert.ok stack[1].match /at Context/

  it 'can raise error', ->
    foo = (context = foo) ->
      AppError.raise { error: 'top-level', message: 'should not see foo or bar in stack' }, context
    bar = () ->
      foo bar
    try
      bar()
    catch err
      stack = err.stack.split('\n')
      assert.notOk stack[1].match /at foo/
      assert.notOk stack[1].match /at bar/
      assert.ok stack[1].match /at Context/

  it 'can handle error in async fashion', (done) ->
    AppError.raiseAsync { error: 'test', message: 'this is async test' }, (err) ->
      if err
        console.log err.stack
        done null
      else
        AppError.async { error: 'no_async_error', message: 'no async error' }, done

  it 'can deal with callback chain', (done) ->
    foo = (cb) ->
      setImmediate ->
        bar cb
    bar = (cb) ->
      setImmediate ->
        baz cb

    baz = (cb) ->
      setImmediate ->
        AppError.raiseAsync { error: 'test' }, cb

    foo (err) ->
      if err
        console.log err.stack
        done null
      else
        done null

  it 'can deal with promise', (done) ->
    foo = (cb) ->
        #setImmediate ->
        console.log "foo called"
        cb null
    bar = (cb) ->
        #setImmediate ->
        console.log "bar called"
        cb null
    baz = (cb) ->
        #setImmediate ->
        console.log "baz called"
        AppError.raiseAsync { error: 'test' }, cb

    fooAsync = Promise.promisify foo
    barAsync = Promise.promisify bar
    bazAsync = Promise.promisify baz
    fooAsync()
      .then ->
        barAsync()
      .then ->
        bazAsync()
      .catch (err) ->
        console.log err.stack
        done null

