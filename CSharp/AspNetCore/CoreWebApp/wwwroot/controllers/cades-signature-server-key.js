'use strict';
app.controller('cadesSignatureServerKeyController', ['$scope', '$http', '$routeParams', 'blockUI', 'util', function ($scope, $http, $routeParams, blockUI, util) {

    $scope.signerCert = null;
    $scope.filename = null;

    // Create an instance of the LacunaWebPKI "object"
    var pki = new LacunaWebPKI(_webPkiLicense);

    // -------------------------------------------------------------------------------------------------
    // Function that initializes the signature
    // -------------------------------------------------------------------------------------------------
    var init = function () {

        // Block the UI while we get things ready
        blockUI.start();

        // Retrive parameter "userfile"
        var userfile = $routeParams.userfile;

        // Call server to perform the signature
        $http.post('Api/CadesSignatureServerKey?userfile=' + userfile).then(function (response) {
            $scope.signerCert = response.data.certificate;
            $scope.filename = response.data.filename;
            blockUI.stop();
        }, util.handleServerError);

    };

    init();

}]);