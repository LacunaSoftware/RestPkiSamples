module.exports = {

   // Returns the verification code associated with the given document, or null
   // if no verification code has been associated with it.
   getVerificationCode: function(session, fileId) {

      // >>>>> NOTICE <<<<<
      // This should be implemented on your application as a SELECT on your
      // "document table" by the ID of the document, returning the value of the
      // verification code column.
      if (session['Files/' + fileId + '/Code']) {
         return session['Files/' + fileId + '/Code'];
      }
      return null;
   },

   // Registers the verification code for a given document.
   setVerificationCode: function(session, fileId, code) {

      // >>>>> NOTICE <<<<<
      // This should be implemented on your application as a UPDATE on your
      // "document table" filling the verification code column, which should be
      // an indexed column.
      session['Files/' + fileId + '/Code'] = code;
      session['Codes/' + code] = fileId;
   },

   // Returns the ID of the document associated with a given verification code,
   // or null if no document matches the given code.
   lookupVerificationCode: function(session, code) {

      if (!code) {
         return null;
      }

      // >>>>> NOTICE <<<<<
      // This should be implemented on your application as a SELECT on your
      // "document table" by the verification code column, which should be an
      // indexed column.
      if (session['Codes/' + code]) {
         return session['Codes/' + code];
      }
      return null;

   }
};