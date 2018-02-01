import { Component, Input } from '@angular/core';

@Component({
    selector: 'dialog-base',
    templateUrl: './dialog-base.component.html'
})
export class DialogBaseComponent {
    @Input() public title;
}