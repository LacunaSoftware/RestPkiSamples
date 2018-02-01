using Lacuna.RestPki.Api;
using Lacuna.RestPki.Client;
using System;
using System.Collections.Generic;
using System.Linq;
using System.Threading.Tasks;

namespace NewAngular2.Models {
    public class OpenCadesSignatureResponse {

        public CmsContentTypes EncapsulatedContentType { get; private set; }
        public bool HasEncapsulatedContent { get; private set; }
        public List<CadesSignerModel> Signers { get; private set; }

        public OpenCadesSignatureResponse(CadesSignature signature) {
            this.EncapsulatedContentType = signature.EncapsulatedContentType;
            this.HasEncapsulatedContent = signature.HasEncapsulatedContent;
            this.Signers = signature.Signers.Select(m => new CadesSignerModel(m)).ToList();
        }
    }

    public class CadesSignerModel : CommonSignatureSignerModel {

        public DigestAlgorithmAndValue MessageDigest { get; private set; }

        public CadesSignerModel(CadesSignerInfo signer) : base(signer) {
            this.MessageDigest = signer.MessageDigest;
        }
    }
}
