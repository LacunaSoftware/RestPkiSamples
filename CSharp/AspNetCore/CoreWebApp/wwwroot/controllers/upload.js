'use strict';
app.controller('uploadController', ['$scope', '$routeParams', '$location', 'Upload', 'util', function ($scope, $routeParams, $location, Upload, util) {

	var returnController = null;

	$scope.file = null;

	var init = function () {
		returnController = $routeParams.rc;
	};

	$scope.upload = function () {

		if (!$scope.file) {
			util.showMessage('Message', 'Please upload some file.');
			return;
		}

		Upload.upload({
			url: 'Api/Upload',
			method: 'POST',
			file: $scope.file
		}).then(function (response) {

			var userfile = response.data;
			$location.path('/' + returnController).search('userfile=' + userfile);

		}, util.handleServerError);
	};

	init();

}]);
