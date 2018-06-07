using Lacuna.RestPki.Api;
using Lacuna.RestPki.Api.PadesSignature;
using Lacuna.RestPki.Client;
using Lacuna.RestPki.SampleSite.Classes;
using Lacuna.RestPki.SampleSite.Models;
using SampleSite.Classes;
using System;
using System.Collections.Generic;
using System.IO;
using System.Linq;
using System.Threading.Tasks;
using System.Web;
using System.Web.Mvc;

namespace Lacuna.RestPki.SampleSite.Controllers {
    public class PadesSignatureWithoutDirectCommunicationController : Controller {

        // GET: PadesSignatureWithoutDirectCommunication
        [HttpGet]
        public ActionResult Index() {
            return View();
        }

        /**
         * POST: PadesSignatureWithoutDirectCommunication
         * 
         * This action receives the form submission from the signature page. It will initialize a PAdES
         * signature process using REST PKI.
         */
        [HttpPost]
        public async Task<ActionResult> Index(SignatureStartModel model) {

            ClientSideSignatureInstructions signatureParams;

            try {

                // Get an instance of the PadesSignatureStarter class, responsible for receiving the
                // signature elements and start the signature process.
                var signatureStarter = new PadesSignatureStarter(Util.GetRestPkiClient()) {

                    // Set the unit of measurement used to edit the pdf marks and visual representations.
                    MeasurementUnits = PadesMeasurementUnits.Centimeters,

                    // Set the signature policy.
                    SignaturePolicyId = StandardPadesSignaturePolicies.Basic,

                    // Set the security context to be used to determine trust in the certificate chain. We have
					// encapsulated the security context choice on Util.cs.
                    SecurityContextId = Util.GetSecurityContextId(),

                    // Set a visual representation for the signature.
                    VisualRepresentation = PadesVisualElements.GetVisualRepresentation()
				};

				// Set certificate's content. (received from a hidden field on the form submission, its value
				// is filled on javascript, see signature-start-form.js)
				signatureStarter.SetSignerCertificate(model.CertContent);

                // Set PDF to be signed.
                signatureStarter.SetPdfToSign(Util.GetSampleDocPath());

                /*
				    Optionally, add marks to the PDF before signing. These differ from the signature visual
                    representation in that they are actually changes done to the document prior to signing,
                    not binded to any signature. Therefore, any number of marks can be added, for instance
                    one per page, whereas there can only be one visual representation per signature. However,
                    since the marks are in reality changes to the PDF, they can only be added to documents
                    which have no previous signatures, otherwise such signatures would be made invalid by the
                    changes to the document (see property PadesSignatureStarter.BypassMarksIfSigned). This
                    problem does not occurr with signature visual representations.
			
				    We have encapsulated this code in a method to include several possibilities depending on
                    the argument passed. Experiment changing the argument to see different examples of PDF
                    marks. Once you decide which is best for your case, you can place the code directly here.
			     */
                //signatureStarter.PdfMarks.Add(PadesVisualElements.GetPdfMark(1));

                // Call the Start() method, which initiates the signature. This yields the parameters
                // for the signature using the certificates.
                signatureParams = await signatureStarter.StartAsync();

            } catch (ValidationException ex) {

                // Return to Index view rendering the error message.
                ModelState.AddModelError("", ex.Message);
                return View();

            }

            // On the next step (Complete action), we'll need once again some information:
            // - The token that identifies the signature process on REST PKI service.
            // - The thumbprint of the selected certificate.
            // - The "to-sign-hash" to be signed. (see signature-complete-form.js)
            // - The OID of the digest algorithm to be used during the signature operation.
            // We'll store these values on TempData, which is a dictionary shared between actions.
            TempData["SignatureCompleteModel"] = new SignatureCompleteModel() {
                Token = signatureParams.Token,
                CertThumb = model.CertThumb,
                ToSignHash = signatureParams.ToSignHash,
                DigestAlgorithmOid = signatureParams.DigestAlgorithmOid
            };

            return RedirectToAction("Complete");
        }

        [HttpGet]
        public ActionResult Complete() {

            // Recovery data from Index action, if returns null, it'll be redirected to Index action again.
            var model = TempData["SignatureCompleteModel"] as SignatureCompleteModel;
            if (model == null) {
                return RedirectToAction("Index");
            }

            return View(model);
        }

        [HttpPost]
        public async Task<ActionResult> Complete(SignatureCompleteModel model) {

            string fileId;
            PKCertificate signerCert;

            try {

                // Get an instance of the PadesSignatureFinisher2 class, responsible for completing the
                // signature process.
                var signatureFinisher = new PadesSignatureFinisher2(Util.GetRestPkiClient()) {

                    // Set the token for this signature (rendered in a hidden input field, see the view).
                    Token = model.Token,

                    // Set the signature computed (rendered in a hidden input field, see the view).
                    Signature = model.Signature

                };

                // Call the Finish() method, which finalizes the signature process and returns a
                // SignatureResult object.
                var result = await signatureFinisher.FinishAsync();

                // The "Certificate" property of the SignatureResult object contains information about the
                // certificate used by the user to sign the file.
                signerCert = result.Certificate;

                // At this point, you'd typically store the signed PDF on your database. For demonstration
                // purposes, we'll store the PDF on our mock Storage class.

                // The SignatureResult object has various methods for writing the signature file to a stream
                // (WriteTo()), local file (WriteToFile()), open a stream to read the content (OpenRead())
                // and get its contents (GetContent()). For large files, avoid the method GetContent() to
                // avoid memory allocation issues.
                using (var resultStream = result.OpenRead()) {
                    fileId = StorageMock.Store(resultStream, ".pdf");
                }
                // If you prefer a simpler approach without stream, simply do:
                //fileId = StorageMock.Store(result.GetContent(), ".pdf");

            } catch (ValidationException ex) {

                // Return to Index view rendering the error message.
                ModelState.AddModelError("", ex.Message);
                return View();

            }

			// Render the signature information page.
			return View("SignatureInfo", new SignatureInfoModel() {
                File = fileId,
                SignerCertificate = signerCert
            });
        }
    }
}