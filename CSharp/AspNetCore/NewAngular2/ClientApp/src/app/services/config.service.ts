import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';

@Injectable()
export class ConfigService {

    constructor(private http: HttpClient) { }

    private accessToken: string;
    private restPkiEndpoint: string;
    private webPkiLicense: string;

    public getAccessToken() {
        return this.accessToken;
    }

    public getRestPkiEndpoint() {
        return this.restPkiEndpoint;
    }

    public getWebPkiLicense() {
        return this.webPkiLicense;
    }

    public load() {
        return new Promise((resolve, reject) => {
            this.http.get<ConfigFile>('assets/restpki.conf.json').subscribe(result => {
                this.accessToken = result.accessToken;
                this.restPkiEndpoint = result.restPkiEndpoint;
                this.webPkiLicense = result.webPkiLicense;
                resolve(true);
            }, error => console.error(error));
        });           
    }
}

interface ConfigFile {
    accessToken: string;
    restPkiEndpoint: string;
    webPkiLicense: string;
}