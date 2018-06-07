using Lacuna.RestPki.Client;
using System;
using System.Collections.Generic;
using System.Linq;
using System.Web;

namespace Lacuna.RestPki.SampleSite.Models {
	public class CheckModel {
		public PadesSignature Signature { get; set; }
		public string File { get; set; }
	}
}