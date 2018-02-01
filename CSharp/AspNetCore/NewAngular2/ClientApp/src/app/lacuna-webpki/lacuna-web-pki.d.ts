export default LacunaWebPKI;

export declare class LacunaWebPKI {
    constructor(license?: string | Object);

    //-------------Usable properties
    readonly standardTrustArbitrators: {
        pkiBrazil: TrustArbitrator,
        pkiItaly: TrustArbitrator,
        windows: TrustArbitrator
    };

    readonly pkcs11Modules: {
        safeSign: Pkcs11Module,
        safeNet: Pkcs11Module
    }

    readonly filters: {
        isPkiBrazilPessoaFisica: Filter,
        hasPkiBrazilCpf: Filter,
        hasPkiBrazilCnpj: Filter,
        pkiBrazilCpfEquals(cpf: string): Filter,
        pkiBrazilCnpjEquals(cnpj: string): Filter,
        hasPkiItalyCodiceFiscale: Filter,
        pkiItalyCodiceFiscaleEquals(cf: string): Filter,
        isWithinValidity: Filter,
        all(filters: Filter[]): Filter,
        all(...filters: Filter[]): Filter,
        any(filters: Filter[]): Filter,
        any(...filters: Filter[]): Filter
    };

    readonly cadesAcceptablePolicies: {
        pkiBrazil: LacunaWebPKI.CadesPolicies[]
    }

    readonly xmlAcceptablePolicies: {
        pkiBrazil: LacunaWebPKI.XmlPolicies[]
    }


    //-------------Public functions
    init(args: {
        ready: () => any,
        license?: string | Object,
        requiredApiVersion?: LacunaWebPKI.ApiVersions,
        notInstalled?: (status: number, message: string) => any,
        defaultError?: ErrorCallback,
        defaultFail?: FailCallback,
        angularScope?: Object,
        ngZone?: Object,
        brand?: string,
        restPkiUrl?: string,
        error?: ErrorCallback,
        fail?: FailCallback
    } | (() => any)): Promise<Object>;

    getVersion(args?: {
        success?: SuccessCallback<string>,
        error?: ErrorCallback,
        fail?: FailCallback
    }): Promise<string>;

    listCertificates(args?: {
        filter?: Filter,
        selectId?: string,
        selectOptionFormatter?: (c: CertificateModel) => string,
        success?: SuccessCallback<CertificateModel[]>,
        error?: ErrorCallback,
        fail?: FailCallback
    }): Promise<CertificateModel[]>;

    readCertificate(args: {
        thumbprint: string,
        success?: SuccessCallback<string>,
        error?: ErrorCallback,
        fail?: FailCallback
    } | string): Promise<string>;

    signHash(args: {
        thumbprint: string,
        hash: string,
        digestAlgorithm: string,
        success?: SuccessCallback<string>,
        error?: ErrorCallback,
        fail?: FailCallback
    }): Promise<string>;

    signData(args: {
        thumbprint: string,
        data: string,
        digestAlgorithm: string,
        success?: SuccessCallback<string>,
        error?: ErrorCallback,
        fail?: FailCallback
    }): Promise<string>;

    signWithRestPki(args: {
        thumbprint: string,
        token: string,
        success?: SuccessCallback<string>,
        error?: ErrorCallback,
        fail?: FailCallback
    }): Promise<string>;

    preauthorizeSignatures(args: {
        certificateThumbprint: string,
        signatureCount: number,
        success?: SuccessCallback<void>,
        error?: ErrorCallback,
        fail?: FailCallback
    }): Promise<void>;

    showFolderBrowser(args?: {
        message?: string,
        success?: SuccessCallback<ShowFolderBrowserResponse>,
        error?: ErrorCallback,
        fail?: FailCallback
    } | string): Promise<ShowFolderBrowserResponse>;

    showFileBrowser(args?: {
        multiselect?: boolean,
        filters?: FileFilterModel[],
        dialogTitle?: string,
        success?: SuccessCallback<ShowFileBrowserResponse>,
        error?: ErrorCallback,
        fail?: FailCallback
    }): Promise<ShowFileBrowserResponse>;

    downloadToFolder(args?: {
        url: string,
        folderId: string,
        filename?: string,
        success?: SuccessCallback<DownloadToFolderResponse>,
        error?: ErrorCallback,
        fail?: FailCallback
    }): Promise<DownloadToFolderResponse>;

    openFolder(args: {
        folderId: string,
        success?: SuccessCallback<boolean>,
        error?: ErrorCallback,
        fail?: FailCallback
    } | string): Promise<boolean>;

    openFile(args: {
        fileId: string,
        success?: SuccessCallback<boolean>,
        error?: ErrorCallback,
        fail?: FailCallback
    } | string): Promise<boolean>;

    redirectToInstallPage(): string;

    //-------------Browser detection
    detectedBrowser: string

    //-------------Web PKI Pro functions
    signPdf(args: {
        fileId: string,
        certificateThumbprint: string,
        output: Output,
        trustArbitrators?: TrustArbitrator[],
        clearPolicyTrustArbitrators?: boolean,
        visualRepresentation?: VisualRepresentation,
        pdfMarks?: PdfMark[],
        bypassMarksIfSigned?: boolean,
        policy: LacunaWebPKI.PadesPolicies,
        success?: SuccessCallback<PdfSignResult>,
        error?: ErrorCallback,
        fail?: FailCallback
    }): Promise<PdfSignResult>;

    signCades(args: {
        fileId: string,
        certificateThumbprint: string,
        output: Output,
        trustArbitrators?: TrustArbitrator[],
        clearPolicyTrustArbitrators?: boolean,
        cmsToCosignFileId?: string,
        autoDetectCosign?: boolean,
        includeEncapsulatedContent?: boolean,
        policy: LacunaWebPKI.CadesPolicies,
        success?: SuccessCallback<CadesSignResult>,
        error?: ErrorCallback,
        fail?: FailCallback
    }): Promise<CadesSignResult>;

    signFullXml(args: {
        fileId: string,
        certificateThumbprint: string,
        output: Output,
        trustArbitrators?: TrustArbitrator[],
        clearPolicyTrustArbitrators?: boolean,
        content?: string,
        policy: LacunaWebPKI.XmlPolicies,
        signatureElementId?: string,
        signatureElementLocation?: XmlSignatureElementLocation,
        namespaces?: NamespaceModel[],
        success?: SuccessCallback<XmlSignResult>, 
        error?: ErrorCallback,
        fail?: FailCallback
    }): Promise<XmlSignResult>; 

    signXmlElement(args: {
        toSignElementId?: string,
        toSignElementsIds?: string[],
        toSignElementsXPath?: string,
        idResolutionTable?: XmlIdResolutionTableModel
        fileId: string,
        certificateThumbprint: string,
        output: Output,
        trustArbitrators?: TrustArbitrator[],
        clearPolicyTrustArbitrators?: boolean,
        content?: string,
        policy: LacunaWebPKI.XmlPolicies,
        signatureElementId?: string,
        signatureElementLocation?: XmlSignatureElementLocation,
        namespaces?: NamespaceModel[],
        success?: SuccessCallback<XmlSignResult>, 
        error?: ErrorCallback,
        fail?: FailCallback
    }): Promise<XmlSignResult>;

    openPades(args: {
        signatureFileId: string,
        validate: boolean,
        dateReference?: Date,
        trustArbitrators?: TrustArbitrator[],
        clearPolicyTrustArbitrators?: boolean,
        specificPolicy: LacunaWebPKI.PadesPolicies,
        success?: SuccessCallback<PadesSignatureModel>,
        error?: ErrorCallback,
        fail?: FailCallback
    }): Promise<PadesSignatureModel>;

    openCades(args: {
        signatureFileId: string,
        originalFileId: string,
        validate: boolean,
        dateReference?: Date,
        trustArbitrators?: TrustArbitrator[],
        clearPolicyTrustArbitrators?: boolean,
        specificPolicy?: LacunaWebPKI.CadesPolicies,
        acceptablePolicies?: LacunaWebPKI.CadesPolicies[],
        success?: SuccessCallback<CadesSignatureModel>,
        error?: ErrorCallback,
        fail?: FailCallback
    }): Promise<CadesSignatureModel>;

    openXades(args: {
        signatureFileId?: string,
        signatureContent?: string,
        validate: boolean,
        dateReference?: Date,
        idResolutionTable?: XmlIdResolutionTableModel,
        trustArbitrators?: TrustArbitrator[],
        clearPolicyTrustArbitrators?: boolean,
        specificPolicy?: LacunaWebPKI.XmlPolicies,
        acceptablePolicies?: LacunaWebPKI.XmlPolicies[],
        success?: SuccessCallback<XmlSignatureModel>,
        error?: ErrorCallback,
        fail?: FailCallback
    }): Promise<XmlSignatureModel>;

    listTokens(args: {
        pkcs11Modules?: Pkcs11Module[],
        success?: SuccessCallback<TokenModel[]>,
        error?: ErrorCallback,
        fail?: FailCallback
    }): Promise<TokenModel[]>;

    generateTokenRsaKeyPair(args: {
        pkcs11Modules?: Pkcs11Module[],
        subjectName?: string,
        tokenSerialNumber: string,
        keyLabel?: string,
        keySize: number,
        success?: SuccessCallback<GenerateTokenKeyPairResponse>,
        error?: ErrorCallback,
        fail?: FailCallback
    }): Promise<GenerateTokenKeyPairResponse>;

    generateSoftwareRsaKeyPair(args: {
        subjectName?: string,
        keySize: number,
        success?: SuccessCallback<GenerateKeyPairResponse>,
        error?: ErrorCallback,
        fail?: FailCallback
    }): Promise<GenerateKeyPairResponse>;

    importTokenCertificate(args: {
        pkcs11Modules?: Pkcs11Module[],
        tokenSerialNumber: string,
        certificateContent: string,
        certificateLabel?: string,
        success?: SuccessCallback<ImportTokenCertificateResponse>,
        error?: ErrorCallback,
        fail?: FailCallback
    }): Promise<ImportTokenCertificateResponse>;

    importCertificate(args: {
        certificateContent: string,
        passwordPolicies?: LacunaWebPKI.PasswordPolicies,
        passwordMinLength?: number
        savePkcs12: boolean,
        success?: SuccessCallback<ImportSoftwareCertificateResponse>,
        error?: ErrorCallback,
        fail?: FailCallback
    }): Promise<ImportSoftwareCertificateResponse>;

    sendAuthenticatedRequest(args: {
        certificateThumbprint: string,
        url: string,
        method: LacunaWebPKI.HttpMethods,
        headers?: Object,
        body?: string,
        timeout?: number,
        success?: SuccessCallback<HttpResponseModel>,
        error?: ErrorCallback,
        fail?: FailCallback
    }): Promise<HttpResponseModel>;

}

// USABLE ENUMS

export namespace LacunaWebPKI {

    //-------------Contants
    export const enum InstallationStates {
        INSTALLED = 0,
        NOT_INSTALLED = 1,
        OUTDATED = 2,
        BROWSER_NOT_SUPPORTED = 3
    }

    export const enum ApiVersions {
        V1_0 = '1.0',
        V1_1 = '1.1',
        V1_2 = '1.2',
        V1_3 = '1.3',
        V1_4 = '1.4'
    }

    export const enum HttpMethods {
        Get = 'get',
        Post = 'post'
    }

    //-------------Pki Options
    export const enum PadesPolicies {
        Basic = 'basic',
        BrazilAdrBasica = 'brazilAdrBasica'
    }

    export const enum CadesPolicies {
        Bes = 'cadesBes',
        BrazilAdrBasica = 'brazilAdrBasica'
    }

    export const enum XmlPolicies {
        XmlDSig = 'xmlDSig',
        XadesBes = 'xadesBes',
        BrazilNFe = 'brazilNFe',
        BrazilAdrBasica = 'brazilAdrBasica',
    }

    export const enum XmlSignedEntityTypes {
        FullXml = 'fullXml',        
        XmlElement = 'xmlElement',
        DetachedResource = 'detachedResource'
    }

    export const enum OutputModes {
        ShowSaveFileDialog = 'showSaveFileDialog',
        SaveInFolder = 'saveInFolder',
        AutoSave = 'autoSave',
        ReturnContent = 'returnContent'
    }

    export const enum TrustArbitratorTypes {
        TrustedRoot = 'trustedRoot',
        Tsl = 'tsl',
        Standard = 'standard'
    }

    export const enum StandardArbitrators {
        PkiBrazil = 'pkiBrazil',
        PkiItaly = 'pkiItaly',
        Windows = 'windows'
    }

    export const enum CertificateTypes {
        A1 = 'A1',
        A2 = 'A2',
        A3 = 'A3',
        A4 = 'A4',
        S1 = 'S1',
        S2 = 'S2',
        S3 = 'S3',
        S4 = 'S4',
        T3 = 'T3',
        T4 = 'T4',
        Unknown = 'Unknown'
    }

    export const enum XmlInsertionOptions{
        AppendChild = 'appendChild',
        PrependChild = 'prependChild',
        AppendSibling = 'appendSibling',
        PrependSibling = 'prependSibling'
    }

    export const enum CmsContentTypes {
        Data              = 'Data',
        SignedData        = 'SignedData',
        EnvelopedData     = 'EnvelopedData',
        DigestedData      = 'DigestedData',
        EncryptedData     = 'EncryptedData',
        AuthenticatedData = 'AuthenticatedData',
        TstInfo           = 'TstInfo',
    }

    // visual representation
    export const enum PadesPaperSizes {
        Custom = 'custom',
        A0 = 'a0',
        A1 = 'a1',
        A2 = 'a2',
        A3 = 'a3',
        A4 = 'a4',
        A5 = 'a5',
        A6 = 'a6',
        A7 = 'a7',
        A8 = 'a8',
        Letter = 'letter',
        Legal = 'legal',
        Ledger = 'ledger'
    }

    export const enum PadesHorizontalAlign {
        Left = 'left',
        Center = 'center',
        Rigth = 'rigth'
    }

    export const enum PadesVerticalAlign {
        Top = 'top',
        Center = 'center',
        Bottom = 'bottom'
    }

    export const enum PadesMeasurementUnits {
        Centimeters = 'centimeters',
        PdfPoints = 'pdfPoints'
    }

    export const enum PadesPageOrientations {
        Auto = 'auto',
        Portrait = 'portrait',
        Landscape = 'landscape'
    }

    // pdf mark
    export const enum PdfElementTypes {
        Text = 'text',
        Image = 'image'
    }

    export const enum PdfTextStyles {
        Normal = 'normal',
        Bold = 'bold',
        Italic = 'italic'
    }

    // password policies
    export const enum PasswordPolicies {
        LettersAndNumbers = 1,
        UpperAndLowerCase = 2,
        SpecialCharacters = 4
    }

    // WebPKI errors
    export const enum ErrorCodes {
        UNDEFINED                      = 'undefined',
        INTERNAL                       = 'internal',
        USER_CANCELLED                 = 'user_cancelled',
        OS_NOT_SUPPORTED               = 'os_not_supported',
        ADDON_TIMEOUT                  = 'addon_timeout',
        ADDON_NOT_DETECTED             = 'addon_not_detected',
        ADDON_SEND_COMMAND_FAILURE     = 'addon_send_command_failure',
        CERTIFICATE_NOT_FOUND          = 'certificate_not_found',
        COMMAND_UNKNOWN                = 'command_unknown',
        COMMAND_NOT_SUPPORTED          = 'command_not_supported',
        COMMAND_PARAMETER_NOT_SET      = 'command_parameter_not_set',
        COMMAND_INVALID_PARAMETER      = 'command_invalid_parameter',
        NATIVE_CONNECT_FAILURE         = 'native_connect_failure',
        NATIVE_DISCONNECTED            = 'native_disconnected',
        NATIVE_NO_RESPONSE             = 'native_no_response',
        REST_PKI_GET_PENDING_SIGNATURE = 'rest_pki_get_pending_signature',
        REST_PKI_POST_SIGNATURE        = 'rest_pki_post_signature',
        REST_PKI_INVALID_CERTIFICATE   = 'rest_pki_invalid_certificate',
        LICENSE_NOT_SET                = 'license_not_set',
        LICENSE_INVALID                = 'license_invalid',
        LICENSE_RESTRICTED             = 'license_restricted',
        LICENSE_EXPIRED                = 'license_expired',
        LICENSE_DOMAIN_NOT_ALLOWED     = 'license_domain_not_allowed',
        VALIDATION_ERROR               = 'validation_error',
        P11_ERROR                      = 'p11_error',
        P11_TOKEN_NOT_FOUND            = 'p11_token_not_found',
        P11_NOT_SUPPORTED              = 'p11_not_supported',
        KEYSET_NOT_FOUND               = 'keyset_not_found',
        ALGORITHM_NOT_SUPPORTED        = 'algorithm_not_supported',
        SIGNED_PDF_TO_MARK             = 'signed_pdf_to_mark',
        JSON_ERROR                     = 'json_error',
        IO_ERROR                       = 'io_error',
        KEYCHAIN_ERROR                 = 'keychain_error',
        KEYCHAIN_SIGN_ERROR            = 'keychain_sign_error',
        DECODE_ERROR                   = 'decode_error',
        CSP_KEYSET_NOT_DEFINED         = 'csp_keyset_not_defined',
        CSP_INVALID_ALGORITHM          = 'csp_invalid_algorithm',
        CSP_INVALID_PROVIDER_TYPE      = 'csp_invalid_provider_type'
    }

}

// TYPES

export interface Promise<T> {
    success(callback: SuccessCallback<T>): Promise<T>;
    error(callback: ErrorCallback): Promise<T>;
    fail(callback: FailCallback): Promise<T>;
}

export interface ExceptionModel {
    message: string,
    error: string,
    origin: string,
    code: LacunaWebPKI.ErrorCodes
}

export interface Output {
    mode: LacunaWebPKI.OutputModes,
    folderId?: string,
    dialogTitle?: string,
    fileNameSuffix?: string
}

export interface TrustArbitrator {
    type: LacunaWebPKI.TrustArbitratorTypes,
    standardArbitrator?: LacunaWebPKI.StandardArbitrators,
    trustedRoot?: string,
    tslUrl?: string,
    tslRoot?: string
}

export interface Pkcs11Module {
    win: string,
    linux: string,
    mac: string
}

export interface CertificateModel {
    subjectName: string,
    issuerName: string,
    email: string,
    thumbprint: string,
    keyUsage: KeyUsagesModel,
    pkiBrazil: PkiBrazilModel,
    pkiItaly: PkiItalyModel,
    validityStart: Date,
    validityEnd: Date
}

export interface DigestModel {
    digestAlgorithmOid: string,
    digestAlgorithmName: string,
    digestValue: string
}

export interface FileModel {
    id: string,
    name: string,
    length: number
}

export interface FileFilterModel {
    description: string,
    extension: string
}

export interface KeyUsagesModel {
    crlSign: boolean,
    dataEncipherment: boolean,
    decipherOnly: boolean,
    digitalSignature: boolean,
    encipherOnly: boolean,
    keyAgreement: boolean,
    keyCertSign: boolean,
    keyEncipherment: boolean,
    nonRepudiation: boolean
}

export interface PkiBrazilModel {
    cpf: string,
    cnpj: string,
    responsavel: string,
    dateOfBirth: Date,
    certificateType: LacunaWebPKI.CertificateTypes,
    isAplicacao: boolean,
    isPessoaFisica: boolean,
    isPessoaJuridica: boolean,
    companyName: string,
    nis: string,
    rgNumero: string,
    rgEmissor: string,
    rgEmissorUF: string,
    oabNumero: string,
    oabUF: string
}

export interface PkiItalyModel {
    codiceFiscale: string
}

export interface SignatureInfo {
    signerCertificate: CertificateModel,
    messageDigest?: DigestModel,
    file?: FileModel,
    signingTime?: Date,
    content?: string
}

export interface ValidationResults {
    passedChecks: ValidationItem[],
    warnings: ValidationItem[],
    errors: ValidationItem[]
}

export interface ValidationItem {
    type: string,
    message: string,
    detail: string,
    innerValidationResults: ValidationResults
}

export interface SignResult {
    isValid: boolean,
    signatureInfo: SignatureInfo,
    signingCertificateValidationResults?: ValidationResults
}

export interface PdfSignResult extends SignResult {
    pagesCount: number
}

export interface CadesSignResult extends SignResult {
    // For now, CadesSignResult has only the same properties as BaseSignResult
}

export interface XmlSignResult extends SignResult {
    // For now, XmlSignResult has only the same properties as BaseSignResult
}

export interface SignatureAlgorithmModel {
    signatureValue: string,
    signatureAlgorithmOid: string,
    signatureAlgorithmName: string, 
    digestAlgorithmOid: string, 
    digestAlgorithmName: string 
}

export interface SignaturePolicyIdentifierModel {
    digest: DigestModel,
    oid: string,
    uri: string
} 

export interface SignerModel {
    signature: SignatureAlgorithmModel,
    signaturePolicy?: SignaturePolicyIdentifierModel,
    certificate: CertificateModel,
    signingTime?: Date
    certifiedDateReference?: Date 
    timestamps?: CadesTimestampModel[],
    isValid?: boolean,
    validationResults?: ValidationResults
}

export interface CadesSignerModel extends SignerModel {
    messageDigest: DigestModel,
}

export interface PadesSignerModel extends SignerModel {
    messageDigest: DigestModel,
    isDocumentTimestamp: boolean,
    signatureFieldName: string
}

export interface XmlSignerModel extends SignerModel {
    signatureElementId : string,
    type : LacunaWebPKI.XmlSignedEntityTypes,
    signedElement : XmlElementModel 
}

export interface CadesSignatureModel {
    encapsulatedContentType: LacunaWebPKI.CmsContentTypes,
    hasEncapsulatedContent: boolean,
    signers: CadesSignerModel[]
}

export interface PadesSignatureModel {
    signers: PadesSignerModel[]
}

export interface XmlSignatureModel {
    signers: XmlSignerModel[]
}

export interface CadesTimestampModel extends CadesSignatureModel {
    genTime: Date,
    serialNumber: string,
    messageImprint: DigestModel
}

export interface XmlElementModel {
    localName: string,
    attributes: XmlAttributeModel[],
    namespaceUri: string 
}

export interface XmlAttributeModel {
    localName: string,
    value: string,
    namespaceUri: string 
}

export interface XmlIdResolutionTableModel {
    includeXmlIdAttribute: boolean,
    elementIdAttributes: XmlIdAttributeModel[],
    globalIdAttributes: XmlNodeNameModel[]
}

export interface XmlIdAttributeModel {
    element: XmlNodeNameModel,
    attribute: XmlNodeNameModel
}

export interface XmlNodeNameModel {
    localName: string,
    namespace: string
}

export interface XmlSignatureElementLocation {
    xpath: string, 
    insertionOption: LacunaWebPKI.XmlInsertionOptions
}

export interface NamespaceModel {
    prefix: string,
    uri: string
}

export interface MechanismsModel {
    rsaGenerateKeyPair: boolean,
    rsaMinKeySize: number,
    rsaMaxKeySize: number
}

export interface TokenModel {
    slotDescription: string,
    slotManufacturer: string,
    label: string,
    manufacturer: string,
    model: string,
    serialNumber: string,
    initialized: boolean,
    mechanisms: MechanismsModel
}

export interface HttpResponseModel {
    content: string,
    headers: Object,
    statusCode: number,
    status: string
}

export interface GenerateKeyPairResponse {
    csr: string
}

export interface GenerateTokenKeyPairResponse extends GenerateKeyPairResponse {
}

export interface ImportCertificateResponse {
    imported: boolean
}

export interface ImportSoftwareCertificateResponse  extends ImportCertificateResponse {
    pkcs12Saved: boolean
}

export interface ImportTokenCertificateResponse extends ImportCertificateResponse {
    // For now, ImportTokenCertificateResponse has only the same properties as ImportCertificateResponse
}

export interface DownloadToFolderResponse {
    filename: string,
    length: number
}

export interface ShowFileBrowserResponse {
    userCancelled: boolean,
    files: FileModel[]
}

export interface ShowFolderBrowserResponse {
    userCancelled: boolean,
    folderId: string
}

// Visual Representation Types

export interface VisualRepresentation {
    text?: PadesVisualText,
    image?: PadesVisualImage,
    position?: PadesVisualPositioning
}

export interface PadesVisualText {
    text?: string,
    includeSigningTime?: boolean,
    container?: PadesVisualRectangle,
    horizontalAlign?: LacunaWebPKI.PadesHorizontalAlign, 
    fontSize?: number
}

export interface PadesVisualImage {
    resource: ResourceContentOrReference,
    opacity?: number,
    horizontalAlign?: LacunaWebPKI.PadesHorizontalAlign,
    verticalAlign?: LacunaWebPKI.PadesVerticalAlign
}

export interface PadesVisualPositioning {
    pageNumber: number,
    measurementUnits: LacunaWebPKI.PadesMeasurementUnits,
    pageOptimization?: PadesPageOptimization,
    auto?: PadesVisualAutoPositioning,
    manual?: PadesVisualRectangle
}

export interface ResourceContentOrReference {
    url?: string,
    content?: string,
    mimeType?: string
}

export interface PadesVisualRectangle {
    bottom?: number,
    top?: number,
    left?: number,
    right?: number,
    width?: number,
    height?: number
}

export interface PadesVisualAutoPositioning {
    container: PadesVisualRectangle,
    signatureRectangleSize: PadesSize,
    rowSpacing: number
}

export interface PadesSize {
    width: number,
    height: number
}

export interface PdfMark {
    measurementUnits: LacunaWebPKI.PadesMeasurementUnits, 
    container: PadesVisualRectangle,
    borderWidth?: number,
    backgroundColor?: string,
    borderColor?: string,
    pageOptimization: PadesPageOptimization,
    elements: PdfMarkElement[]
}

export interface PdfMarkElement {
    elementType: LacunaWebPKI.PdfElementTypes, 
    relativeContainer: PadesVisualRectangle,
    rotation?: number,
    textSections?: PdfTextSection,
    image?: PdfMarkImage
}

export interface PdfTextSection {
    style: LacunaWebPKI.PdfTextStyles,
    text: string,
    color?: string,
    fontSize?: number
}

export interface PdfMarkImage {
    opacity?: number,
    resource: ResourceContentOrReference
}

export interface PadesPageOptimization {
    paperSize: LacunaWebPKI.PadesPaperSizes,
    customPaperSize: PadesSize,
    pageOrientation: LacunaWebPKI.PadesPageOrientations
}

// Common Functions

export interface Filter {
    (cert: CertificateModel) : boolean;
}

export interface SuccessCallback<T> {
    (arg: T) : void;
}

export interface ErrorCallback {
    (message: string, error: string, origin: string, code: string) : void;
}

export interface FailCallback {
    (ex: ExceptionModel) : void;
}