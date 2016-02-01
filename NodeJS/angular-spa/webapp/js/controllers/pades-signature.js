'use strict';
app.controller('padesSignatureController', ['$scope', '$http', 'blockUI', function ($scope, $http, blockUI) {

	$scope.selectedCertificate = null;

	$scope.certificates = [
		{
			subjectName: 'Bruno',
			issuerName: 'CA1'
		},
		{
			subjectName: 'André',
			issuerName: 'CA2'
		}
	];
	
	$scope.sign = function() {
		blockUI.start();
		$http.post('/startPadesSignature').success(onSignatureStartCompleted).error(onServerError);
	};
	
	var onSignatureStartCompleted = function (token) {
		onSignWithRestPkiCompleted(token);
	};
	
	var onSignWithRestPkiCompleted = function(token) {
		$http.post('/completePadesSignature', {
			token: token
		}).success(onCompleteSignautureCompleted).error(onServerError);
	};
	
	var onCompleteSignautureCompleted = function () {
		blockUI.stop();
	};
	
	var onServerError = function (data, status, headers, config) {
		blockUI.stop();
		alert('Server error ' + status);
	};

}]);
