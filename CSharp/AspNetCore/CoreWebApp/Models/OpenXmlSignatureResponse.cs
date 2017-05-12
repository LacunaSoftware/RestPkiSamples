using Lacuna.RestPki.Api;
using Lacuna.RestPki.Client;
using Newtonsoft.Json;
using Newtonsoft.Json.Converters;
using System;
using System.Collections.Generic;
using System.Linq;
using System.Threading.Tasks;

namespace CoreWebApp.Models {
    public class OpenXmlSignatureResponse {

        public List<XmlSignatureModel> Signatures;

        public OpenXmlSignatureResponse(List<XmlSignature> signatures) {
            this.Signatures = signatures.Select(m => new XmlSignatureModel(m)).ToList();
        }

        public class XmlSignatureModel : CommonSignatureSignerModel {

            [JsonConverter(typeof(StringEnumConverter))]
            public XmlSignedEntityTypes Type { get; private set; }
            public XmlElementInfo SignedElement { get; private set; }

            public XmlSignatureModel(XmlSignature signature) : base(signature) {
                this.Type = signature.Type;
                this.SignedElement = signature.SignedElement;
            }
        }
    }
}
