'use strict';
app.controller('validationResultsDialogController', ['$scope', '$uibModalInstance', 'util', 'model', function ($scope, $modalInstance, util, model) {

	$scope.model = model;
	$scope.itemStack = [
		{
			message: 'Validation results',
			innerValidationResults: model,
			breadcrumbIndex: 0
		}
	];

	$scope.showDetails = function (item) {
		if (item.innerValidationResults) {
			item.breadcrumbIndex = $scope.itemStack.length;
			$scope.itemStack.push(item);
			$scope.model = item.innerValidationResults;
		}
	};

	$scope.showParent = function (item) {
		while ($scope.itemStack.length - 1 !== item.breadcrumbIndex) {
			$scope.itemStack.pop();
		}
		$scope.model = item.innerValidationResults;
	};

	$scope.close = function () {
		$modalInstance.close();
	};
}]);
