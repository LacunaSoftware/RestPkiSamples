import { Component, Inject, OnInit } from '@angular/core';
import { MAT_DIALOG_DATA, MatDialogRef } from '@angular/material';
import { RestPkiCertModel } from '../../types.common';

@Component({
    selector: 'signature-results-dialog',
    templateUrl: './signature-results-dialog.component.html'
})
export class SignatureResultsDialogComponent implements OnInit  {
    public constructor(public dialogRef: MatDialogRef<SignatureResultsDialogComponent>, @Inject(MAT_DIALOG_DATA) public data: any) {
        if (data) {
            this.model = data.results.certificate;
            this.cosignUrl = data.results.cosignUrl;
            this.openSignatureUrl = data.results.openSignatureUrl;
            this.signedFile = data.results.signedfile;
            this.cmsfile = data.results.cmsfile;
        }
    }

    public certBreadcrumb: RestPkiCertModel[];
    public model: RestPkiCertModel;
    public cosignUrl: string;
    public openSignatureUrl: string;
    public signedFile: string;
    public cmsfile: string;
    public filename: string;

    ngOnInit() {
        if (this.signedFile) {
            this.filename = this.signedFile;
        } else if (this.cmsfile) {
            this.filename = this.cmsfile;
        } else {
            this.dialogRef.close();
        }
    }

    public hasIssuer() {
        return this.model != null && this.model.issuer != null;
    };

    public showIssuer() {
        if (this.model.issuer) {
            this.model.breadcrumbIndex = this.certBreadcrumb.length;
            this.certBreadcrumb.push(this.model);
            this.model = this.model.issuer;
        }
    };

    public showIssued(cert: RestPkiCertModel) {
        while (this.certBreadcrumb.length !== cert.breadcrumbIndex) {
            this.certBreadcrumb.pop();
        }
        this.model = cert;
    };

    public cosign() {
        this.dialogRef.close();
    }

    public openSignature() {
        this.dialogRef.close();
    };    
}