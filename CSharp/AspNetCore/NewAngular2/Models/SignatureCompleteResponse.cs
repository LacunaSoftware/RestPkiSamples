using System;
using System.Collections.Generic;
using System.Linq;
using System.Threading.Tasks;

namespace NewAngular2.Models {
	public class SignatureCompleteResponse {
		public string Filename { get; set; }
		public CertificateModel Certificate { get; set; }
	}
}
