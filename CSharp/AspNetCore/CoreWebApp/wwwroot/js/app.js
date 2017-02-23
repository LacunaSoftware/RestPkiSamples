var app = angular.module('PkiSdkWebApiSamples', ['ngRoute', 'ui.bootstrap', 'blockUI', 'ngFileUpload']);

app.config(['$routeProvider', '$locationProvider', function ($routeProvider, $locationProvider) {

	$routeProvider.when('/', {
		templateUrl: 'views/home.html',
		controller: 'homeController'
    });

    $routeProvider.when('/upload/:rc', {
        templateUrl: 'views/upload.html',
        controller: 'uploadController'
    });

	$routeProvider.when('/authentication', {
		templateUrl: 'views/authentication.html',
		controller: 'authenticationController'
	});

	$routeProvider.when('/pades-signature', {
		templateUrl: 'views/pades-signature.html',
		controller: 'padesSignatureController'
	});

	$routeProvider.when('/cades-signature', {
		templateUrl: 'views/cades-signature.html',
		controller: 'cadesSignatureController'
	});

	$routeProvider.when('/xml-element-signature', {
		templateUrl: 'views/xml-element-signature.html',
		controller: 'xmlElementSignatureController'
	});

	$routeProvider.otherwise({ redirectTo: "/" });
	$locationProvider.html5Mode(true);
}]);

app.config(['blockUIConfig', function (blockUIConfig) {
	blockUIConfig.autoBlock = false;
	blockUIConfig.message = 'Please wait ...';
}]);

app.factory('util', ['$uibModal', 'blockUI', function ($modal, blockUI) {

	var showMessage = function (title, message) {
		return $modal.open({
			templateUrl: 'views/dialogs/message.html',
			controller: ['$scope', function ($scope) {
				$scope.title = title;
				$scope.message = message;
			}]
		});
	};

    var showSignatureResults = function (results) {
		return $modal.open({
			templateUrl: 'views/dialogs/signature-results.html',
			controller: 'signatureResultsDialogController',
			size: 'lg',
            resolve: {
                results: function () { return results; }
			}
		});
	};

	var showCertificate = function (model) {
		return $modal.open({
			templateUrl: 'views/dialogs/certificate.html',
			controller: 'certificateDialogController',
			size: 'lg',
			resolve: {
				model: function () { return model; }
			}
		});
	};

	var showValidationResults = function (vr) {
		return $modal.open({
			templateUrl: 'views/dialogs/validation-results.html',
			controller: 'validationResultsDialogController',
			size: 'lg',
			resolve: {
				model: function () { return vr; }
			}
		});
	};

	var handleServerError = function (response) {
		blockUI.stop();
		if (response.status === 400 && response.data.validationResults) {
			showMessage('Validation failed!', 'One or more validations failed. Click OK to see more details.').result.then(function () {
				showValidationResults(response.data.validationResults);
			});
		} else {
			showMessage('An error has occurred', response.data.message || 'HTTP error ' + response.status);
		}
	};

	return {
		showMessage: showMessage,
		showSignatureResults: showSignatureResults,
		showCertificate: showCertificate,
		showValidationResults: showValidationResults,
		handleServerError: handleServerError
	};
}]);