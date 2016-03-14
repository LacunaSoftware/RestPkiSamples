/*
 * This file allows the user to upload a file to be signed. Once the file is uploaded, we save it to the
 * "public/app-data" folder and redirect the user to the /pades-signature route passing the filename on
 * the "userfile" URL argument.
 */
var express = require('express');
var request = require('request');
var fs = require('fs');
var uuid = require('node-uuid');
var path = require('path');
var multer = require('multer');

var upload = multer();
var router = express.Router();
var appRoot = process.cwd();

router.get('/', function(req, res, next) {
	res.render('upload');
});

router.post('/', upload.single('userfile'), function(req, res, next) {
	
	// Generate a unique filename with the original extension
	var fileExt = path.extname(req.file.originalname);
	var filename = uuid.v4() + fileExt;
	
	// make sure the "public/app-data" folder exists
	var appDataPath = appRoot + '/public/app-data/';
	if (!fs.existsSync(appDataPath)){
		fs.mkdirSync(appDataPath);
	}
	
	// Redirect the user to the signature route, passing the name of the file as a URL argument
	fs.writeFileSync(appDataPath + filename, req.file.buffer);
	res.redirect(req.query.goto + '?userfile=' + filename);
});

module.exports = router;
