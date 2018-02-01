import { Component, OnInit, NgZone } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { BlockUI, NgBlockUI } from 'ng-block-ui';
import { UtilService } from '../../services/util.service';
import { ConfigService } from '../../services/config.service';
import LacunaWebPKI, { CertificateModel } from '../../lacuna-webpki/lacuna-web-pki';

@Component({
    selector: 'authentication',
    templateUrl: './authentication.component.html'
})
export class AuthenticationComponent implements OnInit {
    constructor(private zone: NgZone, private http: HttpClient, private util: UtilService, private config: ConfigService) {
    }

    @BlockUI() blockUI: NgBlockUI;

    certificates: CertificateModel[];
    selectedCertificate: CertificateModel;

    // Create an instance of the LacunaWebPKI "object"
    pki = new LacunaWebPKI(this.config.getWebPkiLicense());

    ngOnInit() {
        // Block the UI while we get things ready
        this.blockUI.start();

        // Call the init() method on the LacunaWebPKI object, passing a callback for when
        // the component is ready to be used and another to be called when an error occurrs
        // on any of the subsequent operations. For more information, see:
        // https://webpki.lacunasoftware.com/#/Documentation#coding-the-first-lines
        // http://webpki.lacunasoftware.com/Help/classes/LacunaWebPKI.html#method_init
        this.pki.init({
            ready: this.loadCertificates,
            defaultError: this.onWebPkiError,
            restPkiUrl: this.config.getRestPkiEndpoint(), // URL of the Rest PKI instance to be used
            ngZone: this.zone
        });
    }

    // -------------------------------------------------------------------------------------------------
    // Function called when the user clicks the "Refresh" button
    // -------------------------------------------------------------------------------------------------
    refresh() {
        this.blockUI.start();
        this.loadCertificates();
    }

    // -------------------------------------------------------------------------------------------------
    // Function that loads the certificates, either on startup or when the user
    // clicks the "Refresh" button. At this point, the UI is already blocked.
    // -------------------------------------------------------------------------------------------------
    loadCertificates() {
        // Call the listCertificates() method to list the user's certificates. For more information see
        // http://webpki.lacunasoftware.com/Help/classes/LacunaWebPKI.html#method_listCertificates
        this.pki.listCertificates({

            // specify that expired certificates should be ignored
            //filter: pki.filters.isWithinValidity,

            // in order to list only certificates within validity period and having a CPF (ICP-Brasil), use this instead:
            //filter: pki.filters.all(pki.filters.hasPkiBrazilCpf, pki.filters.isWithinValidity),

        }).success((certificates) => {

            // Remember the selected certificate (see below)
            var originalSelected = (this.selectedCertificate) ? this.selectedCertificate.thumbprint : "";

            // Set available certificates on scope
            this.certificates = certificates;

            // Recover previous selection
            for (let c of certificates) {
                if (c.thumbprint === originalSelected) {
                    this.selectedCertificate = c;
                }
            };

            // If a certificate couldn't be selected, select the first of the available certificates
            if (certificates.length > 0 && !this.selectedCertificate) {
                this.selectedCertificate = certificates[0];
            }

            // once the certificates have been listed, unblock the UI
            this.blockUI.stop();

        });
    }

    getCertificateDisplayName(cert: CertificateModel) {
        return cert.subjectName + ' (expires on ' + cert.validityEnd.toDateString() + ', issued by ' + cert.issuerName + ')';
    }

    // -------------------------------------------------------------------------------------------------
    // Function called when the user clicks the "Sign In" button
    // -------------------------------------------------------------------------------------------------
    signIn() {
        if (!this.selectedCertificate) {
            this.util.showMessage('Message', 'Please select a certificate');
            return;
        };

        this.blockUI.start();
        this.http.get('/Api/Authentication').subscribe(this.onTokenAcquired, (error: any) => {
            this.blockUI.stop();
            this.util.handleServerError(error);
        });
    }

    // -------------------------------------------------------------------------------------------------
    // Function called once the server replies with the token for the authentication
    // -------------------------------------------------------------------------------------------------
    onTokenAcquired(response: any) {
        var token = response.data;

        this.pki.signWithRestPki({
            thumbprint: this.selectedCertificate.thumbprint,
            token: token
        }).success(() => {
            this.http.post('/Api/Authentication/' + token, null).subscribe(this.onAuthSuccess, (error: any) => {
                this.blockUI.stop();
                this.util.handleServerError(error);
            });
        });
    }

    // -------------------------------------------------------------------------------------------------
    // Function called once the server replies with the user certificate
    // -------------------------------------------------------------------------------------------------
    onAuthSuccess(response: any) {
        this.blockUI.stop();
        this.util.showMessage('Authentication successful!', 'Click OK to see the certificate details', () => this.util.showCertificate(response.data.certificate));
    }

    // -------------------------------------------------------------------------------------------------
    // Function called if an error occurs on the Web PKI component
    // -------------------------------------------------------------------------------------------------
    onWebPkiError(message: string, error: string, origin: string) {
        // Unblock the UI
        this.blockUI.stop();
        // Log the error to the browser console (for debugging purposes)
        if (console) {
            console.log('An error has occurred on the signature browser component: ' + message, error);
        }
        //// Show the message to the user
        this.util.showMessage('Error', message);
    }

}
