using Lacuna.RestPki.Api;
using Lacuna.RestPki.Api.PadesSignature;
using Lacuna.RestPki.Client;
using System;
using System.Diagnostics;
using System.IO;
using System.Windows;
using System.Collections.Generic;
using System.Linq;
using Lacuna.Pki.Stores;
using Lacuna.Pki;
using System.Threading.Tasks;

namespace Signer {
	/// <summary>
	/// Interaction logic for MainWindow.xaml
	/// </summary>
	public partial class MainWindow {
		public string FileToSign { get; private set; }
		public string SignedFile { get; private set; }
		public ClientSideSignatureInstructions Token { get; set; }
		private RestPkiClient restPkiClient;

		public MainWindow() {
			InitializeComponent();
		}

		private async void Window_Loaded(object sender, RoutedEventArgs e) {
			try {
				restPkiClient = Util.GetRestPkiClient();
				PkiConfig.LoadLicense(Util.GetPkiSdkLicense());
			} catch (Exception ex) {
				MessageBox.Show($"{ex.Message}\r\n\r\nSigner will close.", "Configuration Error");
				addLog($"RestPKI token not found");
				Application.Current.Shutdown();
			}
			addLog($"RestPKI token found");

			await listCertificatesWithKey();
			checkBoxSafeSign.IsChecked = false;
			checkBoxSafeNet.IsChecked = false;
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
			//var progressDialog = await this.ShowProgressAsync("Please wait...", "Signing");
			Pkcs11CertificateStore p11Store = null;
			addLog($"Signature started");
			progressBar.Value = 10;
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
					VisualRepresentation = createVisualRepresentation()
				};

				progressBar.Value = 15;

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

				// Find selected certificate with key
				// --------------------------------------------------------------
				PKCertificateWithKey certWithKey = null;
				var selectedThumbprint = (CertificatesCB.SelectedItem as ComboCertificate).Certificate.ThumbprintSHA256;

				p11Store = Pkcs11CertificateStore.Load(getPkcs11Modules(), new P11LoginProvider());
				// search on pkcs11 store
				if (findCertificate(p11Store.GetCertificatesWithKey(), selectedThumbprint, out certWithKey)) {
				} else if (findCertificate(WindowsCertificateStore.LoadPersonalCurrentUser().GetCertificatesWithKey(), selectedThumbprint, out certWithKey)) {
				} else {
					throw new Exception("Selected certificate not found");
				}
				// --------------------------------------------------------------
				signatureStarter.SetSignerCertificate(certWithKey.Certificate.EncodedValue);

				progressBar.Value = 30;
				addLog($"Step One Started");
				var sw = new Stopwatch();
				sw.Start();
				Token = await signatureStarter.StartAsync();
				sw.Stop();
				addLog($"Step One finished,elapsed {sw.Elapsed.TotalSeconds:N1}s");
				progressBar.Value = 50;

				var signature = certWithKey.SignData(Lacuna.Pki.DigestAlgorithm.GetInstanceByOid(Token.DigestAlgorithmOid), Token.ToSignData);
				var signatureFinisher = new PadesSignatureFinisher2(restPkiClient) {
					Token = Token.Token,
					Signature = signature
				};
				progressBar.Value = 70;
				// Call the Finish() method, which finalizes the signature process and returns a SignatureResult object
				sw.Reset();
				sw.Start();
				var signatureResult = await signatureFinisher.FinishAsync();
				sw.Stop();
				addLog($"Step two finished,elapsed {sw.Elapsed.TotalSeconds:N1}s");
				SignedFile = System.IO.Path.Combine(Path.GetDirectoryName(FileToSign), Path.GetFileNameWithoutExtension(FileToSign) + "Signed" + Path.GetExtension(FileToSign));
				signatureResult.WriteToFile(SignedFile);
				//BusyIndicator.IsBusy = false;
				progressBar.Value = 100;
				OpenFileSignedBt.IsEnabled = true;
				addLog($"Signarure finished");

			} catch (Exception ex) {
				addLog(ex.ToString());

			} finally {
				p11Store.Dispose();
			}
			progressBar.Value = 0;
		}

		private void CertificatesCB_SelectionChanged(object sender, System.Windows.Controls.SelectionChangedEventArgs e) {
			if (CertificatesCB.SelectedItem != null) {
				addLog($"Certificate {(CertificatesCB.SelectedItem as ComboCertificate).Certificate.SubjectDisplayName} selected");
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
			LogTB.ScrollToEnd();
		}

		private PadesVisualRepresentation createVisualRepresentation() {
			return new PadesVisualRepresentation() {

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
			};
		}

		#region PKI SDK usage

		private bool findCertificate(List<PKCertificateWithKey> certificates, byte[] thumbprint, out PKCertificateWithKey cert) {
			cert = certificates.FirstOrDefault(c => c.Certificate.ThumbprintSHA256.SequenceEqual(thumbprint));
			return cert != null;
		}

		private List<string> getPkcs11Modules() {
			var modules = new List<string>();
			if (checkBoxSafeNet.IsChecked.Value) {
				modules.Add("eTPKCS11.dll");
			}
			if (checkBoxSafeSign.IsChecked.Value) {
				modules.Add("aetpkss1.dll");
			}
			return modules;
		}

		private async Task listCertificatesWithKey() {
			var certificates = new List<PKCertificateWithKey>();
			var modules = getPkcs11Modules();
			await Task.Run(() => {
				// list PKCS#11 certificates if any module set
				if (modules.Any()) {
					using (var p11Store = Pkcs11CertificateStore.Load(modules, new P11LoginProvider(true))) {
						certificates.AddRange(p11Store.GetCertificatesWithKey());
					}
				}
				// get all windows certificates that are not yet listed
				certificates.AddRange(WindowsCertificateStore.LoadPersonalCurrentUser().GetCertificatesWithKey()
					.FindAll(winc => !certificates.Select(c => c.Certificate).Contains(winc.Certificate)));
			});

			// clear any previous list
			CertificatesCB.Items.Clear();
			// populate
			certificates.ForEach(c => CertificatesCB.Items.Add(new ComboCertificate(c.Certificate)));
			addLog($"Certificates listed");
			addLog($"{CertificatesCB.Items.Count} Certificates found");

			if (CertificatesCB.Items.Count > 0) {
				CertificatesCB.SelectedItem = CertificatesCB.Items[0];
			}
		}

		private class ComboCertificate {
			public Lacuna.Pki.PKCertificate Certificate { get; set; }
			public ComboCertificate(Lacuna.Pki.PKCertificate certificate) {
				Certificate = certificate;
			}
			public override string ToString() {
				return $"{Certificate.SubjectDisplayName} ({Certificate.IssuerDisplayName})";
            }
		}

		#endregion

		private async void button_Click(object sender, RoutedEventArgs e) {
			await listCertificatesWithKey();
		}
	}
}
