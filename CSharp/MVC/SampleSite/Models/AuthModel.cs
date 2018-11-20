using System;
using System.Collections.Generic;
using System.Linq;
using System.Web;

namespace Lacuna.RestPki.SampleSite.Models {
	public class AuthModel {

		public byte[] Nonce { get; set; }
		public byte[] CertContent { get; set; }
		public byte[] Signature { get; set; }
	}
}