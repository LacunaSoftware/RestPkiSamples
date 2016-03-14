module.exports = {
    standardSignaturePolicies: {
        padesBes: '78d20b33-014d-440e-ad07-929f05d00cdf',
        cadesBes: 'a4522485-c9e5-46c3-950b-0d6e951e17d1',

        pkiBrazilCadesAdrBasica:'3ddd8001-1672-4eb5-a4a2-6e32b17ddc46',
        pkiBrazilCadesAdrTempo: 'a5332ad1-d105-447c-a4bb-b5d02177e439',
        pkiBrazilCadesAdrValidacao: '92378630-dddf-45eb-8296-8fee0b73d5bb',
        pkiBrazilCadesAdrCompleta: '30d881e7-924a-4a14-b5cc-d5a1717d92f6',

        xadesBes: '1beba282-d1b6-4458-8e46-bd8ad6800b54',
        xmlDSigBasic: '2bb5d8c9-49ba-4c62-8104-8141f6459d08',
        pkiBrazilXadesAdrBasica: '1cf5db62-58b6-40ba-88a3-d41bada9b621',
        pkiBrazilXadesAdrTempo: '5aa2e0af-5269-43b0-8d45-f4ef52921f04',
        pkiBrazilNFePadraoNacional: 'a3c24251-d43a-4ba4-b25d-ee8e2ab24f06'
    },

    standardSecurityContexts: {
        pkiBrazil: '201856ce-273c-4058-a872-8937bd547d36',
        pkiItaly: 'c438b17e-4862-446b-86ad-6f85734f0bfe',
        windowsServer: '3881384c-a54d-45c5-bbe9-976b674f5ec7'
    },

    checkResponse: function (err, restRes, body, next) {
        // Status codes 200-299 indicate success
        if (err || restRes.statusCode < 200 || restRes.statusCode >= 300) {
            if (!err) {
				var msg = 'REST PKI returned status code ' + restRes.statusCode + ' (' + restRes.statusMessage + ')';
				if (body.message != null) {
					msg += ': ' + body.message;
				}
                err = new Error(msg);
            }
			if (next) {
				next(err);
			}
			return false;
        } else {
            return true;
        }
    }
};