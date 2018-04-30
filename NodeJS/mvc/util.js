var fs = require('fs');
var crypto = require('crypto');

var appRoot = process.cwd();

// ------------------------------------------------------------------------------------------------
// PLACE YOUR API ACCESS TOKEN BELOW
var accessToken = 'PLACE YOUR API ACCESS TOKEN HERE';
//                 ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
// ------------------------------------------------------------------------------------------------

var restPkiUrl = 'https://pki.rest/';

// Throw exception if token is not set (this check is here just for the sake of
// newcomers, you can remove it).
if (!accessToken || accessToken.indexOf(' API ') >= 0) {
   throw 'The API access token was not set! Hint: to run this sample you must generate an API access token on the REST PKI website and paste it on the file util.js';
}

module.exports = {
   accessToken: accessToken,
   endpoint: restPkiUrl,
   setExpiredPage: function(res) {
      res.set({
         'Cache-Control': 'private, no-store, max-age=0, no-cache, must-revalidate, post-check=0, pre-check=0',
         'Pragma': 'no-cache'
      });
   },
   getPdfStampContent: function() {
      return fs.readFileSync(appRoot + '/resources/PdfStamp.png');
   },
   getSamplePdfContent: function() {
      return fs.readFileSync(appRoot + '/public/SampleDocument.pdf');
   },
   getSampleXml: function() {
      return fs.readFileSync(appRoot + '/public/SampleDocument.xml');
   },
   getSampleNFe: function() {
      return fs.readFileSync(appRoot + '/public/SampleNFe.xml');
   },
   getValidationResultIcon: function(isValid) {
      var filename = isValid ? 'ok.png' : 'not-ok.png';
      return fs.readFileSync(appRoot + '/public/' + filename);
   },
   getIcpBrasilLogoContent: function() {
      return fs.readFileSync(appRoot + '/public/icp-brasil.png');
   },
   joinStringPt: function(strings) {
      var text = '';
      var count = strings.length;
      for (var i = 0; i < strings.length; i++) {
         if (i > 0) {
            if (i < count - 1) {
               text += ', ';
            } else {
               text += ' e ';
            }
         }
         text += strings[i];
      }
      return text;
   },
   formatCpf: function(cpf) {

      if (!cpf) {
         return '';
      }

      return cpf.substring(0, 3) + '.' + cpf.substring(3, 6) + '.' +
          cpf.substring(6, 9) + '-' + cpf.substring(9);
   },
   formatCnpj: function(cnpj) {

      if (!cnpj) {
         return '';
      }

      return cnpj.substring(0, 2) + '.' + cnpj.substring(2, 5) + '.' +
          cnpj.substring(5, 8) + '/' + cnpj.substring(8, 12)  + '-' +
          cnpj.substring(12);
   },
   generateVerificationCode: function() {

      /*
       * Configuration of the code generation
       * ------------------------------------
       *
       * - CodeSize   : size of the code in characters
       *
       * Entropy
       * -------
       *
       * The resulting entropy of the code in bits is the size of the code
       * times 4. Here are some suggestions:
       *
       * - 12 characters = 48 bits
       * - 16 characters = 64 bits
       * - 20 characters = 80 bits
       * - 24 characters = 92 bits
       */
      var codeSize = 16;

      // Generate the entropy with Node.js Crypto's cryptographically strong
      // pseudo-random generation function.
      var numBytes = Math.floor(codeSize / 2);
      var randBuffer = crypto.randomBytes(numBytes);

      return randBuffer.toString('hex').toUpperCase();
   },
   formatVerificationCode: function(code) {
      /*
       * Examples
       * --------
       *
       * - codeSize = 12, codeGroups = 3 : XXXX-XXXX-XXXX
       * - codeSize = 12, codeGroups = 4 : XXX-XXX-XXX-XXX
       * - codeSize = 16, codeGroups = 4 : XXXX-XXXX-XXXX-XXXX
       * - codeSize = 20, codeGroups = 4 : XXXXX-XXXXX-XXXXX-XXXXX
       * - codeSize = 20, codeGroups = 5 : XXXX-XXXX-XXXX-XXXX-XXXX
       * - codeSize = 25, codeGroups = 5 : XXXXX-XXXXX-XXXXX-XXXXX-XXXXX
       */
      var codeGroups = 4;

      // Return the code separated in groups
      var charsPerGroup = (code.length - code.length % codeGroups) / codeGroups;
      var text = '';
      for (var i = 0; i < code.length; i++) {
         if (i !== 0 && i % charsPerGroup === 0) {
            text += '-';
         }
         text += code[i];
      }

      return text;
   },
   parseVerificationCode: function(code) {
      var text = '';
      for (var i = 0; i < code.length; i++) {
         if (code[i] !== '-') {
            text += code[i];
         }
      }

      return text;
   }
};