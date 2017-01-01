class AppError extends Error
  @isa: (obj) ->
    obj instanceof @
  @raiseAsync: (params = {}, cb) ->
    err = @create params, @async
    return cb(err);
  @raise: (params = {}, context = AppError.raise) ->
    err = @create params, context
    throw err
  @create: (params = {}, context = AppError.create) ->
    new @ params, context
  constructor: (params = {}, context = AppError) ->
    super()
    if Error.captureStackTrace
      Error.captureStackTrace @, context
    @parameters = params
    if params.hasOwnProperty('error') 
      @name = params.error
    else
      @name = 'AppError'
    if params.hasOwnProperty('message')
      @_message = params.message
    @message = @format()
    @traces = []
  inspect: () ->
    @toString()
  format: () ->
    if (@_message)
      "#{@_message}: #{JSON.stringify(@parameters)}"
    else
      JSON.stringify(@parameters)

module.exports = AppError

