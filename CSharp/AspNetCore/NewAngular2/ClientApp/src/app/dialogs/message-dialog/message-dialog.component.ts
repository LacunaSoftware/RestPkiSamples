import { Component, Inject } from '@angular/core';
import { MAT_DIALOG_DATA } from '@angular/material';
import { DialogBaseComponent } from '../dialog-base/dialog-base.component';

@Component({
    selector: 'message-dialog',
    templateUrl: './message-dialog.component.html'
})
export class MessageDialogComponent {

    public constructor( @Inject(MAT_DIALOG_DATA) public data: any) {
        if (data) {
            this.title = data.title;
            this.message = data.message;
        }
    }

    public title: string;
    public message: string;
}
