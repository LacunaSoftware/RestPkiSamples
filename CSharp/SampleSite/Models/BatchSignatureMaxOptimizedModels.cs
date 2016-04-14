using System;
using System.Collections.Generic;
using System.Linq;
using System.Web;

namespace Lacuna.RestPki.SampleSite.Models {

	public class BatchSignatureMaxOptimizedStartBatchRequest {
		public byte[] Certificate { get; set; }
	}

	public class BatchSignatureMaxOptimizedStartStepRequest {
		public string BatchId { get; set; }
		public int DocumentId { get; set; }
	}

	public class BatchSignatureMaxOptimizedStartStepResponse {
		public string Token { get; set; }
		public byte[] ToSignHash { get; set; }
		public string DigestAlgorithmOid { get; set; }
	}

	public class BatchSignatureMaxOptimizedCompleteStepRequest {
		public string Token { get; set; }
		public byte[] Signature { get; set; }
	}
}
