using Lacuna.RestPki.Api;
using Lacuna.RestPki.Api.PadesSignature;
using Lacuna.RestPki.Client;
using System;
using System.Configuration;
using System.Diagnostics;
using System.IO;
using System.Security.Cryptography;
using System.Security.Cryptography.X509Certificates;
using System.Windows;
using MahApps.Metro.Controls;
using MahApps.Metro.Controls.Dialogs;

namespace Signer {
	/// <summary>
	/// Interaction logic for MainWindow.xaml
	/// </summary>
	public partial class MainWindow {
		public string FileToSign { get; private set; }
		public string SignedFile { get; private set; }
		public ClientSideSignatureInstructions Token { get; set; }
		private bool init = false;
		private RestPkiClient restPkiClient;

		public MainWindow() {
			InitializeComponent();
			X509Store store = new X509Store("My");
			store.Open(OpenFlags.ReadOnly);
			foreach (X509Certificate2 mCert in store.Certificates) {
				if (mCert.HasPrivateKey) {
					var name = "";
					if (String.IsNullOrWhiteSpace(mCert.FriendlyName)) {
						name = mCert.Subject;
					} else {
						name = mCert.FriendlyName;
					}
					if (name.Contains(",")) {
						name = name.Substring(0, name.IndexOf(',') - 1);
						name = name.Replace("CN=", "");
					}
					CertificatesCB.Items.Add(new Cert() { Name = name, Thumbprint = mCert.Thumbprint });
				}
			}
			addLog($"{CertificatesCB.Items.Count} Certificates found");
		}

		protected override async void OnActivated(EventArgs e) {
			base.OnActivated(e);
			if (!init) {
				try {
					restPkiClient = Util.GetRestPkiClient();
				} catch (Exception ex) {
					await this.ShowMessageAsync("Configuration Error", $"{ex.Message}\r\n\r\nSigner will close.", MessageDialogStyle.Affirmative);
					addLog($"RestPKI token not found");
					Application.Current.Shutdown();
				}
				addLog($"RestPKI token found");
				init = true;
			}
		}

		private byte[] getCertificateBytes(string thumbprint) {
			X509Store store = new X509Store("My");

			store.Open(OpenFlags.ReadOnly);

			foreach (X509Certificate2 mCert in store.Certificates) {
				if (mCert.Thumbprint == thumbprint) {
					return mCert.GetRawCertData();
				}

			}
			return null;
		}
		private X509Certificate2 getCertificate(string thumbprint) {
			var store = new X509Store("My");

			store.Open(OpenFlags.ReadOnly);

			foreach (X509Certificate2 mCert in store.Certificates) {
				if (mCert.Thumbprint == thumbprint) {
					return mCert;
				}
			}
			return null;
		}

		private void fileButtonClick(object sender, RoutedEventArgs e) {
			Microsoft.Win32.OpenFileDialog dlg = new Microsoft.Win32.OpenFileDialog();
			dlg.DefaultExt = ".pdf";
			dlg.Filter = "";
			var result = dlg.ShowDialog();
			if (result == true) {
				FileToSign = dlg.FileName;
				FileNameL.Content = dlg.FileName;
				if (CertificatesCB.SelectedItem != null) {
					SignBt.IsEnabled = true;
				}
			}
		}

		private async void signButtonClick(object sender, RoutedEventArgs e) {
			var progressDialog = await this.ShowProgressAsync("Please wait...", "Signing");
			addLog($"Signature started");
			progressDialog.SetProgress(0.10);
			try {
				var signatureStarter = new PadesSignatureStarter(restPkiClient) {
					// Set the unit of measurement used to edit the pdf marks and visual representations
					MeasurementUnits = PadesMeasurementUnits.Centimeters,
					// Set the signature policy
					SignaturePolicyId = StandardPadesSignaturePolicies.Basic,
					// Set a SecurityContext to be used to determine trust in the certificate chain
					SecurityContextId = Guid.Parse("803517ad-3bbc-4169-b085-60053a8f6dbf"),
					//SecurityContextId =StandardSecurityContexts.PkiBrazil,
					// Note: this SecurityContext above accept unsecured certificates. You can create custom security context on the Rest PKI website.

					// Set a visual representation for the signature
					VisualRepresentation = new PadesVisualRepresentation() {

						// The tags {{name}} and {{national_id}} will be substituted according to the user's certificate
						//
						//		name        : full name of the signer
						//		national_id : if the certificate is ICP-Brasil, contains the signer's CPF
						//
						// For a full list of the supported tags, see: https://github.com/LacunaSoftware/RestPkiSamples/blob/master/PadesTags.md
						Text = new PadesVisualText("Signed by {{name}} ({{national_id}})") {

							// Specify that the signing time should also be rendered
							IncludeSigningTime = true,

							// Optionally set the horizontal alignment of the text ('Left' or 'Right'), if not set the default is Left
							HorizontalAlign = PadesTextHorizontalAlign.Left

						},

						// We'll use as background the image in Content/PdfStamp.png
						Image = new PadesVisualImage(Signer.Resources.PdfStamp_png, "image/png") {

							// Opacity is an integer from 0 to 100 (0 is completely transparent, 100 is completely opaque).
							Opacity = 50,

							// Align the image to the right
							HorizontalAlign = PadesHorizontalAlign.Right

						},

						// Position of the visual representation. We have encapsulated this code in a method to include several
						// possibilities depending on the argument passed. Experiment changing the argument to see different examples
						// of signature positioning. Once you decide which is best for your case, you can place the code directly here.
						Position = PadesVisualElements.GetVisualPositioning(restPkiClient, 1)
					},
				};

				var cert = CertificatesCB.SelectedItem as Cert;
				var certBytes = getCertificateBytes(cert.Thumbprint);
				var certificate = getCertificate(cert.Thumbprint);
				signatureStarter.SetSignerCertificate(certBytes);
				progressDialog.SetProgress(0.15);

				// If the user was redirected here by UploadController (signature with file uploaded by user), the "userfile" URL argument
				// will contain the filename under the "App_Data" folder. Otherwise (signature with server file), we'll sign a sample
				// document.
				if (string.IsNullOrEmpty(FileToSign)) {
					// Set the PDF to be signed as a byte array
					signatureStarter.SetPdfToSign(Signer.Resources.SampleDocument);
				} else {
					// Set the path of the file to be signed
					addLog($"file size {new FileInfo(FileToSign).Length / 1024}kBytes");
					signatureStarter.SetPdfToSign(FileToSign);
				}

				/*
					Optionally, add marks to the PDF before signing. These differ from the signature visual representation in that
					they are actually changes done to the document prior to signing, not binded to any signature. Therefore, any number
					of marks can be added, for instance one per page, whereas there can only be one visual representation per signature.
					However, since the marks are in reality changes to the PDF, they can only be added to documents which have no previous
					signatures, otherwise such signatures would be made invalid by the changes to the document (see property
					PadesSignatureStarter.BypassMarksIfSigned). This problem does not occurr with signature visual representations.

					We have encapsulated this code in a method to include several possibilities depending on the argument passed.
					Experiment changing the argument to see different examples of PDF marks. Once you decide which is best for your case,
					you can place the code directly here.
				*/
				signatureStarter.PdfMarks.Add(PadesVisualElements.GetPdfMark(1));

				// Call the StartWithWebPki() method, which initiates the signature. This yields the token, a 43-character
				// case-sensitive URL-safe string, which identifies this signature process. We'll use this value to call the
				// signWithRestPki() method on the Web PKI component (see javascript on the view) and also to complete the signature
				// on the POST action below (this should not be mistaken with the API access token).

				progressDialog.SetProgress(0.20);
				addLog($"Step One Started");
				var sw = new Stopwatch();
				sw.Start();
				Token = await signatureStarter.StartAsync();
				sw.Stop();
				addLog($"Step One finished,elapsed {sw.Elapsed.TotalSeconds:N1}s");
				progressDialog.SetProgress(0.40);
				byte[] signature;
				using (RSA rsa = certificate.GetRSAPrivateKey()) {
					HashAlgorithmName hashAlgorithm;
					switch (Token.DigestAlgorithm.ApiModel) {
						case DigestAlgorithms.MD5:
							hashAlgorithm = HashAlgorithmName.MD5;
							break;
						case DigestAlgorithms.SHA1:
							hashAlgorithm = HashAlgorithmName.SHA1;
							break;
						case DigestAlgorithms.SHA256:
							hashAlgorithm = HashAlgorithmName.SHA256;
							break;
						case DigestAlgorithms.SHA384:
							hashAlgorithm = HashAlgorithmName.SHA384;
							break;
						case DigestAlgorithms.SHA512:
							hashAlgorithm = HashAlgorithmName.SHA512;
							break;
						default:
							throw new ArgumentOutOfRangeException();
					}
					signature = rsa.SignData(Token.ToSignData, hashAlgorithm, RSASignaturePadding.Pkcs1);

					var signatureFinisher = new PadesSignatureFinisher2(restPkiClient) {
						Token = Token.Token,
						Signature = signature
					};
					progressDialog.SetProgress(0.50);

					// Call the Finish() method, which finalizes the signature process and returns a SignatureResult object
					sw.Reset();
					sw.Start();
					var signatureResult = await signatureFinisher.FinishAsync();
					sw.Stop();
					addLog($"Step two finished,elapsed {sw.Elapsed.TotalSeconds:N1}s");


					SignedFile = System.IO.Path.Combine(Path.GetDirectoryName(FileToSign),
																	Path.GetFileNameWithoutExtension(FileToSign) + "Signed" + Path.GetExtension(FileToSign));
					signatureResult.WriteToFile(SignedFile);
					//BusyIndicator.IsBusy = false;
					progressDialog.SetProgress(0.99);
					OpenFileSignedBt.IsEnabled = true;
					addLog($"Signarure finished");
				}
			} catch (Exception ex) {
				addLog(ex.ToString());
			}
			await progressDialog.CloseAsync();
		}

		private void CertificatesCB_SelectionChanged(object sender, System.Windows.Controls.SelectionChangedEventArgs e) {
			if (CertificatesCB.SelectedItem != null) {
				addLog($"Certificate {(CertificatesCB.SelectedItem as Cert).Name} selected");
				if (!string.IsNullOrWhiteSpace(FileToSign)) {
					SignBt.IsEnabled = true;
				}
			}
		}

		private void OpenFileLocationBt_Click(object sender, RoutedEventArgs e) {
			Process.Start(System.IO.Path.GetDirectoryName(SignedFile));
		}

		private void OpenFileSignedBt_Click(object sender, RoutedEventArgs e) {
			try {
				System.Diagnostics.Process process = new System.Diagnostics.Process();
				process.StartInfo.FileName = SignedFile;
				process.Start();
				process.WaitForExit();
			} catch {
				MessageBox.Show("Could not open the file.", "Error", MessageBoxButton.OK, MessageBoxImage.Warning);
			}
		}

		private void addLog(string log) {
			LogTB.AppendText($"{DateTime.Now:T}: {log}");
			LogTB.AppendText(Environment.NewLine);
		}
	}

	public class Cert {
		public string Name { get; set; }
		public string Thumbprint { get; set; }
		public override string ToString() => Name;
	}
}
