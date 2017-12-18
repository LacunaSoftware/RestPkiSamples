using System;
using System.Collections.Generic;
using System.Linq;
using System.Web;

namespace Lacuna.RestPki.SampleSite.Models {
	public class BatchSignatureInitRequest {
		public string Certificate { get; set; }
	}
	public class BatchSignatureInitResponse {
		public Guid BatchId { get; set; }
	}
	public class BatchSignatureStartRequest {
		public Guid BatchId { get; set; }
		public int DocumentId { get; set; }
	}
	public class BatchSignatureStartResponse {
		public string Token { get; set; }
		public string ToSignHash { get; set; }
		public string DigestAlgorithmOid { get; set; }
	}
	public class BatchSignatureCompleteRequest {
 		public string Token { get; set; }
		public string Signature { get; set; }
	}
}