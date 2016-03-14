'use strict';
app.controller('padesSignatureController', ['$scope', '$http', 'blockUI', function ($scope, $http, blockUI) {
	
	$scope.view = 'input';
	$scope.selectedCertificate = null;
	$scope.certificates = [];
	$scope.signedPdf = null;
	$scope.certInfo = null;

	var pki = new LacunaWebPKI();

	$scope.sign = function() {
		if ($scope.selectedCertificate == null) {
			alert('Select a certificate');
			return;
		}
		
		blockUI.start('Signing...');
		$http.post('/signatureStartPades').success(onSignatureStartCompleted).error(onServerError);
	};
	
	var onSignatureStartCompleted = function (token) {
		pki.signWithRestPki({
			token: token,
			thumbprint: $scope.selectedCertificate.thumbprint
		}).success(function (token) {
			onSignWithRestPkiCompleted(token);
		});
	};
	
	var onSignWithRestPkiCompleted = function(token) {
		$http.post('/completePadesSignature', {
			token: token
		}).success(onCompleteSignautureCompleted).error(onServerError);
	};
	
	var onCompleteSignautureCompleted = function (response) {
		blockUI.stop();
		$scope.certInfo = response.signerCert;
		$scope.signedPdf = response.signedFileName;
		$scope.view = 'success';
	};
	
	var onServerError = function (data, status, headers, config) {
		blockUI.stop();
		alert('Server error ' + data.message);
	};

	var start = function () {
        blockUI.start();
        pki.init({
            angularScope: $scope,
            ready: onWebPkiReady,
        });
    };
	
	var onWebPkiReady = function () {
        loadCertificates();
    };

    $scope.reloadCertificates = function () {
        blockUI.start('Loading Certificates...');
        loadCertificates();
    };

    var loadCertificates = function () {       
        pki.listCertificates().success(function (certs) {
            $scope.certificates = certs;
            blockUI.stop();
        });
    };

    $scope.getCertificateDisplayName = function (cert) {
        return cert.subjectName + ' (issued by ' + cert.issuerName + ')';
    };
		
	
	// INIT
	start();

}]);
