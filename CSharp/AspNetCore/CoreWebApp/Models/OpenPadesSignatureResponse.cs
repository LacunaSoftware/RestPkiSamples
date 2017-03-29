using Lacuna.RestPki.Client;
using System;
using System.Collections.Generic;
using System.Linq;
using System.Threading.Tasks;

namespace CoreWebApp.Models {
    public class OpenPadesSignatureResponse {

        public List<PadesSignerModel> Signers { get; private set; }

        public OpenPadesSignatureResponse(PadesSignature signature) {
            this.Signers = signature.Signers.Select(m => new PadesSignerModel(m)).ToList();
        }
    }

    public class PadesSignerModel : CadesSignerModel {

        public bool IsDocumentTimestamp { get; private set; }
        public string SignatureFieldName { get; private set; }

        public PadesSignerModel(PadesSignerInfo signer) : base(signer) {
            this.IsDocumentTimestamp = signer.IsDocumentTimestamp;
            this.SignatureFieldName = signer.SignatureFieldName;
        }

    }
}
