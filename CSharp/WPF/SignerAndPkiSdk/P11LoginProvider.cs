using Lacuna.Pki.Pkcs11;
using Net.Pkcs11Interop.Common;
using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using System.Threading;
using System.Threading.Tasks;

namespace Signer {

	public class P11LoginProvider : IPkcs11LoginProvider {

		public bool CachePin{ get; set; }
		private bool listingCertificates;
		private Dictionary<string, string> memoryPinCache;

		public P11LoginProvider(bool listingCertificates = false) {
			this.listingCertificates = listingCertificates;
			memoryPinCache = new Dictionary<string, string>();
		}

		public void Login(Func<Pkcs11TokenInfo> updateTokenInfo, Action<string> executeLogin) {
			CKR rv = CKR.CKR_OK;
			var tokenInfo = updateTokenInfo();

			// if caching PIN and cached
			if (CachePin && memoryPinCache.ContainsKey(tokenInfo.SerialNumber)) {
				if (Pkcs11LoginHandler.HandleLoginExecution(() => executeLogin(memoryPinCache[tokenInfo.SerialNumber]), out rv)) {
					// success
					return;
				}
			}

			while (true) {
				string pin = null;
				runInStaThread(() => {
					var dialog = new PinDialog(tokenInfo, listingCertificates);
					if (dialog.ShowDialogThrowingExceptions() ?? false) {
						if (dialog.DialogResult == null || !dialog.DialogResult.Value) {
							// user cancelled
							return;
						}
						// user gave pin
						pin = dialog.Pin;
					}
				});

				if (pin == null) {
					throw new Exception("User cancelled");
				}

				if (!string.IsNullOrEmpty(pin)) {
					if (Pkcs11LoginHandler.HandleLoginExecution(() => executeLogin(pin), out rv)) {
						// success login
						if (CachePin) {
							memoryPinCache[tokenInfo.SerialNumber] = pin;
						}
						return;
					}
				}

				// not success, update token info and loop
				tokenInfo = updateTokenInfo();
			}
		}

		private static void runInStaThread(Action action) {
			var thread = new Thread(new ThreadStart(action));
			thread.SetApartmentState(System.Threading.ApartmentState.STA);
			thread.Start();
			thread.Join();
		}

	}
}
