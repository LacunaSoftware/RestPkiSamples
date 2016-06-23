using System;
using System.Collections.Generic;
using System.Linq;
using System.Web;

namespace Lacuna.RestPki.SampleSite.Models {
	public class BatchSignatureInitRequest {
		public byte[] Certificate { get; set; }
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
		public byte[] ToSignHash { get; set; }
		public string DigestAlgorithmOid { get; set; }
	}
	public class BatchSignatureCompleteRequest {
 		public string Token { get; set; }
		public byte[] Signature { get; set; }
	}
}