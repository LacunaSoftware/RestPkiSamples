// -------------------- Add-on placeholder (IE only) --------------------
if (typeof window.lacunaWebPKIExtension === 'undefined') {
	window.lacunaWebPKIExtension = null;
}



// -------------------- Class declaration --------------------

LacunaWebPKI = function (license) {
	this.license = null;
	this.defaultFailCallback = null;
	this.angularScope = null;
	this.ngZone = null;
	this.brand = null;
	this.restPkiUrl = null;
	if (license) {
		this.license = license;
	}
};

// Inject class prototype

(function ($) {

    // -------------------- Promise subclass --------------------

    $.Promise = function (angularScope, ngZone) {
        this.successCallback = function() { };
        this.failCallback = null;
        this.angularScope = angularScope;
        this.ngZone = ngZone;
    };

	$.Promise.prototype.success = function (callback) {
        this.successCallback = callback;
        return this;
    };

    $.Promise.prototype.error = function (callback) {
        // for backward compatibility, any legacy error callback converted to a fail callback
        this.failCallback = function(ex) {
            callback(ex.message, ex.error, ex.origin, ex.code);
        };
        return this;
    };

    $.Promise.prototype.fail = function (callback) {
        this.failCallback = callback;
        return this;
    };

    $.Promise.prototype._invokeSuccess = function (result, delay) {
        if (delay > 0) {
            var self = this;
            setTimeout(function () {
                self._invokeSuccess(result);
            }, delay);
        } else {
            var callback = this.successCallback || function () { $._log('Success ignored (no callback registered)'); };
            this._apply(function () {
                callback(result);
            });
        }
    };

    $.Promise.prototype._invokeError = function (ex, delay) {
        if (delay > 0) {
            var self = this;
            setTimeout(function () {
                self._invokeError(ex);
            }, delay);
        } else {
            var callback = this.failCallback || function (ex) {
                throw 'Web PKI error originated at ' + ex.origin + ': ' + ex.message + '\n' + ex.complete + '\ncode: ' + ex.code;
            };
            this._apply(function () {
            	callback({
            		userMessage: ex.userMessage || ex.message,
            		message: ex.message,
            		error: ex.complete,
            		origin: ex.origin,
            		code: ex.code
            	});
            });
        }
    };

    // https://coderwall.com/p/ngisma/safe-apply-in-angular-js
    $.Promise.prototype._apply = function (callback) {
        if (this.angularScope) {
            var phase = this.angularScope.$root.$$phase;
            if (phase == '$apply' || phase == '$digest') {
                callback();
            } else {
                this.angularScope.$apply(function () {
                    callback();
                });
            }
        } else if (this.ngZone) {
        	this.ngZone.run(function () {
        		callback();
        	});
        } else {
            callback();
        }
    };


    // -------------------- Constants --------------------

	$._installUrl = 'https://get.webpkiplugin.com/';
	$._chromeExtensionId = 'dcngeagmmhegagicpcmpinaoklddcgon';
	$._firefoxExtensionId = 'webpki@lacunasoftware.com';
	$._chromeExtensionFirstVersionWithSelfUpdate = '2.0.20';
    
    // latest components version ----------------------
	$._extensionRequiredVersion = '2.11.7';
	$._chromeNativeWinRequiredVersion = '2.6.2';
	$._chromeNativeLinuxRequiredVersion = '2.7.4';
	$._chromeNativeMacRequiredVersion = '2.7.4';
	$._ieAddonRequiredVersion = '2.4.2';
    // ------------------------------------------------

    $._chromeInstallationStates = {
        INSTALLED: 0,
        EXTENSION_NOT_INSTALLED: 1,
        EXTENSION_OUTDATED: 2,
        NATIVE_NOT_INSTALLED: 3,
        NATIVE_OUTDATED: 4
    };

    $._certKeyUsages = {
        crlSign: 2,
        dataEncipherment: 16,
        decipherOnly: 32768,
        digitalSignature: 128,
        encipherOnly: 1,
        keyAgreement: 8,
        keyCertSign: 4,
        keyEncipherment: 32,
        nonRepudiation: 64
    };

    $.apiVersions = {
        v1_0: '1.0',
        v1_1: '1.1',
        v1_2: '1.2',
        v1_3: '1.3',
		v1_4: '1.4',
        latest: 'latest'
    };

    $._apiMap = {
        nativeWin: {}, 
        nativeLinux: {},
        nativeMac: {},
        ieAddon: {},
        extension: {}
    };
    // syntax: api_version: supported_since_version
    // Windows
    $._apiMap.nativeWin[$.apiVersions.v1_0] = '2.1.0';
    $._apiMap.nativeWin[$.apiVersions.v1_1] = '2.3.0';
    $._apiMap.nativeWin[$.apiVersions.v1_2] = '2.4.1';
    $._apiMap.nativeWin[$.apiVersions.v1_3] = '2.5.0';
    $._apiMap.nativeWin[$.apiVersions.v1_4] = '2.6.2';

    // IE
    $._apiMap.ieAddon[$.apiVersions.v1_0] = '2.0.4';
    $._apiMap.ieAddon[$.apiVersions.v1_1] = '2.1.1';
    $._apiMap.ieAddon[$.apiVersions.v1_2] = '2.2.4';
    $._apiMap.ieAddon[$.apiVersions.v1_3] = '2.3.0';
    $._apiMap.ieAddon[$.apiVersions.v1_4] = '2.4.2';

    // Linux
    $._apiMap.nativeLinux[$.apiVersions.v1_0] = '2.0.0';
    $._apiMap.nativeLinux[$.apiVersions.v1_1] = '2.4.0';
    $._apiMap.nativeLinux[$.apiVersions.v1_2] = '2.6.2';
    $._apiMap.nativeLinux[$.apiVersions.v1_3] = '2.7.0';
    $._apiMap.nativeLinux[$.apiVersions.v1_4] = '2.7.4';

    // Mac
    $._apiMap.nativeMac[$.apiVersions.v1_0] = '2.3.0';
    $._apiMap.nativeMac[$.apiVersions.v1_1] = '2.4.0';
    $._apiMap.nativeMac[$.apiVersions.v1_2] = '2.6.1';
    $._apiMap.nativeMac[$.apiVersions.v1_3] = '2.7.0';
    $._apiMap.nativeMac[$.apiVersions.v1_4] = '2.7.4';

    // WebExtension
    $._apiMap.extension[$.apiVersions.v1_0] = '2.3.2';
    $._apiMap.extension[$.apiVersions.v1_1] = '2.7.0';
    $._apiMap.extension[$.apiVersions.v1_2] = '2.9.1';
    $._apiMap.extension[$.apiVersions.v1_3] = '2.10.1';
    $._apiMap.extension[$.apiVersions.v1_4] = '2.11.7';

    // All latest
    $._apiMap.nativeWin  [$.apiVersions.latest] = $._chromeNativeWinRequiredVersion;
    $._apiMap.ieAddon    [$.apiVersions.latest] = $._ieAddonRequiredVersion;
    $._apiMap.nativeLinux[$.apiVersions.latest] = $._chromeNativeLinuxRequiredVersion;
    $._apiMap.nativeMac  [$.apiVersions.latest] = $._chromeNativeMacRequiredVersion;
    $._apiMap.extension  [$.apiVersions.latest] = $._extensionRequiredVersion;

	// populated after init
    $._nativeInfo = {};

    $.installationStates = {
        INSTALLED: 0,
        NOT_INSTALLED: 1,
        OUTDATED: 2,
        BROWSER_NOT_SUPPORTED: 3
    };

    // Pki Options ----------------------

    $.padesPolicies = {
        basic: 'basic',
        brazilAdrBasica: 'brazilAdrBasica'
    };

    $.cadesPolicies = {
        bes: 'cadesBes',
        brazilAdrBasica: 'brazilAdrBasica'
    };

    $.xmlPolicies = {
    	xmlDSig: 'xmlDSig',
    	
    	xadesBes: 'xadesBes',
    	brazilNFe: 'brazilNFe',
    	brazilAdrBasica: 'brazilAdrBasica'
    };

    $.cadesAcceptablePolicies = {
        pkiBrazil: [
            'brazilAdrBasica',
            'brazilAdrTempo',
            'brazilAdrValidacao',
            'brazilAdrCompleta',
            'brazilAdrArquivamento'
        ]
    };

    $.xmlAcceptablePolicies = {
    	pkiBrazil: [
            'brazilAdrBasica',
            'brazilAdrTempo'
    	]
    };

	$.standardTrustArbitrators = {
	    pkiBrazil: {
	        type: 'standard',
	        standardArbitrator: 'pkiBrazil'
	    },
	    pkiItaly: {
	        type: 'standard',
	        standardArbitrator: 'pkiItaly'
	    },
	    pkiPeru: {
	        type: 'standard',
	        standardArbitrator: 'pkiPeru'
	    },
	    windows: {
	        type: 'standard',
	        standardArbitrator: 'windows'
	    }
	};

	$.xmlInsertionOptions = {
		appendChild: 'appendChild',
		prependChild: 'prependChild',
		appendSibling: 'appendSibling',
		prependSibling: 'prependSibling'
	};

	$.outputModes = {
	    showSaveFileDialog: 'showSaveFileDialog',
	    saveInFolder: 'saveInFolder',
	    autoSave: 'autoSave',
	    returnContent: 'returnContent'
	};

	$.trustArbitratorTypes = {
	    trustedRoot: 'trustedRoot',
	    tsl: 'tsl',
	    standard: 'standard'
	};

    // visual representation
	$.padesPaperSizes = {
	    custom: 'custom',
	    a0: 'a0',
	    a1: 'a1',
	    a2: 'a2',
	    a3: 'a3',
	    a4: 'a4',
	    a5: 'a5',
	    a6: 'a6',
	    a7: 'a7',
	    a8: 'a8',
	    letter: 'letter',
	    legal: 'legal',
	    ledger: 'ledger'
	};

	$.padesHorizontalAlign = {
	    left: 'left',
	    center: 'center',
	    rigth: 'rigth'
	};

	$.padesVerticalAlign = {
	    top: 'top',
	    center: 'center',
	    bottom: 'bottom'
	};

	$.padesMeasurementUnits = {
	    centimeters: 'centimeters',
	    pdfPoints: 'pdfPoints'
	};

	$.padesPageOrientations = {
	    auto: 'auto',
	    portrait: 'portrait',
        landscape: 'landscape'
	};

    // pdf mark
	$.markElementTypes = {
	    text: 'text',
	    image: 'image'
	};

	$.markTextStyle = {
	    normal: 0,
	    bold: 1,
	    italic: 2
	};

	// password policies
	$.passwordPolicies = {
		lettersAndNumbers: 1,
		upperAndLowerCase: 2,
		specialCharacters: 4
	};

	// standard pkcs11 modules
	$.pkcs11Modules = {
		safeSign: { win: 'aetpkss1.dll', linux: 'libaetpkss.so.3', mac: 'libaetpkss.dylib' },
		safeNet: { win: 'eTPKCS11.dll', linux: 'libeToken.so', mac: 'libeToken.dylib' }
	};

    // WebPKI errors
	$.errorCodes = {
		UNDEFINED:                      'undefined',
	    INTERNAL:                       'internal',
	    USER_CANCELLED:                 'user_cancelled',
	    OS_NOT_SUPPORTED:               'os_not_supported',
	    ADDON_TIMEOUT:                  'addon_timeout',
	    ADDON_NOT_DETECTED:             'addon_not_detected',
	    ADDON_SEND_COMMAND_FAILURE:     'addon_send_command_failure',
	    CERTIFICATE_NOT_FOUND:          'certificate_not_found',
	    COMMAND_UNKNOWN:                'command_unknown',
	    COMMAND_NOT_SUPPORTED:          'command_not_supported',
	    COMMAND_PARAMETER_NOT_SET:      'command_parameter_not_set',
	    COMMAND_INVALID_PARAMETER:      'command_invalid_parameter',
	    NATIVE_CONNECT_FAILURE:         'native_connect_failure',
	    NATIVE_DISCONNECTED:            'native_disconnected',
	    NATIVE_NO_RESPONSE:             'native_no_response',
	    REST_PKI_GET_PENDING_SIGNATURE: 'rest_pki_get_pending_signature',
	    REST_PKI_POST_SIGNATURE:        'rest_pki_post_signature',
	    REST_PKI_INVALID_CERTIFICATE:   'rest_pki_invalid_certificate',
	    LICENSE_NOT_SET:                'license_not_set',
	    LICENSE_INVALID:                'license_invalid',
	    LICENSE_RESTRICTED:             'license_restricted',
	    LICENSE_EXPIRED:                'license_expired',
	    LICENSE_DOMAIN_NOT_ALLOWED:     'license_domain_not_allowed',
	    VALIDATION_ERROR:               'validation_error',
	    P11_ERROR:                      'p11_error',
	    P11_TOKEN_NOT_FOUND:            'p11_token_not_found',
	    P11_NOT_SUPPORTED:              'p11_not_supported',
	    KEYSET_NOT_FOUND:               'keyset_not_found',
	    ALGORITHM_NOT_SUPPORTED:        'algorithm_not_supported',
	    SIGNED_PDF_TO_MARK:             'signed_pdf_to_mark',
	    JSON_ERROR:                     'json_error',
	    IO_ERROR:                       'io_error',
	    KEYCHAIN_ERROR:                 'keychain_error',
	    KEYCHAIN_SIGN_ERROR:            'keychain_sign_error',
	    DECODE_ERROR:                   'decode_error',
	    CSP_KEYSET_NOT_DEFINED:         'csp_keyset_not_defined',
	    CSP_INVALID_ALGORITHM:          'csp_invalid_algorithm',
	    CSP_INVALID_PROVIDER_TYPE:      'csp_invalid_provider_type'
	    
	};

	// -------------------- "Private" static functions (no reference to 'this') --------------------

	$._compareVersions = function (v1, v2) {

		var v1parts = v1.split('.');
		var v2parts = v2.split('.');

		function isPositiveInteger(x) {
			return /^\d+$/.test(x);
		}

		function validateParts(parts) {
			for (var i = 0; i < parts.length; ++i) {
				if (!isPositiveInteger(parts[i])) {
					return false;
				}
			}
			return true;
		}

		if (!validateParts(v1parts) || !validateParts(v2parts)) {
			return NaN;
		}

		for (var i = 0; i < v1parts.length; ++i) {

			if (v2parts.length === i) {
				return 1;
			}

			var v1p = parseInt(v1parts[i], 10);
			var v2p = parseInt(v2parts[i], 10);

			if (v1p === v2p) {
				continue;
			}
			if (v1p > v2p) {
				return 1;
			}
			return -1;
		}

		if (v1parts.length != v2parts.length) {
			return -1;
		}

		return 0;
	};

	$._log = function (message) {
		if (window.console) {
			window.console.log(message);
		}
	};

	$._parseDataUrl = function (url) {
	    var match = /^data:(.+);base64,(.+)$/.exec(url);
	    if (!match) {
	        $._log('failed to parse data url');
	        return null;
	    }
	    return {
	        mimeType: match[1],
	        content: match[2]
	    };
	};

	$._downloadResource = function (url, callBack) {
	    $._log('resolving resource reference: ' + url);
	    var xhr = new XMLHttpRequest();
	    xhr.open('GET', url);
	    xhr.responseType = 'blob';
	    xhr.onload = function() {
	        var responseReader  = new FileReader();
	        responseReader.onloadend = function () {
	            $._log('resource reference resolved');
	            var resource = $._parseDataUrl(responseReader.result);
	            callBack(resource);
	        };
	        responseReader.readAsDataURL(xhr.response);
	    };
	    xhr.send();
	};

	$._getRequestOsP11Modules = function (p11Modules) {
		if (!p11Modules || !p11Modules.length) {
			return null;
		}
		osModules = [];
		for (var i=0; i<p11Modules.length; i++) {
			if ($._nativeInfo.os === 'Windows') {
				osModules.push(p11Modules[i].win);
			} else if ($._nativeInfo.os === 'Linux') {
				osModules.push(p11Modules[i].linux);
			} else if ($._nativeInfo.os === 'Darwin') {
				osModules.push(p11Modules[i].mac);
			}
		}
		return osModules;
	};

	// -------------------- "Private" instance functions (with references to 'this') --------------------

	$._createContext = function (args) {
		var promise = new $.Promise(this.angularScope, this.ngZone);
		if (args && args.success) {
			promise.success(args.success);
		}
		if (args && args.fail) {
		    promise.fail(args.fail);
		} else if (args && args.error) {
		    promise.error(args.error);
		} else {
		    promise.fail(this.defaultFailCallback);
		}
		var context = {
			promise: promise,
			license: this.license
		};
		return context;
	};

	// -------------------- Public functions --------------------

	$.init = function (args) {

		if (!args) {
			args = {};
		} else if (typeof args === 'function') {
			args = {
				ready: args
			};
		}

		if (args.license) {
			this.license = args.license;
		}
		if (args.defaultError) {
		    this.defaultFailCallback = function (ex) { args.defaultError(ex.message, ex.error, ex.origin, ex.code); };
		}
		if (args.defaultFail) {
            // overwrite any legacy error callback
		    this.defaultFailCallback = args.defaultFail;
		}		
		if (args.angularScope) {
			this.angularScope = args.angularScope;
		}
		if (args.ngZone) {
			this.ngZone = args.ngZone;
		}
		if (args.brand) {
			this.brand = args.brand;
		}
		if (args.restPkiUrl) {
		    this.restPkiUrl = args.restPkiUrl;
		}

		var self = this;
		var onCheckInstalledSuccess = function (result) {
		    if (result.isInstalled) {
				if (args.ready) {
					args.ready();
				} else {
					$._log('Web PKI ready (no callback registered)');
				}
			} else {
				if (args.notInstalled) {
					args.notInstalled(result.status, result.message, result.browserSpecificStatus);
				} else {
					self.redirectToInstallPage();
				}
			}
		};

		var context = this._createContext({
			success: onCheckInstalledSuccess,
			fail: args.fail,
            error: args.error
		});
		$._requestHandler.checkInstalled(context, args.requiredApiVersion);
		return context.promise;
	};

	$.getVersion = function (args) {
		var context = this._createContext(args);
		$._requestHandler.sendCommand(context, 'getVersion', null);
		return context.promise;
	};

	$.listCertificates = function (args) {

		if (!args) {
			args = {};
		} else if (args.filter) {
			if (typeof args.filter !== 'function') {
				if (typeof args.filter === 'boolean') {
					throw 'args.filter must be a function (hint: if you used "pki.filters.xxx()", try removing the "()")';
				} else {
					throw 'args.filter must be a function, received ' + (typeof args.filter);
				}
			}
		}

		var context = this._createContext(args);
		$._requestHandler.sendCommand(context, 'listCertificates', null, function (result) {
			return $._processCertificates(result, args.filter, args.selectId, args.selectOptionFormatter);
		});

		return context.promise;
	};

	$._processCertificates = function (result, filter, selectId, selectOptionFormatter) {
		var toReturn = [];
		for (var i = 0; i < result.length; i++) {
			var cert = result[i];
			cert.validityStart = new Date(cert.validityStart);
			cert.validityEnd = new Date(cert.validityEnd);
			cert.keyUsage = $._processKeyUsage(cert.keyUsage);
			if (cert.pkiBrazil && cert.pkiBrazil.dateOfBirth) {
				var s = cert.pkiBrazil.dateOfBirth;
				cert.pkiBrazil.dateOfBirth = new Date(parseInt(s.slice(0, 4), 10), parseInt(s.slice(5, 7), 10) - 1, parseInt(s.slice(8, 10), 10));
			}
			if (filter) {
				if (filter(cert)) {
					toReturn.push(cert);
				}
			} else {
				toReturn.push(cert);
			}
		}

		toReturn.sort(function(a, b) {
			// sort the certificates by its subject common name
			if (a.subjectName > b.subjectName) {
				return 1;
			} else if (a.subjectName < b.subjectName) {
				return -1;
			} else {
				// same common name, sort by the expiration date, the longer date, the first
				return a.validityEnd > b.validityEnd ? -1 : (a.validityEnd < b.validityEnd ? 1 : 0);
			}
		});

		if (selectId) {
			if (!selectOptionFormatter) {
				selectOptionFormatter = function (c) {
					return c.subjectName + ' (issued by ' + c.issuerName + ')';
				};
			}
			var select = document.getElementById(selectId);
			while (select.options.length > 0) {
				select.remove(0);
			}
			for (var j = 0; j < toReturn.length; j++) {
				var c = toReturn[j];
				var option = document.createElement('option');
				option.value = c.thumbprint;
				option.text = selectOptionFormatter(c);
				select.add(option);
			}
		}
		return toReturn;
	};

	$._processKeyUsage = function (keyUsageValue) {
	    return {
	        crlSign: (keyUsageValue & $._certKeyUsages.crlSign) !== 0,
	        dataEncipherment: (keyUsageValue & $._certKeyUsages.dataEncipherment) !== 0,
	        decipherOnly: (keyUsageValue & $._certKeyUsages.decipherOnly) !== 0,
	        digitalSignature: (keyUsageValue & $._certKeyUsages.digitalSignature) !== 0,
	        encipherOnly: (keyUsageValue & $._certKeyUsages.encipherOnly) !== 0,
	        keyAgreement: (keyUsageValue & $._certKeyUsages.keyAgreement) !== 0,
	        keyCertSign: (keyUsageValue & $._certKeyUsages.keyCertSign) !== 0,
	        keyEncipherment: (keyUsageValue & $._certKeyUsages.keyEncipherment) !== 0,
	        nonRepudiation: (keyUsageValue & $._certKeyUsages.nonRepudiation) !== 0
	    };
	};

	$.filters = {
		isPkiBrazilPessoaFisica: function (cert) {
			if (typeof cert == 'undefined') {
				throw 'filter called without cert argument (hint: if you are using "pki.filters.isPkiBrazilPessoaFisica()", try "pki.filters.isPkiBrazilPessoaFisica")';
			}
			return (cert.pkiBrazil && (cert.pkiBrazil.cpf || '') !== '' && (cert.pkiBrazil.cnpj || '') === '');
		},
		hasPkiBrazilCpf: function (cert) {
			if (typeof cert == 'undefined') {
				throw 'filter called without cert argument (hint: if you are using "pki.filters.hasPkiBrazilCpf()", try "pki.filters.hasPkiBrazilCpf")';
			}
			return (cert.pkiBrazil && (cert.pkiBrazil.cpf || '') !== '');
		},
		hasPkiBrazilCnpj: function (cert) {
			if (typeof cert == 'undefined') {
				throw 'filter called without cert argument (hint: if you are using "pki.filters.hasPkiBrazilCnpj()", try "pki.filters.hasPkiBrazilCnpj")';
			}
			return (cert.pkiBrazil && (cert.pkiBrazil.cnpj || '') !== '');
		},
		pkiBrazilCpfEquals: function (cpf) {
			if (typeof cpf !== 'string') {
				throw 'cpf must be a string (hint: if you are using "pki.filters.pkiBrazilCpfEquals", try "pki.filters.pkiBrazilCpfEquals(' + "'" + 'somecpf' + "'" + ')")';
			}
			return function (cert) {
				return (cert.pkiBrazil && cert.pkiBrazil.cpf === cpf);
			};
		},
		pkiBrazilCnpjEquals: function (cnpj) {
			if (typeof cnpj !== 'string') {
				throw 'cnpj must be a string (hint: if you are using "pki.filters.pkiBrazilCnpjEquals", try "pki.filters.pkiBrazilCnpjEquals(' + "'" + 'somecnpj' + "'" +')")';
			}
			return function (cert) {
				return (cert.pkiBrazil && cert.pkiBrazil.cnpj === cnpj);
			};
		},
		hasPkiItalyCodiceFiscale: function (cert) {
			if (typeof cert == 'undefined') {
				throw 'filter called without cert argument (hint: if you are using "pki.filters.hasPkiItalyCodiceFiscale()", try "pki.filters.hasPkiItalyCodiceFiscale")';
			}
			return (cert.pkiItaly && (cert.pkiItaly.codiceFiscale || '') !== '');
		},
		pkiItalyCodiceFiscaleEquals: function (cf) {
			if (typeof cf !== 'string') {
				throw 'cf must be a string (hint: if you are using "pki.filters.pkiItalyCodiceFiscaleEquals", try "pki.filters.pkiItalyCodiceFiscaleEquals(' + "'" + 'someCodice' + "'" + ')")';
			}
			return function (cert) {
				return (cert.pkiItaly && cert.pkiItaly.codiceFiscale === cf);
			};
		},
		isWithinValidity: function (cert) {
			if (typeof cert == 'undefined') {
				throw 'filter called without cert argument (hint: if you are using "pki.filters.isWithinValidity()", try "pki.filters.isWithinValidity")';
			}
			var now = new Date();
			return (cert.validityStart <= now && now <= cert.validityEnd);
		},
		all: function () {
			var filters;
			if (arguments.length === 1 && typeof arguments[0] === 'object') {
				filters = arguments[0];
			} else {
				filters = arguments;
			}
			return function (cert) {
				for (var i = 0; i < filters.length; i++) {
					var filter = filters[i];
					if (!filter(cert)) {
						return false;
					}
				}
				return true;
			};
		},
		any: function () {
			var filters;
			if (arguments.length === 1 && typeof arguments[0] === 'object') {
				filters = arguments[0];
			} else {
				filters = arguments;
			}
			return function (cert) {
				for (var i = 0; i < filters.length; i++) {
					var filter = filters[i];
					if (filter(cert)) {
						return true;
					}
				}
				return false;
			};
		}
	};

	$.readCertificate = function (args) {

		if (typeof args === 'string') {
			args = {
				thumbprint: args
			};
		}

		var context = this._createContext(args);
		$._requestHandler.sendCommand(context, 'readCertificate', { certificateThumbprint: args.thumbprint });
		return context.promise;
	};

	$.pollNative = function (args) {
		if (!args) {
			args = {};
		}
		var context = this._createContext(args);
		var apiVersion = args.requiredApiVersion;

		if (!apiVersion) {
			apiVersion = $.apiVersions.latest;
		}
		if (!$._apiMap.nativeWin[apiVersion]) {
			throw 'Unknown JSlib API version: ' + apiVersion;
		}

		$._requestHandler.sendCommand(context, 'pollNative', {
            requiredNativeWinVersion:   $._apiMap.nativeWin[apiVersion],
            requiredNativeLinuxVersion: $._apiMap.nativeLinux[apiVersion],
            requiredNativeMacVersion:   $._apiMap.nativeMac[apiVersion]
		});
		return context.promise;
	};

	$.signHash = function (args) {
		var context = this._createContext(args);
		var request = {
			certificateThumbprint: args.thumbprint,
			hash: args.hash,
			digestAlgorithm: args.digestAlgorithm
		};
		$._requestHandler.sendCommand(context, 'signHash', request);
		return context.promise;
	};

	$.signData = function (args) {
		var context = this._createContext(args);
		var request = {
			certificateThumbprint: args.thumbprint,
			data: args.data,
			digestAlgorithm: args.digestAlgorithm
		};
		$._requestHandler.sendCommand(context, 'signData', request);
		return context.promise;
	};

	$.signWithRestPki = function (args) {
	    var context = this._createContext(args);
	    var request = {
	        certificateThumbprint: args.thumbprint,
	        token: args.token,
            restPkiUrl: this.restPkiUrl
	    };
	    $._requestHandler.sendCommand(context, 'signWithRestPki', request);
	    return context.promise;
	};

	$.preauthorizeSignatures = function (args) {

		if (!args) {
			args = {};
		}

		var context = this._createContext(args);
		var request = {
			certificateThumbprint: args.certificateThumbprint,
			signatureCount: args.signatureCount
		};
		$._requestHandler.sendCommand(context, 'preauthorizeSignatures', request);
		return context.promise;
	};

	$.showFolderBrowser = function (args) {

		if (!args) {
			args = {};
		} else if (typeof args === 'string') {
			args = {
				message: args
			};
		}

		var context = this._createContext(args);
		var request = {
			message: args.message
		};
		$._requestHandler.sendCommand(context, 'showFolderBrowser', request);
		return context.promise;
	};

	$.showFileBrowser = function (args) {

	    if (!args) {
	        args = {};
	    }

	    var context = this._createContext(args);
	    var request = {
	        multiselect: args.multiselect,
            filters: args.filters,
            dialogTitle: args.dialogTitle
	    };
	    $._requestHandler.sendCommand(context, 'showFileBrowser', request);
	    return context.promise;
	};

	$.downloadToFolder = function (args) {

		if (!args) {
			args = {};
		}

		var url = args.url || '';
		if (url.indexOf('://') < 0) {
			var a = document.createElement('a');
			a.href = url;
			url = a.href;
		}
	
		var context = this._createContext(args);
		var request = {
			url: url,
			folderId: args.folderId,
			filename: args.filename
		};
		$._requestHandler.sendCommand(context, 'downloadToFolder', request);
		return context.promise;
	};

	$.openFolder = function (args) {

		if (!args) {
			args = {};
		} else if (typeof args === 'string') {
			args = {
				folderId: args
			};
		}

		var context = this._createContext(args);
		$._requestHandler.sendCommand(context, 'openFolder', args.folderId);
		return context.promise;
	};

	$.openFile = function (args) {

	    if (!args) {
	        args = {};
	    } else if (typeof args === 'string') {
	        args = {
	            fileId: args
	        };
	    }

	    var context = this._createContext(args);
	    $._requestHandler.sendCommand(context, 'openFile', args.fileId);
	    return context.promise;
	};

	$.redirectToInstallPage = function () {
		document.location.href = $._installUrl + (this.brand || '') + '?returnUrl=' + encodeURIComponent(document.URL) + '&jslib=2.9.0';
	};

	$.updateExtension = function (args) {
		if (!args) {
			args = {};
		}
		var context = this._createContext(args);
		$._requestHandler.sendCommand(context, 'updateExtension', null);
		return context.promise;
	};

	    // -------------------- Web PKI Pro functions --------------------------

	$._createCommonSignerRequest = function(args) {
		if (!args.output) {
			throw 'An output parameter must be passed to signer methods';
		}
	    return {
	    	fileId: args.fileId,
	    	content: args.content,
	        certificateThumbprint: args.certificateThumbprint,
	        output: {
	            mode: args.output.mode,
	            folderId: args.output.folderId,
	            dialogTitle: args.output.dialogTitle,
	            fileNameSuffix: args.output.fileNameSuffix
	        },
            trustArbitrators: args.trustArbitrators,
            clearPolicyTrustArbitrators: args.clearPolicyTrustArbitrators,
			policy: args.policy
		};
	};
	
	$.signPdf = function (args) {
	    var context = this._createContext(args);
	    var request = $._createCommonSignerRequest(args);
        request.visualRepresentation = args.visualRepresentation;
        request.pdfMarks = args.pdfMarks;
        request.bypassMarksIfSigned = args.bypassMarksIfSigned;

	    if (request.visualRepresentation && request.visualRepresentation.image && request.visualRepresentation.image.resource && !request.visualRepresentation.image.resource.content && request.visualRepresentation.image.resource.url && !/^(https?:)?\/\//.exec(request.visualRepresentation.image.resource.url)) {
	        $._downloadResource(request.visualRepresentation.image.resource.url, function (resource) {
	            request.visualRepresentation.image.resource = resource;
	            $._requestHandler.sendCommand(context, 'signPdf', request);
	        });
	    } else {
	        $._requestHandler.sendCommand(context, 'signPdf', request);
	    }
	    return context.promise;
	};

	$.signCades = function (args) {
	    var context = this._createContext(args);
	    var request = $._createCommonSignerRequest(args);
        request.cmsToCosignFileId = args.cmsToCosignFileId;
        request.autoDetectCosign = args.autoDetectCosign;
        request.includeEncapsulatedContent = args.includeEncapsulatedContent === null || args.includeEncapsulatedContent === undefined ? true : args.includeEncapsulatedContent;
	    
		$._requestHandler.sendCommand(context, 'signCades', request);
		return context.promise;
	};

	$.signFullXml = function (args) {
		var context = this._createContext(args);
		var request = $._createCommonSignerRequest(args);
		request.signerType = 'fullXml';

		$._signXmlCommon(args, request, context);
		return context.promise;
	};

	$.signXmlElement = function (args) {
		var context = this._createContext(args);
		var request = $._createCommonSignerRequest(args);
		request.signerType = 'xmlElement';
		request.toSignElementId = args.toSignElementId;
		request.toSignElementsIds = args.toSignElementsIds;
		request.toSignElementsXPath = args.toSignElementsXPath;
		request.idResolutionTable = args.idResolutionTable;

		$._signXmlCommon(args, request, context);
		return context.promise;
	};

	$._signXmlCommon = function (args, request, context) {
		request.signatureElementId = args.signatureElementId;

		if (args.signatureElementLocation) {
			request.signatureElementLocation = {
				xpath: args.signatureElementLocation.xpath,
				insertionOption: args.signatureElementLocation.insertionOption
			};
		}
		request.namespaces = args.namespaces;

		$._requestHandler.sendCommand(context, 'signXml', request);
	};

	$._createCommonOpenRequest = function(args) {
		return {
			signatureFileId: args.signatureFileId,
	    	signatureContent: args.signatureContent,
            validate: args.validate,
            dateReference: args.dateReference,
	        trustArbitrators: args.trustArbitrators,
	        clearPolicyTrustArbitrators: args.clearPolicyTrustArbitrators,
	        specificPolicy: args.specificPolicy
		};
	};

	$.openPades = function (args) {
	    var context = this._createContext(args);
	    var request = $._createCommonOpenRequest(args);
	    	
	    $._requestHandler.sendCommand(context, 'openPades', request);
	    return context.promise;
	};

	$.openCades = function (args) {
	    var context = this._createContext(args);
	    var request = $._createCommonOpenRequest(args);
    	request.originalFileId = args.originalFileId;
    	request.originalContent = args.originalContent;
        request.acceptablePolicies = args.acceptablePolicies;

	    $._requestHandler.sendCommand(context, 'openCades', request);
	    return context.promise;
	};

	$.openXmlSignature = function (args) {
		var context = this._createContext(args);
		var request = $._createCommonOpenRequest(args);
		request.idResolutionTable = args.idResolutionTable;
		request.acceptablePolicies = args.acceptablePolicies;

		$._requestHandler.sendCommand(context, 'openXmlSignature', request);
	    return context.promise;
	};

	$.listTokens = function(args) {
		var context = this._createContext(args);
		var request = {
			pkcs11Modules: $._getRequestOsP11Modules(args.pkcs11Modules)
		};
		$._requestHandler.sendCommand(context, 'listTokens', request);
		return context.promise;
	};

	$.generateTokenRsaKeyPair = function(args) {
		var context = this._createContext(args);
		var request = {
			pkcs11Modules: $._getRequestOsP11Modules(args.pkcs11Modules),
			subjectName: args.subjectName,
			tokenSerialNumber: args.tokenSerialNumber,
			keyLabel: args.keyLabel,
			keySize: args.keySize
		};
		$._requestHandler.sendCommand(context, 'generateTokenRsaKeyPair', request);
		return context.promise;
	};

	$.generateSoftwareRsaKeyPair = function(args) {
		var context = this._createContext(args);
		var request = {
			subjectName: args.subjectName,
			keySize: args.keySize
		};
		$._requestHandler.sendCommand(context, 'generateSoftwareRsaKeyPair', request);
		return context.promise;
	};

	$.importTokenCertificate = function(args) {
		var context = this._createContext(args);
		var request = {
			pkcs11Modules: $._getRequestOsP11Modules(args.pkcs11Modules),
			tokenSerialNumber: args.tokenSerialNumber,
			certificateContent: args.certificateContent,
			certificateLabel: args.certificateLabel
		};
		$._requestHandler.sendCommand(context, 'importTokenCertificate', request);
		return context.promise;
	};

	$.importCertificate = function(args) {
		var context = this._createContext(args);
		var request = {
			certificateContent: args.certificateContent,
			passwordPolicies: args.passwordPolicies,
			passwordMinLength: args.passwordMinLength,
			savePkcs12: args.savePkcs12
		};
		$._requestHandler.sendCommand(context, 'importCertificate', request);
		return context.promise;
	};

	$.sendAuthenticatedRequest = function(args) {
	    var context = this._createContext(args);
	    var request = {
	        certificateThumbprint: args.certificateThumbprint,
	        method: args.method,
	        headers: args.headers,
	        body: args.body,
            url: args.url
	    };
	    $._requestHandler.sendCommand(context, 'sendAuthenticatedRequest', request);
	    return context.promise;
	};


	// -------------------- Browser detection --------------------
	// http://stackoverflow.com/questions/2400935/browser-detection-in-javascript
	$.detectedBrowser = (function () {
		var ua = navigator.userAgent, tem,
		M = ua.match(/(opera|chrome|safari|firefox|msie|trident(?=\/))\/?\s*(\d+)/i) || [];
		if (/trident/i.test(M[1])) {
			tem = /\brv[ :]+(\d+)/g.exec(ua) || [];
			return 'IE ' + (tem[1] || '');
		}
		if (M[1] === 'Chrome') {
			tem = ua.match(/\b(OPR|Edge)\/(\d+)/);
			if (tem !== null) return tem.slice(1).join(' ').replace('OPR', 'Opera');
		}
		M = M[2] ? [M[1], M[2]] : [navigator.appName, navigator.appVersion, '-?'];
		if ((tem = ua.match(/version\/(\d+)/i)) !== null) M.splice(1, 1, tem[1]);
		return M.join(' ');
	})();




	// -------------------- Browser-dependent singleton --------------------

	if ($._requestHandler === undefined) {

		var extensionRequiredVersion = '0.0.0';
		var extensionFirstVersionWithSelfUpdate = null;

		var chromeNativeWinRequiredVersion = null;
		var chromeNativeLinuxRequiredVersion = null;
		var chromeNativeMacRequiredVersion = null;
		var ieAddonRequiredVersion = null;

		var isIE = null;
		var isChrome = null;
		var isFirefox = null;

		var setRequiredComponentVersions = function (apiVersion) {
			if (!apiVersion) {
				apiVersion = $.apiVersions.v1_3;

			}
			if (!$._apiMap.nativeWin[apiVersion]) {
				throw 'Unknown JSlib API version: ' + apiVersion;
			}

			chromeNativeWinRequiredVersion   = $._apiMap.nativeWin[apiVersion];
			chromeNativeLinuxRequiredVersion = $._apiMap.nativeLinux[apiVersion];
			chromeNativeMacRequiredVersion   = $._apiMap.nativeMac[apiVersion];
			ieAddonRequiredVersion           = $._apiMap.ieAddon[apiVersion];
			extensionRequiredVersion         = $._apiMap.extension[apiVersion];
			if (isChrome) {
				extensionFirstVersionWithSelfUpdate = $._chromeExtensionFirstVersionWithSelfUpdate;
			}
		};

		isIE = ($.detectedBrowser.indexOf('IE') >= 0);
		isChrome = ($.detectedBrowser.indexOf('Chrome') >= 0);
		isFirefox = ($.detectedBrowser.indexOf('Firefox') >= 0);
		
		if (!isIE) {

			// --------------------------------------------------------------------------------------------------------------------------------
			// ------------------------------------------------ WEB EXTENSION REQUEST HANDLER -------------------------------------------------
			// --------------------------------------------------------------------------------------------------------------------------------


			$._requestHandler = new function () {

				var requestEventName = 'com.lacunasoftware.WebPKI.RequestEvent';
				var responseEventName = 'com.lacunasoftware.WebPKI.ResponseEvent';
				var pendingRequests = {};


				var s4 = function () {
					return Math.floor((1 + Math.random()) * 0x10000).toString(16).substring(1);
				};

				var generateGuid = function () {
					return s4() + s4() + '-' + s4() + '-' + s4() + '-' + s4() + '-' + s4() + s4() + s4();
				};

				var registerPromise = function (promise, responseProcessor) {
					var requestId = generateGuid();
					pendingRequests[requestId] = { promise: promise, responseProcessor: responseProcessor };
					return requestId;
				};

				var sendCommand = function (context, command, request, responseProcessor) {
					var requestId = registerPromise(context.promise, responseProcessor);
					var message = {
						requestId: requestId,
						license: context.license,
						command: command,
						request: request
					};
					if (isChrome) {
					    var event = new CustomEvent('build', { 'detail': message });
					    event.initEvent(requestEventName);
					    document.dispatchEvent(event);
					} else {
					    window.postMessage({
					        port: requestEventName,
					        message: message
					    }, "*");
					}
				};

				var checkInstalled = function (context, apiVersion) {
				    setRequiredComponentVersions(apiVersion);
					setTimeout(function () { pollExtension(context, 25); }, 200); // 25 x 200 ms = 5 seconds until we give up
				};

				var pollExtension = function (context, tryCount) {
					$._log('polling extension');
					var meta = document.getElementById($._chromeExtensionId) || document.getElementById($._firefoxExtensionId.replace(/[^A-Za-z0-9_]/g, '_'));
					if (meta === null) {
						if (tryCount > 1) {
							setTimeout(function () {
								pollExtension(context, tryCount - 1);
							}, 200);
						} else {
							context.promise._invokeSuccess({
								isInstalled: false,
								status: $.installationStates.NOT_INSTALLED,
								message: 'The Web PKI extension is not installed',
								browserSpecificStatus: $._chromeInstallationStates.EXTENSION_NOT_INSTALLED
							});
						}
						return;
					}
					checkExtensionVersion(context);
				};

				var checkExtensionVersion = function (context) {
					$._log('checking extension version');
					var subPromise = new $.Promise(null);
					subPromise.success(function (version) {
						if ($._compareVersions(version, extensionRequiredVersion) < 0) {
							var canSelfUpdate = (extensionFirstVersionWithSelfUpdate !== null && $._compareVersions(version, extensionFirstVersionWithSelfUpdate) >= 0);
							context.promise._invokeSuccess({
								isInstalled: false,
								status: $.installationStates.OUTDATED,
								browserSpecificStatus: $._chromeInstallationStates.EXTENSION_OUTDATED,
								message: 'The Web PKI extension is outdated (installed version: ' + version + ', required version: ' + extensionRequiredVersion + ')',
								chromeExtensionCanSelfUpdate: canSelfUpdate
							});
						} else {
							initializeExtension(context);
						}
					});
					subPromise.fail(function (ex) {
						context.promise._invokeError(ex);
					});
					sendCommand({ license: context.license, promise: subPromise }, 'getExtensionVersion', null);
				};

				var initializeExtension = function (context) {
					$._log('initializing extension');
					var subPromise = new $.Promise(null);
					subPromise.success(function (response) {
						if (response.isReady) {
							$._nativeInfo = response.nativeInfo;
							if (response.nativeInfo.os === 'Windows' && $._compareVersions(response.nativeInfo.installedVersion, chromeNativeWinRequiredVersion) < 0) {
								context.promise._invokeSuccess({
									isInstalled: false,
									status: $.installationStates.OUTDATED,
									browserSpecificStatus: $._chromeInstallationStates.NATIVE_OUTDATED,
									message: 'The Web PKI native component is outdated (installed version: ' + response.nativeInfo.installedVersion + ', required version: ' + chromeNativeWinRequiredVersion + ')',
									platformInfo: response.platformInfo,
									nativeInfo: response.nativeInfo
								});
							} else if (response.nativeInfo.os === 'Linux' && $._compareVersions(response.nativeInfo.installedVersion, chromeNativeLinuxRequiredVersion) < 0) {
								context.promise._invokeSuccess({
									isInstalled: false,
									status: $.installationStates.OUTDATED,
									browserSpecificStatus: $._chromeInstallationStates.NATIVE_OUTDATED,
									message: 'The Web PKI native component is outdated (installed version: ' + response.nativeInfo.installedVersion + ', required version: ' + chromeNativeLinuxRequiredVersion + ')',
									platformInfo: response.platformInfo,
									nativeInfo: response.nativeInfo
								});
							} else if (response.nativeInfo.os === 'Darwin' && $._compareVersions(response.nativeInfo.installedVersion, chromeNativeMacRequiredVersion) < 0) {
								context.promise._invokeSuccess({
									isInstalled: false,
									status: $.installationStates.OUTDATED,
									browserSpecificStatus: $._chromeInstallationStates.NATIVE_OUTDATED,
									message: 'The Web PKI native component is outdated (installed version: ' + response.nativeInfo.installedVersion + ', required version: ' + chromeNativeMacRequiredVersion + ')',
									platformInfo: response.platformInfo,
									nativeInfo: response.nativeInfo
								});
							} else {	
								context.promise._invokeSuccess({
									isInstalled: true
								});
							}

						} else {
							context.promise._invokeSuccess({
								isInstalled: false,
								status: convertInstallationStatus(response.status),
								browserSpecificStatus: response.status,
								message: response.message,
								platformInfo: response.platformInfo,
								nativeInfo: response.nativeInfo
							});
						}
					});
					subPromise.fail(function (ex) {
						context.promise._invokeError(ex);
					});
					sendCommand({ license: context.license, promise: subPromise }, 'initialize', null);
				};

				var convertInstallationStatus = function (bss) {
					if (bss === $._chromeInstallationStates.INSTALLED) {
						return $.installationStates.INSTALLED;
					} else if (bss === $._chromeInstallationStates.EXTENSION_OUTDATED || bss === $._chromeInstallationStates.NATIVE_OUTDATED) {
						return $.installationStates.OUTDATED;
					} else {
						return $.installationStates.NOT_INSTALLED;
					}
				};

				var onResponseReceived = function (result) {
					var request = pendingRequests[result.requestId];
					delete pendingRequests[result.requestId];
					if (result.success) {
						if (request.responseProcessor) {
							result.response = request.responseProcessor(result.response);
						}
						request.promise._invokeSuccess(result.response);
					} else {
					    request.promise._invokeError(result.exception);
					}
				};

				this.sendCommand = sendCommand;
				this.checkInstalled = checkInstalled;

				if (isChrome) {
				    document.addEventListener(responseEventName, function (event) {
				        onResponseReceived(event.detail);
				    });
				} else {
				    window.addEventListener('message', function (event) {
				        if (event && event.data && event.data.port === responseEventName) {
				            onResponseReceived(event.data.message);
				        }
				    });
				}

			};

		} else {

			// --------------------------------------------------------------------------------------------------------------------------------
			// ------------------------------------------------------ IE REQUEST HANDLER ------------------------------------------------------
			// --------------------------------------------------------------------------------------------------------------------------------

			$._requestHandler = new function () {

				var pendingRequests = {};
				var currentPollIndex = 0;

				var s4 = function () {
					return Math.floor((1 + Math.random()) * 0x10000).toString(16).substring(1);
				};

				var generateGuid = function () {
					return s4() + s4() + '-' + s4() + '-' + s4() + '-' + s4() + '-' + s4() + s4() + s4();
				};

				var registerPromise = function (promise, responseProcessor) {
					var requestId = generateGuid();
					pendingRequests[requestId] = {
						promise: promise,
						pollStart: currentPollIndex,
						responseProcessor: responseProcessor
					};
					return requestId;
				};

				var getAddon = function() {
					return window.lacunaWebPKIExtension;
				};

				var poll = function () {

					if (getAddon()) {

						var resultsJson = getAddon().GetAvailableResults();
						if (resultsJson === null) {
							throw 'Add-on method GetAvailableResults failed'; // TODO
						}

						var results = JSON.parse(resultsJson);
						var requestIdsToRemove = [];
						for (var requestId in pendingRequests) {
							var pendingRequest = pendingRequests[requestId];
							var removePendingRequest = false;
							if (pendingRequest.sendFailed) {
								removePendingRequest = true;
							} else {
								var result = null;
								for (var i = 0; i < results.length; i++) {
									if (results[i].requestId == requestId) {
										result = results[i];
										break;
									}
								}
								if (result !== null) {
									if (result.success) {
										if (pendingRequest.responseProcessor) {
											result.response = pendingRequest.responseProcessor(result.response);
										}
										pendingRequest.promise._invokeSuccess(result.response);
									} else {
									    pendingRequest.promise._invokeError(result.exception);
									}
									removePendingRequest = true;
								} else if (currentPollIndex >= pendingRequest.pollStart + 120) { // timeout: 120 x 500ms = 60 seconds
									pendingRequest.promise._invokeError({
										message: 'The operation has timed out',
										complete: 'The operation has timed out',
										origin: 'helper',
										code: 'addon_timeout'
									});
									removePendingRequest = true;
								}
							}
							if (removePendingRequest) {
								requestIdsToRemove.push(requestId);
							}
						}
						for (var j = 0; j < requestIdsToRemove.length; j++) {
							delete pendingRequests[requestIdsToRemove[j]];
						}

						currentPollIndex += 1;
					}

					setTimeout(poll, 500);
				};

				var checkExtension = function (context, tryCount) {
					$._log('checking extension');
					if (getAddon() === null) {
						if (tryCount > 1) {
							setTimeout(function () {
								checkExtension(context, tryCount - 1);
							}, 200);
						} else {
							context.promise._invokeSuccess({
								isInstalled: false,
								status: $.installationStates.NOT_INSTALLED,
								message: 'The Web PKI add-on is not installed'
							});
						}
						return;
					}
					var subPromise = new $.Promise(null);
					subPromise.success(function (version) {
						$._nativeInfo = { os: 'Windows', installedVersion: version };
						if ($._compareVersions(version, ieAddonRequiredVersion) < 0) {
							context.promise._invokeSuccess({
								isInstalled: false,
								status: $.installationStates.OUTDATED,
								message: 'The Web PKI add-on is outdated (installed version: ' + version + ', latest version: ' + ieAddonRequiredVersion + ')'
							});
						} else {
							context.promise._invokeSuccess({
								isInstalled: true
							});
						}
					});
					subPromise.fail(function (ex) {
						context.promise._invokeError(ex);
					});
					sendCommand({ license: context.license, promise: subPromise }, 'getVersion', null);
				};

				var sendCommand = function (context, command, request, responseProcessor) {
					if (getAddon()) {
						var requestId = registerPromise(context.promise, responseProcessor);
						var message = {
							requestId: requestId,
							license: context.license,
							command: command,
							request: request
						};
						var sendCommandError;
						try {
							var success = getAddon().SendCommand(JSON.stringify(message));
							if (success === false) {
								sendCommandError = 'Failed to send command to add-on';
							}
						} catch (err) {
							sendCommandError = 'Exception when sending command to add-on: ' + err;
						}
						if (sendCommandError) {
							context.promise._invokeError({
								message: 'Failed to send command to add-on',
								complete: sendCommandError,
								origin: 'helper',
								code: 'addon_send_command_failure'
							}, 200);
							pendingRequests[requestId].sendFailed = true;
						}
					} else {
						context.promise._invokeError({
							message: 'Add-on not detected',
							complete: 'Add-on not detected',
							origin: 'helper',
							code: 'addon_not_detected'
						}, 200);
					}
				};

				var checkInstalled = function (context, apiVersion) {
				    setRequiredComponentVersions(apiVersion);
					setTimeout(function () { checkExtension(context, 25); }, 200); // 25 x 200 ms = 5 seconds until we give up
				};

				this.sendCommand = sendCommand;
				this.checkInstalled = checkInstalled;
				poll();
			};

		}


	}

})(LacunaWebPKI.prototype);

if (typeof exports === 'object') {
	if (Object.defineProperties) {
		Object.defineProperties(exports, {
			//Using this syntax instead of "exports.default = ..." to maintain compatibility with ES3 (because of the .default)
			'default': {
				value: LacunaWebPKI
			},
			// https://github.com/webpack/webpack/issues/2945
			'__esModule': {
				value: true
			},
			'LacunaWebPKI': {
				value: LacunaWebPKI
			}
		});
	} else {
		exports['default'] = LacunaWebPKI;
		exports.__esModule = true;
		exports.LacunaWebPKI = LacunaWebPKI;
	}
}
