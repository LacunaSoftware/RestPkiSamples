var pki = new LacunaWebPKI();

function initWebPki() {
    pki.init({
        ready: loadCertificates,
        defaultError: onWebPkiError
    });
}

function loadCertificates() {
    var select = document.getElementById('certificateSelect');
    while (select.options.length > 0) {
        select.remove(0);
    }
    pki.listCertificates().success(function (certs) {
        for (var i = 0; i < certs.length; i++) {
            var cert = certs[i];
            var option = document.createElement('option');
            option.value = cert.thumbprint;
            option.text = cert.subjectName + ' (issued by ' + cert.issuerName + ')';
            select.add(option);
        }
    });
}

function getSelectedCertificateThumbprint() {
    var select = document.getElementById('certificateSelect');
    if (select.selectedIndex < 0) {
        return null;
    }
    return select.options[select.selectedIndex].value;
}

function onWebPkiError(message, error, origin) {
    alert(message);
}

document.addEventListener('DOMContentLoaded', initWebPki);
