import { BrowserModule } from '@angular/platform-browser';
import { BrowserAnimationsModule } from '@angular/platform-browser/animations';
import { NgModule, APP_INITIALIZER } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { HttpClientModule } from '@angular/common/http';
import { RouterModule } from '@angular/router';
import { MatDialogModule } from '@angular/material';
import { BlockUIModule } from 'ng-block-ui';

// services
import { UtilService } from './services/util.service';
import { ConfigService } from './services/config.service';

// components
import { AppComponent } from './app.component';
import { NavMenuComponent } from './components/nav-menu/nav-menu.component';
import { HomeComponent } from './components/home/home.component';
import { CounterComponent } from './components/counter/counter.component';
import { FetchDataComponent } from './components/fetch-data/fetch-data.component';

import { AuthenticationComponent } from './components/authentication/authentication.component';
import { BatchSignatureComponent } from './components/batch-signature/batch-signature.component';
import { CadesSignatureComponent } from './components/cades-signature/cades-signature.component';
import { CadesSignatureServerKeyComponent } from './components/cades-signature-server-key/cades-signature-server-key.component';
import { CertificateDialogComponent } from './dialogs/certificate-dialog/certificate-dialog.component';
import { MessageDialogComponent } from './dialogs/message-dialog/message-dialog.component';
import { OpenCadesSignatureComponent } from './components/open-cades-signature/open-cades-signature.component';
import { OpenPadesSignatureComponent } from './components/open-pades-signature/open-pades-signature.component';
import { OpenXmlSignatureComponent } from './components/open-xml-signature/open-xml-signature.component';
import { PadesSignatureComponent } from './components/pades-signature/pades-signature.component';
import { PadesSignatureServerKeyComponent } from './components/pades-signature-server-key/pades-signature-server-key.component';
import { SignatureResultsDialogComponent } from './dialogs/signature-results-dialog/signature-results-dialog.component';
import { UploadComponent } from './components/upload/upload.component';
import { ValidationResultsDialogComponent } from './dialogs/validation-results-dialog/validation-results-dialog.component';
import { XmlElementSignatureComponent } from './components/xml-element-signature/xml-element-signature.component';
import { XmlFullSignatureComponent } from './components/xml-full-signature/xml-full-signature.component';

@NgModule({
    declarations: [
        AppComponent,
        NavMenuComponent,
        HomeComponent,
        CounterComponent,
        FetchDataComponent,

        AuthenticationComponent,
        BatchSignatureComponent,
        CadesSignatureComponent,
        CadesSignatureServerKeyComponent,
        CertificateDialogComponent,
        MessageDialogComponent,
        OpenCadesSignatureComponent,
        OpenPadesSignatureComponent,
        OpenXmlSignatureComponent,
        PadesSignatureComponent,
        PadesSignatureServerKeyComponent,
        SignatureResultsDialogComponent,
        UploadComponent,
        ValidationResultsDialogComponent,
        XmlElementSignatureComponent,
        XmlFullSignatureComponent
    ],
    imports: [
        BrowserModule.withServerTransition({ appId: 'ng-cli-universal' }),
        BrowserAnimationsModule,
        HttpClientModule,
        FormsModule,
        MatDialogModule,
        BlockUIModule,
        RouterModule.forRoot([
            { path: '', component: HomeComponent, pathMatch: 'full' },
            { path: 'counter', component: CounterComponent },
            { path: 'fetch-data', component: FetchDataComponent },

            { path: 'authentication', component: AuthenticationComponent },
            { path: 'batch-signature', component: BatchSignatureComponent },
            { path: 'cades-signature', component: CadesSignatureComponent },
            { path: 'cades-signature-server-key', component: CadesSignatureServerKeyComponent },
            { path: 'open-cades-signature', component: OpenCadesSignatureComponent },
            { path: 'open-pades-signature', component: OpenPadesSignatureComponent },
            { path: 'open-xml-signature', component: OpenXmlSignatureComponent },
            { path: 'pades-signature', component: PadesSignatureComponent },
            { path: 'pades-signature-server-key', component: PadesSignatureServerKeyComponent },
            { path: 'upload', component: UploadComponent },
            { path: 'xml-element-signature', component: XmlElementSignatureComponent },
            { path: 'xml-full-signature', component: XmlFullSignatureComponent },

            { path: '**', redirectTo: 'home' }
        ])
    ],
    entryComponents: [
        CertificateDialogComponent,
        MessageDialogComponent,
        SignatureResultsDialogComponent,
        ValidationResultsDialogComponent
    ],
    providers: [
        UtilService,
        ConfigService,
        {
            provide: APP_INITIALIZER,
            useFactory: (config: ConfigService) => (() => config.load()),
            deps: [ConfigService],
            multi: true
        }
    ],
    bootstrap: [AppComponent]
})
export class AppModule { }
