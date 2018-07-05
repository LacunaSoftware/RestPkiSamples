const express= require('express');

let router = express.Router();

router.get('/', function(req, res, next) {
   delete req.session.userId;
   req.session.userId = null;
   res.redirect('/');
});

module.exports = router;