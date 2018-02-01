import { Component, Inject } from '@angular/core';
import { MAT_DIALOG_DATA } from '@angular/material';
import { RestPkiValidationResults, RestPkiValidationItem } from '../../types.common';

@Component({
    selector: 'validation-results-dialog',
    templateUrl: './validation-results-dialog.component.html'
})
export class ValidationResultsDialogComponent {

    public constructor( @Inject(MAT_DIALOG_DATA) public data: any) {
        if (data) {
            this.model = data.model;
        }

        this.itemStack = [
            {
                message: 'Validation results',
                innerValidationResults: this.model,
                breadcrumbIndex: 0,
                detail: "",
                type: ""
            }
        ];
    }

    public model: RestPkiValidationResults;
    public itemStack: RestPkiValidationItem[];

    public showDetails(item: RestPkiValidationItem) {
        if (item.innerValidationResults) {
            item.breadcrumbIndex = this.itemStack.length;
            this.itemStack.push(item);
            this.model = item.innerValidationResults;
        }
    }

    public showParent(item: RestPkiValidationItem) {
        while (this.itemStack.length - 1 !== item.breadcrumbIndex) {
            this.itemStack.pop();
        }
        this.model = item.innerValidationResults;
    }
}