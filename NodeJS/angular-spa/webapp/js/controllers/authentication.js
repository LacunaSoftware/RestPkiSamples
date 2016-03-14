'use strict';
app.controller('authenticationController', ['$scope', '$http', 'blockUI', function ($scope, $http, blockUI) {
	
	$scope.view = 'input';
	$scope.selectedCertificate = null;
	$scope.certificates = [];
	$scope.certInfo = null;
	$scope.validationResultsString = null;

	var pki = new LacunaWebPKI();

	$scope.signIn = function () {
		if ($scope.selectedCertificate == null) {
			alert('Select a certificate');
			return;
		}
		
		blockUI.start('Signing in...');
		$http.get('/authenticationStart').success(onAuthenticationStarted).error(onServerError);
	};

	var onAuthenticationStarted = function (token) {
	    pki.signWithRestPki({
	        token: token,
	        thumbprint: $scope.selectedCertificate.thumbprint
	    }).success(function () {
	        // Once the operation is completed
	        $http.post('/completeAuthentication',  {
					token: token
			}).success(function (response) {
				if (response.success) {
					$scope.view = 'success';
					$scope.certInfo = response.certificate;
				} else {
					$scope.view = 'fail';
					$scope.validationResultsString = validationResultsToString(response.validationResults, '');
				}
				blockUI.stop();
			}).error(onServerError);
	    });
	};
	
	// Function to convert Validation Results to string
	var validationResultsToString = function (vr, ident) {
		var text = '';
		var itemIdent = ident + '\t';
		var i = i;
		
		text += ident + 'Errors:\n';
		for (i=0; i<vr.errors.length; i++) {
			text += itemIdent + vr.errors[i].message;
			if (vr.errors[i].detail) {
				text += ' (' + vr.errors[i].detail + ')';
			}
			text += '\n';
			if (vr.errors[i].innerValidationResults) {
				text += validationResultsToString(vr.errors[i].innerValidationResults, ident + '\t');
			}
		}
		
		text += ident + 'Warnings:\n';
		for (i=0; i<vr.warnings.length; i++) {
			text += itemIdent + vr.warnings[i].message;
			if (vr.warnings[i].detail) {
				text += ' (' + vr.warnings[i].detail + ')';
			}
			text += '\n';
			if (vr.warnings[i].innerValidationResults) {
				text += validationResultsToString(vr.warnings[i].innerValidationResults, ident + '\t');
			}
		}
		
		text += ident + 'Passed Checks:\n';
		for (i=0; i<vr.passedChecks.length; i++) {
			text += itemIdent + vr.passedChecks[i].message;
			if (vr.passedChecks[i].detail) {
				text += ' (' + vr.passedChecks[i].detail + ')';
			}
			text += '\n';
			if (vr.passedChecks[i].innerValidationResults) {
				text += validationResultsToString(vr.passedChecks[i].innerValidationResults, ident + '\t');
			}
		}
		return text;
	};
	
	var onServerError = function (data, status, headers, config) {
		blockUI.stop();
		alert('Server error ' + data.message);
	};
	
	var onWebPkiReady = function () {
        loadCertificates();
    };

    $scope.reloadCertificates = function () {
        blockUI.start('Loading Certificates...');
        loadCertificates();
    };

    var loadCertificates = function () {       
        console.log($scope.thecertificado);
        pki.listCertificates().success(function (certs) {
            $scope.certificates = certs;
            blockUI.stop();
        });
    };

    $scope.getCertificateDisplayName = function (cert) {
        return cert.subjectName + ' (issued by ' + cert.issuerName + ')';
    };
	
	$scope.back = function () {
		$scope.view = 'input';
	};
		
	var init = function () {
        blockUI.start();
        pki.init({
            angularScope: $scope,
            ready: onWebPkiReady,
        });
    };
	
	// INIT
	init();

}]);
