const express = require('express');

let router = express.Router();
router.use('/', require('./home'));
router.use('/sign-out', require('./sign-out'));
router.use('/upload', require('./upload'));
router.use('/authentication', require('./authentication'));
router.use('/pades-signature', require('./pades-signature'));
router.use('/open-pades-signature', require('./open-pades-signature'));
router.use('/pades-signature-server-key', require('./pades-signature-server-key'));
router.use('/check', require('./check'));
router.use('/printer-friendly-version', require('./printer-friendly-version'));
router.use('/cades-signature', require('./cades-signature'));
router.use('/open-cades-signature', require('./open-cades-signature'));
router.use('/cades-signature-server-key', require('./cades-signature-server-key'));
router.use('/xml-full-signature', require('./xml-full-signature'));
router.use('/xml-element-signature', require('./xml-element-signature'));

module.exports = router;
