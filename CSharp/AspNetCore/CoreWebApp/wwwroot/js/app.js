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

    $routeProvider.when('/open-pades-signature', {
        templateUrl: 'views/open-signature.html',
        controller: 'openPadesSignatureController'
    });

	$routeProvider.when('/cades-signature', {
		templateUrl: 'views/cades-signature.html',
		controller: 'cadesSignatureController'
	});

    $routeProvider.when('/open-cades-signature', {
        templateUrl: 'views/open-signature.html',
        controller: 'openCadesSignatureController'
    });

	$routeProvider.when('/xml-element-signature', {
		templateUrl: 'views/xml-element-signature.html',
		controller: 'xmlElementSignatureController'
    });

    $routeProvider.when('/xml-full-signature', {
        templateUrl: 'views/xml-full-signature.html',
        controller: 'xmlFullSignatureController'
    });

    $routeProvider.when('/batch-signature', {
        templateUrl: 'views/batch-signature.html',
        controller: 'batchSignatureController'
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

    var addAlert = function (type, message) {
        $('#messagesPanel').append(
            '<div class="alert alert-' + type + ' alert-dismissible">' +
            '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>' +
            '<span>' + message + '</span>' +
            '</div>');
    };

	return {
		showMessage: showMessage,
		showSignatureResults: showSignatureResults,
		showCertificate: showCertificate,
		showValidationResults: showValidationResults,
        handleServerError: handleServerError,
        addAlert: addAlert
	};
}]);

app.filter('digits', function() {
    return function(input, digits) {
        var zeros = '';
        for (var i = digits - 1; i > 0; i--) {
            if (input < Math.pow(10, i)) {
                zeros += 0;
            }
        }
        return zeros + input;
    };
});