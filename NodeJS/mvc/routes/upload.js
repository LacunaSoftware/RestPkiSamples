/*
 * This file allows the user to upload a file to be signed. Once the file is
 * uploaded, we save it to the * "public/app-data" folder and redirect the user
 * to the /pades-signature route passing the filename on the "userfile" URL
 * argument.
 */
const express = require('express');
const fs = require('fs');
const uuidv4 = require('uuid/v4');
const path = require('path');
const multer = require('multer');

let upload = multer();
let router = express.Router();
let appRoot = process.cwd();

router.get('/', function(req, res, next) {
   res.render('upload');
});

router.post('/', upload.single('userfile'), function(req, res, next) {

   // Generate a unique filename with the original extension.
   let fileExt = path.extname(req.file.originalname);
   let filename = uuidv4() + fileExt;

   // make sure the "public/app-data" folder exists.
   let appDataPath = appRoot + '/public/app-data/';
   if (!fs.existsSync(appDataPath)) {
      fs.mkdirSync(appDataPath);
   }

   // Redirect the user to the signature route, passing the name of the file as
   // a URL argument.
   fs.writeFileSync(appDataPath + filename, req.file.buffer);
   res.redirect(req.query.goto + '?userfile=' + filename);
});

module.exports = router;
