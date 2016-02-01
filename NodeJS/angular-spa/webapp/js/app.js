var app = angular.module('RestPkiSamples', ['ngRoute', 'blockUI']);

app.config(['$routeProvider', function ($routeProvider) {

	$routeProvider.when('/', {
		templateUrl: 'views/home.html',
		controller: 'homeController'
	});

	$routeProvider.when('/pades-signature', {
		templateUrl: 'views/pades-signature.html',
		controller: 'padesSignatureController'
	});

	$routeProvider.otherwise({ redirectTo: "/" });
}]);

app.config(['blockUIConfig', function (blockUIConfig) {
	blockUIConfig.autoBlock = false;
	blockUIConfig.message = 'Please wait ...';
}]);
