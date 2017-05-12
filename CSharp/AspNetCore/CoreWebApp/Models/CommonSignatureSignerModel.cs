using Lacuna.RestPki.Client;
using System;
using System.Collections.Generic;
using System.Linq;
using System.Threading.Tasks;

namespace CoreWebApp.Models {
    public class CommonSignatureSignerModel {

        public SignatureAlgorithmAndValue Signature { get; private set; }
        public SignaturePolicyIdentifier SignaturePolicy { get; private set; }
        public CertificateModel Certificate { get; private set; }
        public DateTimeOffset? SigningTime { get; private set; }
        public DateTimeOffset? CertifiedDateReference { get; private set; }
        public List<CadesTimestamp> Timestamps { get; private set; }
        public ValidationResultsModel ValidationResults { get; private set; }

        public CommonSignatureSignerModel(CadesSignerInfo signer) {
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

        public CommonSignatureSignerModel(XmlSignature signature) {
            this.Signature = signature.Signature;
            if (signature.SignaturePolicy != null) {
                this.SignaturePolicy = signature.SignaturePolicy;
            }
            this.Certificate = new CertificateModel(signature.Certificate);
            this.SigningTime = signature.SigningTime;
            this.CertifiedDateReference = signature.CertifiedDateReference;
            this.Timestamps = new List<CadesTimestamp>();
            if (signature.Timestamps != null) {
                this.Timestamps.AddRange(signature.Timestamps);
            }
            if (signature.ValidationResults != null) {
                this.ValidationResults = new ValidationResultsModel(signature.ValidationResults);
            }
        }
    }
}
