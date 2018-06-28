var express = require('express'),
    session = require('express-session'),
    path = require('path'),
    logger = require('morgan'),
    bodyParser = require('body-parser');

// Create global app object
var app = express();

// View engine setup
app.set('views', path.join(__dirname, 'views'));
app.set('view engine', 'jade');

app.use(logger('dev'));
app.use(bodyParser.json());
app.use(bodyParser.urlencoded({extended: false}));

app.use(express.static(path.join(__dirname, 'public')));

app.use(session({ secret: 'keyboard cat', saveUninitialized: true, resave: true, store: new session.MemoryStore() }));

// Add middleware to add session to locals.
app.use(function(req, res, next) {
   res.locals.userId = req.session.userId;
   next();
});

app.use(require('./routes'));

// catch 404 and forward to error handler
app.use(function(req, res, next) {
   var err = new Error('Not Found');
   err.status = 404;
   next(err);
});

// error handlers

// development error handler
// will print stacktrace
if (app.get('env') === 'development') {
   app.use(function(err, req, res, next) {
      res.status(err.status || 500);
      res.render('error', {
         message: err.message,
         error: err
      });
   });
}

// production error handler
// no stacktraces leaked to user
app.use(function(err, req, res, next) {
   res.status(err.status || 500);
   res.render('error', {
      message: err.message,
      error: {}
   });
});

module.exports = app;
