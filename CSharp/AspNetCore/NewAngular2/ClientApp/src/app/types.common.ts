export interface RestPkiCertModel {
    breadcrumbIndex?: number,
    subjectName: NameModel,
    emailAddress?: string,
    issuerName: NameModel,
    serialNumber: string,
    validityStart: Date,
    validityEnd: Date,
    issuer?: RestPkiCertModel,
    pkiBrazil?: PkiBrazilCertModel
}

export interface NameModel {
    string?: string,
    country: string,
    organization: string,
    organizationUnit: string,
    DNQualifier?: string,
    stateName?: string,
    commonName: string,
    serialNumber?: string,
    locality?: string,
    title?: string,
    surname?: string,
    givenName?: string,
    initials?: string,
    pseudonym?: string,
    generationQualifier?: string,
    emailAddress: string
}

export interface PkiBrazilCertModel {
    certificateType: string,
    cpf: string,
    cnpj: string,
    responsavel: string,
    dateOfBirth: string,
    companyName: string,
    oabUF: string,
    oabNumero: string,
    rgEmissorUF: string,
    rgNumero: string,
}

export interface RestPkiValidationItem {
    type: string,
    message: string,
    detail: string,
    innerValidationResults: RestPkiValidationResults,
    breadcrumbIndex?: number
}

export interface RestPkiValidationResults {
    passedChecks: RestPkiValidationItem[],
    warnings: RestPkiValidationItem[],
    errors: RestPkiValidationItem[]
}