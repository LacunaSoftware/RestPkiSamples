using System;
using System.Collections.Generic;
using System.Linq;
using System.Web;

namespace SampleSite.Models {
	public class AuthenticationPostResponse {
		public bool Success { get; set; }
		public string Message { get; set; }
		public string ValidationResults { get; set; }
	}
}