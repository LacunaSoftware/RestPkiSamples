import { Component, Inject } from '@angular/core';
import { MAT_DIALOG_DATA } from '@angular/material';
import { DialogBaseComponent } from '../dialog-base/dialog-base.component';
import { RestPkiCertModel } from '../../types.common';

@Component({
    selector: 'certificate-dialog',
    templateUrl: './certificate-dialog.component.html'
})
export class CertificateDialogComponent {
    public constructor( @Inject(MAT_DIALOG_DATA) public data: any) {
        if (data) {
            this.model = data.model;
        }        
    }

    public certBreadcrumb: RestPkiCertModel[];
    public model: RestPkiCertModel;

    public hasIssuer() {
        return this.model != null && this.model.issuer != null;
    }

    public showIssuer() {
        if (this.model.issuer) {
            this.model.breadcrumbIndex = this.certBreadcrumb.length;
            this.certBreadcrumb.push(this.model);
            this.model = this.model.issuer;
        }
    }

    public showIssued(cert: RestPkiCertModel) {
        while (this.certBreadcrumb.length !== cert.breadcrumbIndex) {
            this.certBreadcrumb.pop();
        }
        this.model = cert;
    }
}