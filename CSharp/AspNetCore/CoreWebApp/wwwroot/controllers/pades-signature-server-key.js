'use strict';
app.controller('padesSignatureServerKeyController', ['$scope', '$http', '$routeParams', 'blockUI', 'util', function ($scope, $http, $routeParams, blockUI, util) {

    // Create an instance of the LacunaWebPKI "object"
    var pki = new LacunaWebPKI(_webPkiLicense);

    // -------------------------------------------------------------------------------------------------
    // Function that initializes the signature
    // -------------------------------------------------------------------------------------------------
    var init = function () {

        // Block the UI while we get things ready
        blockUI.start();

        // Retrieve parameter "userfile"
        var userfile = $routeParams.userfile;

        // Call server to perform the signature
        $http.post('Api/PadesSignatureServerKey?userfile=' + userfile).then(function (response) {
            $scope.signerCert = response.data.certificate;
            $scope.filename = response.data.filename;
            blockUI.stop();
        }, util.handleServerError);

    };

    init();

}]);