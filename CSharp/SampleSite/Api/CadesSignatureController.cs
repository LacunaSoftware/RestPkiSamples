using Lacuna.Pki;
using Lacuna.Pki.Cades;
using Lacuna.Pki.Stores;
using SampleSite.Classes;
using SampleSite.Models;
using System;
using System.Collections.Generic;
using System.Linq;
using System.Net;
using System.Net.Http;
using System.Threading.Tasks;
using System.Web.Http;
using System.Web.Http.Description;

namespace SampleSite.Api {

	/**
	 * This controller contains the server-side logic for the CAdES signature example. The client-side is implemented at:
	 * - HTML: Views/Home/CadesSignature.cshtml
	 * - JS: Content/js/app/cades-signature.js
	 * 
	 * This controller implements the logic described at
	 * http://pki.lacunasoftware.com/Help/html/c5494b89-d573-4a35-a911-721e32b08dd9.htm
	 * 
	 * Note on encodings: the models used in this controller declare byte arrays, but on the
	 * javascript the values are Base64-encoded. ASP.NET's Web API framework is taking care of
	 * the conversions for us. However, if another technology is to be used, such as MVC, the
	 * conversion might have to be done manually on the server-side.
	 */
	public class CadesSignatureController : ApiController {

		// Helper method that returns the signature policy to be used
		private static CadesPolicySpec getPolicy() {
			/**
			 * By changing the argument below, you can accept signatures only signed with certificates
			 * from a certain PKI, for instance, ICP-Brasil (TrustArbitrators.PkiBrazil). For more information, see
			 * http://pki.lacunasoftware.com/Help/html/e7724d78-9835-4f06-b58c-939b721f6e7b.htm
			 * 
			 * The value below (TrustArbitrators.Windows) specifies that the root certification authorities in the
			 * Windows certificate store are to be used as trust arbitrators.
			 */
			return CadesPolicySpec.GetCadesBes(TrustArbitrators.Windows);

			// In order to accept certificates from any root CA's (DEBUGGING PURPOSES ONLY), use this instead:
			//return CadesPolicySpec.GetCadesBes(new AcceptAllTrustArbitrator());

			/*
			 * Policies control how signatures are performed and validated. You can specify other signature
			 * policies, such as one of the ICP-Brasil policies. For more information, see
			 * http://pki.lacunasoftware.com/Help/html/e9c1693f-91e4-41a8-8dd1-666c221e427b.htm
			 * http://pki.lacunasoftware.com/Help/html/351f08cd-07ab-43ae-86a8-2e61be7f2a7f.htm
			 */
		}

		/**
		 * POST Api/CadesSignature/Start
		 * 
		 * This action is called once the user's certificate encoding has been read, and contains the
		 * logic to prepare the byte array that needs to be actually signed with the user's private key
		 * (the "to-sign-bytes").
		 */
		[Route("api/CadesSignature/Start")]
		[HttpPost]
		[ResponseType(typeof(SignatureStartResponse))]
		public IHttpActionResult Start(SignatureStartRequest model) {

			byte[] toSign;
			SignatureAlgorithm signatureAlg;

			try {

				// Instantiate a CadesSigner class
				var cadesSigner = new CadesSigner();

				// Set the data to sign, which in the case of this example is a fixed sample document
				cadesSigner.SetDataToSign(Util.GetSampleDocContent());

				// Decode the user's certificate and set as the signer certificate
				cadesSigner.SetSigningCertificate(PKCertificate.Decode(model.Certificate));

				// Set the signature policy
				cadesSigner.SetPolicy(getPolicy());

				// Generate the "to-sign-bytes". This method also yields the signature algorithm that must
				// be used on the client-side, based on the signature policy.
				toSign = cadesSigner.GenerateToSignBytes(out signatureAlg);

			} catch (ValidationException ex) {

				// Some of the operations above may throw a ValidationException, for instance if the certificate
				// encoding cannot be read or if the certificate is expired.
				return Ok(new SignatureStartResponse() {
					Success = false,
					Message = "A validation error has occurred",
					ValidationResults = ex.ValidationResults.ToString()
				});

			}

			// On the next step (Complete action), we'll need once again the signer's certificate and the
			// "to-sign-bytes" (besides from the document to be signed and the policy). We'll store these
			// values on the database and return to the page an identifier that will be later used to locate
			// these values again.
			SignatureProcess signatureProcess;
			using (var dbContext = new DbContext()) {
				signatureProcess = SignatureProcess.Create();
				signatureProcess.CadesSignerCertificate = model.Certificate;
				signatureProcess.CadesToSign = toSign;
				dbContext.SignatureProcesses.Add(signatureProcess);
				dbContext.SaveChanges();
			}

			// Send back to the page:
			// - The identifier that we'll later use to locate the user's certificate and "to-sign-bytes"
			// - The "to-sign-bytes"
			// - The OID of the digest algorithm to be used during the signature operation
			var response = new SignatureStartResponse() {
				Success = true,
				ProcessId = signatureProcess.Id,
				ToSign = toSign,
				DigestAlgorithmOid = signatureAlg.DigestAlgorithm.Oid
			};
			return Ok(response);
		}

		/**
		 * POST Api/CadesSignature/Complete
		 * 
		 * This action is called once the "to-sign-bytes" are signed using the user's certificate. The
		 * page sends back the SignatureProcess ID and the signature operation result.
		 */
		[Route("api/CadesSignature/Complete")]
		[HttpPost]
		[ResponseType(typeof(SignatureCompleteResponse))]
		public IHttpActionResult Complete(SignatureCompleteRequest model) {

			// We'll use the SignatureProcess ID to locate the values we stored during the signature first step
			SignatureProcess signatureProcess;
			using (var dbContext = new DbContext()) {
				signatureProcess = dbContext.SignatureProcesses.FirstOrDefault(p => p.Id == model.ProcessId);
				// We won't be needing this information again, so let's do some housekeeping
				if (signatureProcess != null) {
					dbContext.SignatureProcesses.Remove(signatureProcess);
					dbContext.SaveChanges();
				}
			}

			// If we haven't found the SignatureProcess, something went wrong and we cannot continue (this shouldn't normally happen)
			if (signatureProcess == null) {
				return NotFound();
			}

			byte[] signatureContent;
			try {

				var cadesSigner = new CadesSigner();
				
				// Set the document to be signed and the policy, exactly like in the Start method
				cadesSigner.SetDataToSign(Util.GetSampleDocContent());
				cadesSigner.SetPolicy(getPolicy());
				
				// Set signer's certificate recovered from the database
				cadesSigner.SetSigningCertificate(PKCertificate.Decode(signatureProcess.CadesSignerCertificate));
				
				// Set the signature computed on the client-side, along with the "to-sign-bytes" recovered from the database
				cadesSigner.SetPrecomputedSignature(model.Signature, signatureProcess.CadesToSign);

				// Call ComputeSignature(), which does all the work, including validation of the signer's certificate and of the resulting signature
				cadesSigner.ComputeSignature();

				// Get the signature as an array of bytes
				signatureContent = cadesSigner.GetSignature();

			} catch (ValidationException ex) {
				// Some of the operations above may throw a ValidationException, for instance if the certificate is revoked.
				return Ok(new SignatureCompleteResponse() {
					Success = false,
					Message = "A validation error has occurred",
					ValidationResults = ex.ValidationResults.ToString()
				});
			}

			// Store the signature for future download (see method SignatureController.Download in the Controllers folder)
			Signature signature;
			using (var dbContext = new DbContext()) {
				signature = Signature.Create();
				signature.Type = SignatureTypes.Cades;
				signature.Content = signatureContent;
				dbContext.Signatures.Add(signature);
				dbContext.SaveChanges();
			}

			// Inform the page of the success, along with the ID of the stored signature, so that the page
			// can render the download link
			return Ok(new SignatureCompleteResponse() {
				Success = true,
				SignatureId = signature.Id
			});
		}

	}
}
