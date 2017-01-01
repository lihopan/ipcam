errorlet = require 'errorlet'

# keys are the list of logging to enable... 
logLevels = 
  debug: 2
  warn: 4
  error: 8

logLevel = logLevels.debug

logKeys = {}

verbosityLevels = 
  whisper: 2 # third level verbosity
  talk: 4 # second level verbosity
  speech: 8 # the first level verbosity
  scream: 16 # can't be turned off... 

enable = (key) ->
  logKeys[key] = new RegExp key
  
disable = (key) ->
  delete logKeys[key]

setLevel = (lvl) ->
  if logLevels.hasOwnProperty(lvl)
    logLevel = logLevels[lvl]

setKeys = (keys = []) ->
  keys = 
    if keys instanceof Array
      keys
    else
      [ keys ]
  for key in keys
    enable(key)
  return

transports = [ ]

contains = (ary, item) ->
  for x in ary
    if item == x 
      return true
  false
  
isFunction = (func) ->
  typeof(func) == 'function' or func instanceof Function

isTransportValid = (logger) ->
  isFunction(logger.log) and isFunction(logger.warn) and isFunction(logger.error)

addTransport = (logger) ->
  if isTransportValid logger
    if not contains transports, logger
      transports.push logger
  else
    errorlet.raise {error: 'loglet.invalid_transport', message: "must have .log, .warn, and .error function."}

addTransport console

removeTransport = (logger) ->
  for x, i in transports
    if logger == x
      transports.splice i, 1

_log = (args...) ->
  for trans in transports
    trans.log args...
  return

_warn = (args...) ->
  for trans in transports 
    trans.warn args...
  return
    
_error = (args...) ->
  for trans in transports 
    trans.error args...
  return

# we take the first as a key... 
debug = (key, args...) ->
  if logLevel <= logLevels.debug 
    for k, regex of logKeys
      if key.match regex
        _log 'DEBUG ----------', key, JSON.stringify(args, null, 2)
  return

whisper = (args...) ->

talk = (args...) ->

speech = (args...) ->

scream = (args...) ->
  _log args...

warn = (args...) ->
  if logLevel <= logLevels.warn
    list = []
    list.push "*** WARN START ***\n"
    for arg in args
      list.push arg
    list.push "*** WARN END ***\n"
    _warn.apply @, list
  return

error = (args...) ->
  list = []
  list.push '****** ERROR START ******\n'
  for arg in args
    list.push arg
    if arg?.stack
      list.push arg.stack
  list.push '****** ERROR END   ******\n'
  _error.apply @, list
  return

croak = (args...) ->
  error args...
  code = -1
  for arg in args 
    if arg?.code
      code = arg.code
  process.exit(code)
  return

module.exports = 
  addTransport: addTransport
  removeTransport: removeTransport
  setLevel: setLevel
  enable: enable
  disable: disable
  setKeys: setKeys
  debug: debug
  warn: warn
  error: error
  log: _log
  whisper: whisper
  talk: talk
  speech: speech
  scream: scream
  croak: croak
