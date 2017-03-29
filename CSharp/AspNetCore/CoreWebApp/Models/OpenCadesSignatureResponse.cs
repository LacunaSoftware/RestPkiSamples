using Lacuna.RestPki.Api;
using Lacuna.RestPki.Client;
using System;
using System.Collections.Generic;
using System.Linq;
using System.Threading.Tasks;

namespace CoreWebApp.Models {
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

    public class CadesSignerModel {

        public DigestAlgorithmAndValue MessageDigest { get; private set; }
        public SignatureAlgorithmAndValue Signature { get; private set; }
        public SignaturePolicyIdentifier SignaturePolicy { get; private set; }
        public CertificateModel Certificate { get; private set; }
        public DateTimeOffset? SigningTime { get; private set; }
        public DateTimeOffset? CertifiedDateReference { get; private set; }
        public List<CadesTimestamp> Timestamps { get; private set; }
        public ValidationResultsModel ValidationResults { get; private set; }

        public CadesSignerModel(CadesSignerInfo signer) {
            this.MessageDigest = signer.MessageDigest;
            this.Signature = signer.Signature;
            if (signer.SignaturePolicy != null) {
                this.SignaturePolicy = signer.SignaturePolicy;
            }
            this.Certificate = new CertificateModel(signer.Certificate);
            this.SigningTime = signer.SigningTime;
            this.CertifiedDateReference = signer.CertifiedDateReference;
            this.Timestamps = new List<CadesTimestamp>();
            if (signer.Timestamps != null) {
                this.Timestamps.AddRange(signer.Timestamps);
            }
            if (signer.ValidationResults != null) {
                this.ValidationResults = new ValidationResultsModel(signer.ValidationResults);
            }
        }
    }
}
