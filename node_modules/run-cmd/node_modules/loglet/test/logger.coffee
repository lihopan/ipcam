logger = require '../src/logger'
assert = require 'assert'

class ObjectLogger 
  constructor: () ->
    @out = []
    @err = []
  log: (args...) ->
    @out.push args
  warn: (args...) ->
    @out.push args
  error: (args...) ->
    @err.push args

describe 'logger test', () ->
  
  it 'can remove transport', (done) ->
    try 
      logger.removeTransport console
      done null
    catch e
      done e
  
  transport = null
  
  it 'can add transport', (done) ->
    try
      transport = new ObjectLogger()
      logger.addTransport transport 
      done null
    catch e
      done e
  
  it 'can log', (done) ->
    items = ['this', 'is', 'a', 'test']
    try 
      logger.log items... 
      assert.deepEqual transport.out[transport.out.length - 1], items
      done null
    catch e
      done e

  