using Lacuna.RestPki.Api;
using Lacuna.RestPki.Client;
using Lacuna.RestPki.Client.FluentApi;
using System;
using System.Collections.Generic;
using System.Drawing;
using System.Globalization;
using System.IO;
using System.Linq;
using System.Text;
using System.Web;
using System.Web.UI;
using System.Web.UI.WebControls;

namespace WebForms {

	public partial class PrinterFriendlyVersion : System.Web.UI.Page {

		// ##################################################################################################################
		// Configuration of the Printer-Friendly version
		// ##################################################################################################################

		// Name of your website, with preceding article (article in lowercase)
		private const string VerificationSiteNameWithArticle = "a Minha Central de Verificação";

		// Publicly accessible URL of your website. Preferably HTTPS.
		private const string VerificationSite = "http://localhost:52539/";

		// Format of the verification link, with "{0}" as the verification code placeholder
		private const string VerificationLinkFormat = "http://localhost:52539/Check?code={0}";

		// "Normal" font size. Sizes of header fonts are defined based on this size.
		private const int NormalFontSize = 12;

		// CultureInfo to be used when converting dates to string
		private static readonly CultureInfo CultureInfo = new CultureInfo("pt-BR");

		// Date format to be used when converting dates to string (for other formats, see https://docs.microsoft.com/en-us/dotnet/standard/base-types/standard-date-and-time-format-strings)
		private const string DateFormat = "g"; // short date with short time

		// Time zone to be used when converting dates to string (for other time zones, see https://docs.microsoft.com/en-us/windows-hardware/manufacture/desktop/default-time-zones)
		private static readonly TimeZoneInfo TimeZone = TimeZoneInfo.FindSystemTimeZoneById("E. South America Standard Time");

		// Display name of the time zone chosen above
		private const string TimeZoneDisplayName = "horário de Brasília";

		// You may also change texts, positions and more by editing directly the method generatePrinterFriendlyVersion below
		// ##################################################################################################################

		protected void Page_Load(object sender, EventArgs e) {

			// Get document ID from query string
			var fileId = Request.QueryString["file"];

			// Locate document and read content from storage
			var fileContent = Storage.Read(fileId);

			// Check if doc already has a verification code registered on storage
			var verificationCode = Storage.GetVerificationCode(fileId);
			if (verificationCode == null) {
				// If not, generate a code and register it
				verificationCode = Util.GenerateVerificationCode();
				Storage.SetVerificationCode(fileId, verificationCode);
			}

			// Generate the printer-friendly version
			var pfvContent = generatePrinterFriendlyVersion(fileContent, verificationCode);

			// Return printer-friendly version as a downloadable file
			Response.ContentType = "application/pdf";
			Response.AddHeader("Content-Disposition", "attachment; filename=printer-friendly.pdf");
			Response.BinaryWrite(pfvContent);
			Response.End();
		}

		private byte[] generatePrinterFriendlyVersion(byte[] pdfContent, string verificationCode) {

			var client = Util.GetRestPkiClient();
			var verificationLink = string.Format(VerificationLinkFormat, verificationCode);

			// 1. Upload the PDF
			var blob = client.UploadFile(pdfContent);

			// 2. Inspect signatures on the uploaded PDF
			var sigExplorer = new PadesSignatureExplorer(client) {
				Validate = true,
				DefaultSignaturePolicyId = StandardPadesSignaturePolicies.Basic,
				SecurityContextId = StandardSecurityContexts.PkiBrazil,
			};
			sigExplorer.SetSignatureFile(blob);
			var signature = sigExplorer.Open();

			// 3. Create PDF with verification information from uploaded PDF

			var pdfMarker = new PdfMarker(client);
			pdfMarker.SetFile(blob);

			// Build string with joined names of signers (see method getDisplayName below)
			var signerNames = Util.JoinStringsPt(signature.Signers.Select(s => getDisplayName(s.Certificate)));
			var allPagesMessage = $"Este documento foi assinado digitalmente por {signerNames}.\nPara verificar a validade das assinaturas acesse {VerificationSiteNameWithArticle} em {VerificationSite} e informe o código {verificationCode}";

			// PdfHelper is a class from the Rest PKI Client "fluent API" that helps to create elements and parameters for the PdfMarker
			var pdf = new PdfHelper();

			// ICP-Brasil logo on bottom-right corner of every page (except on the page which will be created at the end of the document)
			pdfMarker.Marks.Add(
				pdf.Mark()
				.OnAllPages()
				.OnContainer(pdf.Container().Width(1).AnchorRight(1).Height(1).AnchorBottom(1))
				.AddElement(
					pdf.ImageElement()
					.WithOpacity(75)
					.WithImage(Util.GetIcpBrasilLogoContent(), "image/png")
				)
			);

			// Summary on bottom margin of every page (except on the page which will be created at the end of the document)
			pdfMarker.Marks.Add(
				pdf.Mark()
				.OnAllPages()
				.OnContainer(pdf.Container().Height(2).AnchorBottom().VarWidth().Margins(1.5, 3.5))
				.AddElement(
					pdf.TextElement()
					.WithOpacity(75)
					.AddSection(allPagesMessage)
				)
			);

			// Summary on right margin of every page (except on the page which will be created at the end of the document),
			// rotated 90 degrees counterclockwise (text goes up)
			pdfMarker.Marks.Add(
				pdf.Mark()
				.OnAllPages()
				.OnContainer(pdf.Container().Width(2).AnchorRight().VarHeight().Margins(1.5, 3.5))
				.AddElement(
					pdf.TextElement()
					.Rotate90Counterclockwise()
					.WithOpacity(75)
					.AddSection(allPagesMessage)
				)
			);

			// Create a "manifest" mark on a new page added on the end of the document. We'll add several elements to this mark.
			var manifestMark = pdf.Mark()
				.OnNewPage()
				// This mark's container is the whole page with 1-inch margins
				.OnContainer(pdf.Container().VarWidthAndHeight().Margins(2.54, 2.54));

			// We'll keep track of our "vertical offset" as we add elements to the mark
			double verticalOffset = 0;
			double elementHeight;

			elementHeight = 3;
			manifestMark
			// ICP-Brasil logo on the upper-left corner
			.AddElement(
				pdf.ImageElement()
				.OnContainer(pdf.Container().Height(elementHeight).AnchorTop(verticalOffset).Width(elementHeight /* using elementHeight as width because the image is square */).AnchorLeft())
				.WithImage(Util.GetIcpBrasilLogoContent(), "image/png")
			)
			// QR Code with the verification link on the upper-right corner
			.AddElement(
				pdf.QRCodeElement()
				.OnContainer(pdf.Container().Height(elementHeight).AnchorTop(verticalOffset).Width(elementHeight /* using elementHeight as width because QR Codes are square */).AnchorRight())
				.WithQRCodeData(verificationLink)
			)
			// Header "VERIFICAÇÃO DAS ASSINATURAS" centered between ICP-Brasil logo and QR Code
			.AddElement(
				pdf.TextElement()
				.OnContainer(pdf.Container().Height(elementHeight).AnchorTop(verticalOffset + 0.2).FullWidth())
				.AlignTextCenter()
				.AddSection(pdf.TextSection().WithFontSize(NormalFontSize * 1.6).WithText("VERIFICAÇÃO DAS\nASSINATURAS"))
			);
			verticalOffset += elementHeight;

			// Vertical padding
			verticalOffset += 1.7;

			// Header with verification code
			elementHeight = 2;
			manifestMark.AddElement(
				pdf.TextElement()
				.OnContainer(pdf.Container().Height(elementHeight).AnchorTop(verticalOffset).FullWidth())
				.AlignTextCenter()
				.AddSection(pdf.TextSection().WithFontSize(NormalFontSize * 1.2).WithText($"Código para verificação: {verificationCode}"))
			);
			verticalOffset += elementHeight;

			// Paragraph saying "this document was signed by the following signers etc" and mentioning the time zone of the date/times below
			elementHeight = 2.5;
			manifestMark.AddElement(
				pdf.TextElement()
				.OnContainer(pdf.Container().Height(elementHeight).AnchorTop(verticalOffset).FullWidth())
				.AddSection(pdf.TextSection().WithFontSize(NormalFontSize).WithText($"Este documento foi assinado digitalmente pelos seguintes signatários nas datas indicadas ({TimeZoneDisplayName}):"))
			);
			verticalOffset += elementHeight;

			// Iterate signers
			foreach (var signer in signature.Signers) {

				elementHeight = 1.5;

				manifestMark
				// Green "check" or red "X" icon depending on result of validation for this signer
				.AddElement(
					pdf.ImageElement()
					.OnContainer(pdf.Container().Height(0.5).AnchorTop(verticalOffset + 0.2).Width(0.5).AnchorLeft())
					.WithImage(Util.GetValidationResultIcon(signer.ValidationResults.IsValid), "image/png")
				)
				// Description of signer (see method getSignerDescription below)
				.AddElement(
					pdf.TextElement()
					.OnContainer(pdf.Container().Height(elementHeight).AnchorTop(verticalOffset).VarWidth().Margins(0.8, 0))
					.AddSection(pdf.TextSection().WithFontSize(NormalFontSize).WithText(getSignerDescription(signer)))
				);

				verticalOffset += elementHeight;
			}

			// Some vertical padding from last signer
			verticalOffset += 1;

			// Paragraph with link to verification site and citing both the verification code above and the verification link below
			elementHeight = 2.5;
			manifestMark.AddElement(
				pdf.TextElement()
				.OnContainer(pdf.Container().Height(elementHeight).AnchorTop(verticalOffset).FullWidth())
				.AddSection(pdf.TextSection().WithFontSize(NormalFontSize).WithText($"Para verificar a validade das assinaturas, acesse {VerificationSiteNameWithArticle} em "))
				.AddSection(pdf.TextSection().WithFontSize(NormalFontSize).WithColor(Color.Blue).WithText(VerificationSite))
				.AddSection(pdf.TextSection().WithFontSize(NormalFontSize).WithText(" e informe o código acima ou acesse o link abaixo:"))
			);
			verticalOffset += elementHeight;

			// Verification link
			elementHeight = 1.5;
			manifestMark.AddElement(
				pdf.TextElement()
				.OnContainer(pdf.Container().Height(elementHeight).AnchorTop(verticalOffset).FullWidth())
				.AddSection(pdf.TextSection().WithFontSize(NormalFontSize).WithColor(Color.Blue).WithText(verificationLink))
				.AlignTextCenter()
			);

			// Apply marks
			pdfMarker.Marks.Add(manifestMark);
			var result = pdfMarker.Apply();

			// Return result
			return result.GetContent();
		}

		private static string getDisplayName(PKCertificate c) {
			if (!string.IsNullOrEmpty(c.PkiBrazil.Responsavel)) {
				return c.PkiBrazil.Responsavel;
			}
			return c.SubjectName.CommonName;
		}

		private static string getDescription(PKCertificate c) {
			var text = new StringBuilder();
			text.Append(getDisplayName(c));
			if (!string.IsNullOrEmpty(c.PkiBrazil.Cpf)) {
				text.Append($" (CPF {c.PkiBrazil.CpfFormatted})");
			}
			if (!string.IsNullOrEmpty(c.PkiBrazil.Cnpj)) {
				text.Append($", empresa {c.PkiBrazil.CompanyName} (CNPJ {c.PkiBrazil.CnpjFormatted})");
			}
			return text.ToString();
		}

		private static string getSignerDescription(PadesSignerInfo signer) {
			var text = new StringBuilder();
			text.Append(getDescription(signer.Certificate));
			if (signer.SigningTime != null) {
				var dateStr = TimeZoneInfo.ConvertTime(signer.SigningTime.Value, TimeZone).ToString(DateFormat, CultureInfo);
				text.Append($" em {dateStr}");
			}
			return text.ToString();
		}
	}
}
