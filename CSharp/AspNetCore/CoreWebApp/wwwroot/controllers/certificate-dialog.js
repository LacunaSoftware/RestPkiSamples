'use strict';
app.controller('certificateDialogController', ['$scope', '$uibModalInstance', 'model', function ($scope, $modalInstance, model) {

	$scope.certBreadcrumb = [];
	$scope.model = model;

	$scope.hasIssuer = function () {
		return $scope.model != null && $scope.model.issuer != null;
	}

	$scope.showIssuer = function () {
		if ($scope.model.issuer) {
			$scope.model.breadcrumbIndex = $scope.certBreadcrumb.length;
			$scope.certBreadcrumb.push($scope.model);
			$scope.model = $scope.model.issuer;
		}
	};

	$scope.showIssued = function (cert) {
		while ($scope.certBreadcrumb.length !== cert.breadcrumbIndex) {
			$scope.certBreadcrumb.pop();
		}
		$scope.model = cert;
	};

	$scope.close = function () {
		$modalInstance.close();
	};
}]);
