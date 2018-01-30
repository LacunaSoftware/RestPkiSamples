using System;
using System.Collections.Generic;
using System.Linq;
using System.Web;

namespace Lacuna.RestPki.SampleSite.Models {
    public class SignatureCompleteModel {
        public byte[] CertThumb { get; set; }
        public byte[] ToSignHash { get; set; }
        public byte[] Signature { get; set; }
        public string Token { get; set; }
        public string DigestAlgorithmOid { get; set; }
    }
}