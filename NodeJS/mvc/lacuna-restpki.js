module.exports = {
    standardSignaturePolicies: {
        padesBasic: '78d20b33-014d-440e-ad07-929f05d00cdf',
        padesBasicWithPkiBrazilCerts: '3fec800c-366c-49bf-82c5-2e72154e70f6',
        padesPadesTWithPkiBrazilCerts: '6a39aeea-a2d0-4754-bf8c-19da15296ddb',
        pkiBrazilPadesAdrBasica: '531d5012-4c0d-4b6f-89e8-ebdcc605d7c2',
        pkiBrazilPadesAdrTempo: '10f0d9a5-a0a9-42e9-9523-e181ce05a25b',

        cadesBes: 'a4522485-c9e5-46c3-950b-0d6e951e17d1',
        cadesBesWithSigningTimeAndNoCrls: '8108539d-c137-4f45-a1f2-de5305bc0a37',
        pkiBrazilCadesAdrBasica:'3ddd8001-1672-4eb5-a4a2-6e32b17ddc46',
        pkiBrazilCadesAdrTempo: 'a5332ad1-d105-447c-a4bb-b5d02177e439',
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
                err = new Error('REST PKI returned status code ' + restRes.statusCode + ' (' + restRes.statusMessage + ')');
            }
            next(err);
            return false;
        } else {
            return true;
        }
    }
};