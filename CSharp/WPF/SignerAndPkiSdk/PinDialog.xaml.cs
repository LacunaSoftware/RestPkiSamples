using Lacuna.Pki.Pkcs11;
using Net.Pkcs11Interop.Common;
using System;
using System.Collections.Generic;
using System.Threading;
using System.Windows;


namespace Signer {
	/// <summary>
	/// Interaction logic for PinDialog.xaml
	/// </summary>
	public partial class PinDialog : Window {

		public string Pin { get; set; }

		private Pkcs11TokenInfo tokenInfo;
		private bool listingCertificates;

		public PinDialog(Pkcs11TokenInfo tokenInfo, bool listingCertificates = false) {
			this.tokenInfo = tokenInfo;
			this.listingCertificates = listingCertificates;
			Dispatcher.UnhandledException += Dispatcher_UnhandledException;
			InitializeComponent();
		}

		private void Window_Loaded(object sender, RoutedEventArgs e) {
			activiateWindow();

			var format = listingCertificates ? "Listing {0}" : "Signing with {0}";
			LToken.Content = string.Format(format, tokenInfo.Label);
			if (tokenInfo.UserPinLocked) {
				StatusLabel.Content = "Token blocked";
				return;
			}
			if (tokenInfo.UserPinFinalTry) {
				StatusLabel.Content = "Wrong PIN. This is your last attempt. Be wise";
				return;
			}
			if (tokenInfo.UserPinCountLow) {
				StatusLabel.Content = "Wrong PIN";
				return;
			}
			StatusLabel.Content = string.Empty;
		}

		private void OKButton_Click(object sender, RoutedEventArgs e) {
			this.DialogResult = true;
			this.Pin = PinPasswordBox.Password;
			this.Close();
		}

		private void CancelButton_Click(object sender, RoutedEventArgs e) {
			this.DialogResult = false;
			this.Pin = null;
			this.Close();
		}

		private void activiateWindow() {
			Activate();
			Topmost = true;
			Topmost = false;
			Focus();
		}

		#region Exception handling

		private Exception caughtException;

		private void Dispatcher_UnhandledException(object sender, System.Windows.Threading.DispatcherUnhandledExceptionEventArgs e) {
			caughtException = e.Exception;
			e.Handled = true;
			this.Close();
		}

		public bool? ShowDialogThrowingExceptions() {
			var result = ShowDialog();
			if (caughtException != null) {
				throw new Exception("An exception occurred while getting user input", caughtException);
			}
			return result;
		}

		#endregion
	}
}
