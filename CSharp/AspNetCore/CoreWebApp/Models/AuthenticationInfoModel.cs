using Lacuna.RestPki.Client;
using System;
using System.Collections.Generic;
using System.Linq;
using System.Threading.Tasks;

namespace CoreWebApp.Models {
	public class AuthenticationInfoModel {
		public PKCertificate UserCert { get; set; }
	}
}
