using System;
using System.Collections.Generic;
using System.Linq;
using System.Web;

namespace Lacuna.RestPki.SampleSite.Models {
    public class SignatureStartModel {
        public byte[] CertContent { get; set; }
        public byte[] CertThumb { get; set; }
    }
}