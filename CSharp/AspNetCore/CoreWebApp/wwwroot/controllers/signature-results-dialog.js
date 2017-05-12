'use strict';
app.controller('signatureResultsDialogController', ['$scope', '$http', '$uibModalInstance', '$location', 'results', function ($scope, $http, $modalInstance, $location, results) {

	$scope.certBreadcrumb = [];
	$scope.model = results.certificate;
    $scope.cosignUrl = results.cosignUrl;
    $scope.openSignatureUrl = results.openSignatureUrl;
    $scope.signedFile = results.signedfile;
    $scope.cmsfile = results.cmsfile;
	$scope.filename = null;

	var init = function () {
        if (results.signedfile) {
            $scope.filename = results.signedfile;
        } else if (results.cmsfile) {
            $scope.filename = results.cmsfile;
        } else {
            $modalInstance.close();
        }
	};

	$scope.hasIssuer = function () {
		return $scope.model != null && $scope.model.issuer != null;
	};

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

	$scope.cosign = function () {
		$modalInstance.close();
    }

    $scope.openSignature = function () {
        $modalInstance.close();
    };

	$scope.close = function () {
		$modalInstance.close();
	};

	init();

}]);
