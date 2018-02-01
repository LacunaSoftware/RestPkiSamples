import { Component } from '@angular/core';
import { BlockUI, NgBlockUI } from 'ng-block-ui';
import { UtilService } from '../../services/util.service';
import { ConfigService } from '../../services/config.service';
import { RestPkiCertModel } from '../../types.common';

@Component({
    selector: 'app-counter-component',
    templateUrl: './counter.component.html'
})
export class CounterComponent {
    constructor(private dialogs: UtilService, private config: ConfigService) { }

    @BlockUI() blockUI: NgBlockUI;
    public currentCount = 0;

    public incrementCounter() {
        this.currentCount++;

        //this.blockUI.start();
        //setTimeout(() => {
        //    this.blockUI.stop();
        //}, 2000);

        //this.dialogs.showMessage("Counter", "Este é uma mensagem do Counter Component.", () => {
        //    console.log('AccessToken: ' + this.config.getAccessToken());
        //    console.log('RestPkiEndpoint: ' + this.config.getRestPkiEndpoint());
        //    console.log('WebPkiLicense: ' + this.config.getWebPkiLicense());
        //});

        //var vr = {
        //    passedChecks: [{
        //        type: "alpha",
        //        message: "all checks passed correctly",
        //        detail: "no details",
        //        innerValidationResults: null
        //    }],
        //    warnings: [{
        //        type: "alpha",
        //        message: "all checks passed correctly",
        //        detail: "no details",
        //        innerValidationResults: null
        //    }],
        //    errors: [{
        //        type: "alpha",
        //        message: "all checks passed correctly",
        //        detail: "no details",
        //        innerValidationResults: null
        //    }],
        //};

        //this.dialogs.showValidationResults(vr);

        var cert: RestPkiCertModel = {
            subjectName: {
                commonName: "João",
                emailAddress: "joao@joao.com",
                country: "Brazil",
                organization: "João LTDA",
                organizationUnit: "JCORP"
            },
            issuerName: {
                commonName: "José",
                emailAddress: "jose@jose.com",
                country: "Ecuador",
                organization: "Jose LTDA",
                organizationUnit: "ZECORP"
            },
            serialNumber: 'asdf9820345u4iuoer89upiojhg89piwerkfdbio34er90jiok2l4wrtbjiókl',
            validityEnd: new Date(), 
            validityStart: new Date()
        };

        this.dialogs.showCertificate(cert);
    }
}
