let express = require('express');
let session = require('express-session');
let path = require('path');
let logger = require('morgan');
let bodyParser = require('body-parser');

// Create global app object
let app = express();

// View engine setup
app.set('views', path.join(__dirname, 'views'));
app.set('view engine', 'pug');

app.use(logger('dev'));
app.use(bodyParser.json());
app.use(bodyParser.urlencoded({extended: false}));

app.use(express.static(path.join(__dirname, 'public')));

app.use(session({ secret: 'keyboard cat', saveUninitialized: true, resave: true, store: new session.MemoryStore() }));

// Add middleware to add session and environment info to locals.
app.use(function(req, res, next) {
   res.locals.userId = req.session.userId;
   res.locals.environment = app.get('env');
   next();
});

app.use(require('./routes'));

// catch 404 and forward to error handler
app.use(function(req, res, next) {
   let err = new Error('Not Found');
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
