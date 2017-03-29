'use strict';
app.controller('openCadesSignatureController', ['$scope', '$http', '$routeParams', 'blockUI', 'util', function ($scope, $http, $routeParams, blockUI, util) {

    $scope.signatureType = 'CAdES';
    $scope.model = null;

    var init = function () {

        // Block the UI
        blockUI.start();

        $http.get('Api/OpenCadesSignature/' + $routeParams.userfile).then(function (response) {
            $scope.model = response.data;

            // Unblock the UI
            blockUI.stop();
        }, util.handleServerError);
    };

    $scope.showCertificate = function (certificate) {
        util.showCertificate(certificate);
    };

    $scope.showValidationResults = function (vr) {
        if (vr == null) {
            return;
        }
        util.showValidationResults(vr);
    };

    init();

}]);