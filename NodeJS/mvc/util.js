const fs = require('fs');
const crypto = require('crypto');
const { RestPkiClient, StandardSecurityContexts } = require('restpki-client');

class Util {

   static getRestPkiClient() {

      // -----------------------------------------------------------------------
      // PLACE YOUR API ACCESS TOKEN BELOW
      let accessToken = 'PLACE YOUR API ACCESS TOKEN HERE';
      //                 ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
      // -----------------------------------------------------------------------

      // Throw exception if token is not set (this check is here just for the
      // sake of newcomers, you can remove it).
      if (!accessToken || accessToken.indexOf(' API ') >= 0) {
         throw new Error('The API access token was not set! Hint: to run this sample you must generate an API access token on the REST PKI website and paste it on the file util.js');
      }

      // -----------------------------------------------------------------------
      // IMPORTANT NOTICE: in production code, you should use HTTPS to
      // communicate with REST PKI, otherwise your API access token, as well as
      // the documents you sign, will be sent to REST PKI unencrypted.
      // -----------------------------------------------------------------------
      let restPkiUrl = 'https://pki.rest/';

      return new RestPkiClient(restPkiUrl, accessToken);
   }

   /**
    * This method is called by all pages to determine the security context to be
    * used.
    *
    * Security contexts dictate witch root certification authorities are trusted
    * during certificate validation. In you API calls, you can use on of the
    * standard security contexts or reference one of your custom contexts.
    */
   static getSecurityContextId() {

      if (app.get('env') === 'development') {

         /*
          * Lacuna Text PKI (for development purposes only!)
          *
          * This security context trusts ICP-Brasil certificates as well as
          * certificates on Lacuna Software's test PKI. Use it to accept the
          * test certificates provided by Lacuna Software.
          *
          * THIS SHOULD NEVER BE USED ON A PRODUCTION ENVIRONMENT!
          */
         return StandardSecurityContexts.lacunaTest;
         // Notice for On Premises users: this security context might not exist
         // on your installation, if you encounter an error please contact
         // developer support.
      }

      // In production, accepting only certificates from ICP-Brasil
      return StandardSecurityContexts.pkiBrazil;
   }

   static createAppData() {
      let appDataPath = appRoot + '/public/app-data/';
      if (!fs.existsSync(appDataPath)) {
         fs.mkdirSync(appDataPath);
      }
   }

   static setExpiredPage(res) {
      res.set({
         'Cache-Control': 'private, no-store, max-age=0, no-cache, must-revalidate, post-check=0, pre-check=0',
         'Pragma': 'no-cache'
      });
   }

   static getPdfStampContent() {
      return fs.readFileSync(appRoot + '/resources/PdfStamp.png');
   }

   static getSamplePdfPath() {
      return appRoot + '/public/SampleDocument.pdf';
   }

   static getSampleXmlPath() {
      return appRoot + '/public/SampleDocument.xml';
   }

   static getSampleNFePath() {
      return appRoot + '/public/SampleNFe.xml';
   }

   static getValidationResultIcon(isValid) {
      let filename = isValid ? 'ok.png' : 'not-ok.png';
      return fs.readFileSync(appRoot + '/public/' + filename);
   }

   static getIcpBrasilLogoContent() {
      return fs.readFileSync(appRoot + '/public/icp-brasil.png');
   }

   static joinStringPt(strings) {
      let text = '';
      let count = strings.length;
      for (let i = 0; i < strings.length; i++) {
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
   }

   static formatCpf(cpf) {

      if (!cpf) {
         return '';
      }

      return cpf.substring(0, 3) + '.' + cpf.substring(3, 6) + '.' +
          cpf.substring(6, 9) + '-' + cpf.substring(9);
   }

   static formatCnpj(cnpj) {

      if (!cnpj) {
         return '';
      }

      return cnpj.substring(0, 2) + '.' + cnpj.substring(2, 5) + '.' +
          cnpj.substring(5, 8) + '/' + cnpj.substring(8, 12)  + '-' +
          cnpj.substring(12);
   }

   static generateVerificationCode() {

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
      let codeSize = 16;

      // Generate the entropy with Node.js Crypto's cryptographically strong
      // pseudo-random generation function.
      let numBytes = Math.floor(codeSize / 2);
      let randBuffer = crypto.randomBytes(numBytes);

      return randBuffer.toString('hex').toUpperCase();
   }

   static formatVerificationCode(code) {
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
      let codeGroups = 4;

      // Return the code separated in groups
      let charsPerGroup = (code.length - code.length % codeGroups) / codeGroups;
      let text = '';
      for (let i = 0; i < code.length; i++) {
         if (i !== 0 && i % charsPerGroup === 0) {
            text += '-';
         }
         text += code[i];
      }

      return text;
   }

   static parseVerificationCode(code) {
      let text = '';
      for (let i = 0; i < code.length; i++) {
         if (code[i] !== '-') {
            text += code[i];
         }
      }

      return text;
   }
}

exports.Util = Util;