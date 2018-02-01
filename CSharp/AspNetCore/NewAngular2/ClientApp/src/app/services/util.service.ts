import { Injectable } from '@angular/core';
import { MatDialog } from '@angular/material';
import { RestPkiCertModel, RestPkiValidationResults } from '../types.common';
import { CertificateDialogComponent } from '../dialogs/certificate-dialog/certificate-dialog.component';
import { MessageDialogComponent } from '../dialogs/message-dialog/message-dialog.component';
import { SignatureResultsDialogComponent } from '../dialogs/signature-results-dialog/signature-results-dialog.component';
import { ValidationResultsDialogComponent } from '../dialogs/validation-results-dialog/validation-results-dialog.component';

@Injectable()
// This service is meant to encapsulate and ease the usage of the dialogs which are inside '/dialogs'
export class UtilService {
    constructor(public dialog: MatDialog) { }

    showMessage(title: string, message: string, callback?: (result:any) => void) {
        let dialogRef = this.dialog.open(MessageDialogComponent, {
            data: {
                title: title,
                message: message
            }
        });

        if (callback) {
            dialogRef.afterClosed().subscribe(callback);
        }        
    }

    showSignatureResults() {
        //todo
    }

    showCertificate(cert: RestPkiCertModel) {
        this.dialog.open(CertificateDialogComponent, {
            width: '800px',
            data: {
                model: cert
            }
        });
    }

    showValidationResults(vr: RestPkiValidationResults) {
        this.dialog.open(ValidationResultsDialogComponent, {
            data: {
                model: vr
            }
        });
    }

    handleServerError(error: any) {
        console.error(error);
        if (error.status === 400 && error.data.validationResults) {
            this.showMessage('Validation failed!', 'One or more validations failed. Click OK to see more details.', () => {
                this.showValidationResults(error.data.validationResults);
            });
        } else {
            this.showMessage('An error has occurred', error.data.message || 'HTTP error ' + error.status);
        }
    }
}
