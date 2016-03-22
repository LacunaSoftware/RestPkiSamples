# REST PKI client lib for Python
# This file contains classes that encapsulate the calls to the REST PKI API.
import sys
import six
import requests
import base64
import simplejson as json
from abc import ABCMeta, abstractmethod


class StandardSecurityContexts:
    PKI_BRAZIL = '201856ce-273c-4058-a872-8937bd547d36'
    PKI_ITALY = 'c438b17e-4862-446b-86ad-6f85734f0bfe'
    WINDOWS_SERVER = '3881384c-a54d-45c5-bbe9-976b674f5ec7'

    def __init__(self):
        return


class StandardSignaturePolicies:
    PADES_BASIC = '78d20b33-014d-440e-ad07-929f05d00cdf'

    CADES_BES = 'a4522485-c9e5-46c3-950b-0d6e951e17d1'
    CADES_ICPBR_ADR_BASICA = '3ddd8001-1672-4eb5-a4a2-6e32b17ddc46'
    CADES_ICPBR_ADR_TEMPO = 'a5332ad1-d105-447c-a4bb-b5d02177e439'
    CADES_ICPBR_ADR_VALIDACAO = '92378630-dddf-45eb-8296-8fee0b73d5bb'
    CADES_ICPBR_ADR_COMPLETA = '30d881e7-924a-4a14-b5cc-d5a1717d92f6'

    XMLDSIG_BASIC = "2bb5d8c9-49ba-4c62-8104-8141f6459d08"
    XADES_BES = "1beba282-d1b6-4458-8e46-bd8ad6800b54"
    XADES_ICPBR_ADR_BASICA = "1cf5db62-58b6-40ba-88a3-d41bada9b621"
    XADES_ICPBR_ADR_TEMPO = "5aa2e0af-5269-43b0-8d45-f4ef52921f04"
    NFE_PADRAO_NACIONAL = "a3c24251-d43a-4ba4-b25d-ee8e2ab24f06"


    def __init__(self):
        return


class RestPkiClient:
    _endpointUrl = ''
    _headers = dict()

    def __init__(self, endpoint_url, access_token):
        self._endpointUrl = endpoint_url
        self.accessToken = access_token
        self._headers['Authorization'] = 'Bearer %s' % self.accessToken
        self._headers['Accept'] = 'application/json'
        self._headers['Content-Type'] = 'application/json'

    def post(self, url, data=None):
        response = requests.post('%s%s' % (self._endpointUrl, url), data=json.dumps(data), headers=self._headers)
        response.raise_for_status()
        return response

    def get(self, url, params=None):
        response = requests.get('%s%s' % (self._endpointUrl, url), params=params, headers=self._headers)
        response.raise_for_status()
        return response

    def get_authentication(self):
        return Authentication(self)


class Authentication:
    _client = None
    _certificate = None
    _done = False

    def __init__(self, restpki_client):
        self._client = restpki_client

    def start_with_webpki(self, security_context_id):
        response = self._client.post('Api/Authentications', data={'securityContextId': security_context_id})
        return response.json().get('token', None)

    def complete_with_webpki(self, token):
        response = self._client.post('Api/Authentications/%s/Finalize' % token)
        self._certificate = response.json().get('certificate', None)
        self._done = True
        return ValidationResults(response.json().get('validationResults', None))

    @property
    def certificate(self):
        if not self._done:
            raise Exception(
                'The property "certificate" can only be called after calling the complete_with_webpki() method')

        return self._certificate


class PadesSignatureStarter:
    pdf_content = None
    security_context_id = None
    signature_policy_id = None
    callback_argument = None
    visual_representation = None
    _client = None

    def __init__(self, client):
        self._client = client

    def set_pdf_path(self, local_pdf_path):
        f = open(local_pdf_path, 'rb')
        self.pdf_content = f.read()
        f.close()

    def start_with_webpki(self):
        if self.pdf_content is None:
            raise Exception('The PDF to sign was not set')

        if self.signature_policy_id is None:
            raise Exception('The signature policy was not set')

        data = dict()
        data['pdfToSign'] = base64.b64encode(self.pdf_content)
        data['signaturePolicyId'] = self.signature_policy_id
        data['securityContextId'] = self.security_context_id
        data['callbackArgument'] = self.callback_argument
        data['visualRepresentation'] = self.visual_representation

        response = self._client.post('Api/PadesSignatures', data=data)
        return response.json().get('token', None)


class PadesSignatureFinisher:
    token = ''
    _client = None
    _done = False
    _signed_pdf_content = None
    _certificate = None
    _callback_argument = None

    def __init__(self, restpki_client):
        self._client = restpki_client

    def finish(self):
        if not self.token:
            raise Exception('The token was not set')

        response = self._client.post('Api/PadesSignatures/%s/Finalize' % self.token)
        self._signed_pdf_content = base64.b64decode(response.json().get('signedPdf', None))
        self._certificate = response.json().get('certificate', None)
        self._callback_argument = response.json().get('callbackArgument', None)
        self._done = True

    @property
    def signed_pdf_content(self):
        if not self._done:
            raise Exception('The property "signed_pdf_content" can only be called after calling the finish() method')

        return self._signed_pdf_content

    @property
    def callback_argument(self):
        if not self._done:
            raise Exception('The property "callback_argument" can only be called after calling the finish() method')

        return self._callback_argument

    @property
    def certificate(self):
        if not self._done:
            raise Exception('The property "certificate" can only be called after calling the finish() method')

        return self._certificate

    def write_signed_pdf(self, local_pdf_path):
        if not self._done:
            raise Exception(
                'The method write_signed_pdf() can only be called after calling the finish() method')

        f = open(local_pdf_path, 'wb')
        f.write(self._signed_pdf_content)
        f.close()


class CadesSignatureStarter:
    content_to_sign = None
    security_context_id = None
    signature_policy_id = None
    cms_to_cosign_bytes = None
    encapsulate_content = None
    callback_argument = None
    _client = None

    def __init__(self, client):
        self._client = client

    def set_file_to_sign_path(self, path):
        f = open(path, 'rb')
        self.content_to_sign = f.read()
        f.close()

    def set_cms_to_cosign_path(self, path):
        f = open(path, 'rb')
        self.cms_to_cosign_bytes = f.read()
        f.close()

    def start_with_webpki(self):
        if self.content_to_sign is None and self.cms_to_cosign_bytes is None:
            raise Exception('The content to sign was not set and no CMS to be co-signed was given')

        if self.signature_policy_id is None:
            raise Exception('The signature policy was not set')

        data = dict()
        data['signaturePolicyId'] = self.signature_policy_id
        data['securityContextId'] = self.security_context_id
        data['callbackArgument'] = self.callback_argument
        data['encapsulateContent'] = self.encapsulate_content
        if self.content_to_sign is not None:
            data['contentToSign'] = base64.b64encode(self.content_to_sign)
        if self.cms_to_cosign_bytes is not None:
            data['cmsToCoSign'] = base64.b64encode(self.cms_to_cosign_bytes)

        response = self._client.post('Api/CadesSignatures', data=data)
        return response.json().get('token', None)


class CadesSignatureFinisher:
    token = None
    _client = None
    _done = False
    _cms = None
    _certificate = None
    _callback_argument = None

    def __init__(self, restpki_client):
        self._client = restpki_client

    def finish(self):
        if not self.token:
            raise Exception('The token was not set')

        response = self._client.post('Api/CadesSignatures/%s/Finalize' % self.token).json()
        self._cms = base64.b64decode(response.get('cms', None))
        self._certificate = response.get('certificate', None)
        self._callback_argument = response.get('callbackArgument', None)
        self._done = True

    @property
    def cms(self):
        if not self._done:
            raise Exception('The property "cms" can only be called after calling the finish() method')

        return self._cms

    @property
    def certificate(self):
        if not self._done:
            raise Exception('The property "certificate" can only be called after calling the finish() method')

        return self._certificate

    @property
    def callback_argument(self):
        if not self._done:
            raise Exception('The property "callback_argument" can only be called after calling the finish() method')

        return self._callback_argument

    def write_cms(self, path):
        if not self._done:
            raise Exception(
                'The method write_cms() can only be called after calling the finish() method')

        f = open(path, 'wb')
        f.write(self._cms)
        f.close()


class ValidationItem:
    itemType = None
    message = ''
    detail = ''
    innerValidationResults = None

    def __init__(self, model):
        self.itemType = model['type']
        self.message = model['message']
        self.detail = model['detail']

        if model['innerValidationResults'] is not None:
            self.innerValidationResults = ValidationResults(model['innerValidationResults'])

    def __unicode__(self):
        return self.to_string(0)

    def to_string(self, indentation_level):
        text = ''
        text += self.message

        if self.detail is not None and len(self.detail) > 0:
            text += ' (%s)' % self.detail

        if self.innerValidationResults is not None:
            text += '\n'
            text += ''
            text += self.innerValidationResults.to_string(indentation_level + 1)

        return text


class PadesVisualPositioningPresets:
    _cached_presets = dict()

    def __init__(self):
        return

    @staticmethod
    def get_footnote(restpki_client, page_number=None, rows=None):
        url_segment = 'Footnote'

        if page_number is not None:
            url_segment += '?pageNumber=%s' % page_number

        if rows is not None:
            url_segment += '?rows=%s' % rows

        return PadesVisualPositioningPresets._get_preset(restpki_client, url_segment)

    @staticmethod
    def get_new_page(restpki_client):
        return PadesVisualPositioningPresets._get_preset(restpki_client, 'NewPage')

    @staticmethod
    def _get_preset(restpki_client, url_segment):
        if url_segment in PadesVisualPositioningPresets._cached_presets:
            return PadesVisualPositioningPresets._cached_presets[url_segment]

        preset = restpki_client.get('Api/PadesVisualPositioningPresets/%s' % url_segment)
        PadesVisualPositioningPresets._cached_presets[url_segment] = preset.json()
        return preset.json()


# XML
# ----------------------------------------------------------------------------------------------------------------------

class XmlInsertionOptions:
    appendChild = 'appendChild'
    prependChild = 'prependChild'
    appendSibling = 'appendSibling'
    prependSibling = 'prependSibling'

class NamespaceManager:
    namespaces = None

    def __init__(self):
        self.namespaces = []

    def add_namespace(self, prefix, uri):
        ns = {'prefix': prefix, 'uri': uri}
        self.namespaces.append(ns)

class XmlIdResolutionTable:
    _include_xml_id_attribute = None
    _element_id_attributes = None
    _global_id_attributes = None

    def __init__(self, include_xml_id_attribute = True):
        self._include_xml_id_attribute = include_xml_id_attribute
        self._element_id_attributes = []
        self._global_id_attributes = []

    def add_global_id_attribute(self, id_attribute_local_name, id_attribute_namespace = None):
        self._global_id_attributes.append({'localName': id_attribute_local_name, 'namespace': id_attribute_namespace})

    def set_element_id_attribute(self, element_local_name, element_namespace, id_attribute_local_name, id_attribute_namespace = None):
        self._element_id_attributes.append({'element': {'localName': element_local_name, 'namespace': element_namespace}, 'attribute': {'localName': id_attribute_local_name, 'namespace': id_attribute_namespace}})

    def to_model(self):
        return {'includeXmlIdAttribute': self._include_xml_id_attribute, 'elementIdAttributes': self._element_id_attributes, 'globalIdAttributes': self._global_id_attributes}

class XmlSignatureStarter:
    __metaclass__ = ABCMeta
    _client = None
    xml_content = None
    _xpath = None
    _insertion_option = None
    _namespace_manager = None
    signature_element_id = None
    security_context_id = None
    signature_policy_id = None
    callback_argument = None

    @abstractmethod
    def start_with_webpki(self):
        pass

    def __init__(self, client):
        self._client = client

    def set_xml_path(self, local_pdf_path):
        f = open(local_pdf_path, 'rb')
        self.xml_content = f.read()
        f.close()

    def set_signature_element_location(self, xpath, insertion_options, namespace_manager):
        self._xpath = xpath
        self._insertion_option = insertion_options
        self._namespace_manager = namespace_manager

    def get_common_request_data(self):
        if self.signature_policy_id is None:
            raise Exception('The signature policy was not set')
        data = dict()
        if self.xml_content is not None:
            data['xml'] = base64.b64encode(self.xml_content)

        if self._xpath is not None:
            data['signatureElementLocation'] = {'xpath': self._xpath, 'InsertionOption': None if self._insertion_option is None else self._insertion_option, 'namespaces': None if self._namespace_manager is None else self._namespace_manager.namespaces}
        data['signatureElementId'] = self.signature_element_id
        data['signaturePolicyId'] = self.signature_policy_id
        data['securityContextId'] = self.security_context_id
        data['callbackArgument'] = self.callback_argument
        return data

class XmlElementSignatureStarter(XmlSignatureStarter):
    element_tosign_id = None
    id_resolution_table = None

    def __init__(self, client):
        super(XmlElementSignatureStarter, self).__init__(client)

    def start_with_webpki(self):
        if self.xml_content is None:
            raise Exception('The XML to sign was not set')

        if self.element_tosign_id is None or self.element_tosign_id == '':
            raise Exception('The XML element Id to sign was not set')

        data = super(XmlElementSignatureStarter, self).get_common_request_data()
        data['elementToSignId'] = self.element_tosign_id
        if self.id_resolution_table is not None:
            data['idResolutionTable'] = self.id_resolution_table.to_model()

        response = self._client.post('Api/XmlSignatures/XmlElementSignature', data=data)
        return response.json().get('token', None)

class FullXmlSignatureStarter(XmlSignatureStarter):

    def __init__(self, client):
        super(FullXmlSignatureStarter, self).__init__(client)

    def start_with_webpki(self):
        if self.xml_content is None:
            raise Exception('The XML to sign was not set')

        data = super(FullXmlSignatureStarter, self).get_common_request_data()
        response = self._client.post('Api/XmlSignatures/FullXmlSignature', data=data)
        return response.json().get('token', None)

class DetachedResourceXmlSignatureStarter(XmlSignatureStarter):
    resource_content = None
    resource_uri = None

    def __init__(self, client):
        super(DetachedResourceXmlSignatureStarter, self).__init__(client)

    def set_tosign_detached_resource(self, resource_path, resource_uri = None):
        self.resource_uri = resource_uri
        f = open(resource_path, 'rb')
        self.resource_content = f.read()
        f.close()

    def start_with_webpki(self):
        if self.resource_content is None or len(resource_content) == 0:
            raise Exception('The detached resource to sign was not set')

        data = super(DetachedResourceXmlSignatureStarter, self).get_common_request_data()
        data['detachedResourceToSignContent'] = self.resource_content
        data['detachedResourceReferenceUri'] = self.resource_uri
        response = self._client.post('Api/XmlSignatures/DetachedResourceXmlSignature', data=data)
        return response.json().get('token', None)

class OnlineResourceXmlSignatureStarter(XmlSignatureStarter):
    resource_uri = None

    def __init__(self, client):
        super(OnlineResourceXmlSignatureStarter, self).__init__(client)

    def start_with_webpki(self):
        if self.resource_uri is None or resource_uri == '':
            raise Exception('The online resource URI to sign was not set')

        data = super(OnlineResourceXmlSignatureStarter, self).get_common_request_data()
        data['resourceToSignUri'] = self.resource_uri
        response = self._client.post('Api/XmlSignatures/OnlineResourceXmlSignature', data=data)
        return response.json().get('token', None)

class XmlSignatureFinisher:
    token = ''
    _client = None
    _done = False
    _signed_xml_content = None
    _certificate = None
    _callback_argument = None

    def __init__(self, restpki_client):
        self._client = restpki_client

    def finish(self):
        if not self.token:
            raise Exception('The token was not set')

        response = self._client.post('Api/XmlSignatures/%s/Finalize' % self.token)
        self._signed_xml_content = base64.b64decode(response.json().get('signedXml', None))
        self._certificate = response.json().get('certificate', None)
        self._callback_argument = response.json().get('callbackArgument', None)
        self._done = True

    @property
    def signed_xml_content(self):
        if not self._done:
            raise Exception('The property "signed_xml_content" can only be called after calling the finish() method')

        return self._signed_xml_content

    @property
    def callback_argument(self):
        if not self._done:
            raise Exception('The property "callback_argument" can only be called after calling the finish() method')

        return self._callback_argument

    @property
    def certificate(self):
        if not self._done:
            raise Exception('The property "certificate" can only be called after calling the finish() method')

        return self._certificate

    def write_signed_xml(self, local_xml_path):
        if not self._done:
            raise Exception(
                'The method write_signed_xml() can only be called after calling the finish() method')

        f = open(local_xml_path, 'wb')
        f.write(self._signed_xml_content)
        f.close()

#-----------------------------------------------------------------------------------------------------------------------

@six.python_2_unicode_compatible
class ValidationResults:
    errors = None
    warnings = None
    passed_checks = None

    def __init__(self, model):
        self.errors = self._convert_items(model['errors'])
        self.warnings = self._convert_items(model['warnings'])
        self.passed_checks = self._convert_items(model['passedChecks'])

    @property
    def is_valid(self):
        return len(self.errors) == 0

    @property
    def checks_performed(self):
        return len(self.errors) + len(self.warnings) + len(self.passed_checks)

    @property
    def has_errors(self):
        return not len(self.errors) == 0

    @property
    def has_warnings(self):
        return not len(self.warnings) == 0

    @staticmethod
    def _convert_items(items):
        converted = list()
        for item in items:
            converted.append(ValidationItem(item))

        return converted

    @staticmethod
    def _join_items(items, indentation_level):
        text = ''
        is_first = True
        tab = '\t' * indentation_level

        for item in items:
            if is_first:
                is_first = False
            else:
                text += '\n'

            text += '%s- ' % tab
            text += item.to_string(indentation_level)

        return text

    @property
    def summary(self):
        return self.get_summary(0)

    def get_summary(self, indentation_level=0):
        tab = '\t' * indentation_level
        text = '%sValidation results: ' % tab

        if self.checks_performed == 0:
            text += 'no checks performed'
        else:
            text += '%s checks performed' % self.checks_performed

            if self.has_errors:
                text += ', %s errors' % len(self.errors)
            if self.has_warnings:
                text += ', %s warnings' % len(self.warnings)
            if len(self.passed_checks) > 0:
                if not self.has_errors and not self.has_warnings:
                    text += ', all passed'
                else:
                    text += ', %s passed' % len(self.passed_checks)

        return text

    def __str__(self):
        return self.to_string(0)

    def to_string(self, indentation_level):
        tab = '\t' * indentation_level
        text = ''
        text += self.get_summary(indentation_level)

        if self.has_errors:
            text += '\n%sErrors:\n' % tab
            text += self._join_items(self.errors, indentation_level)

        if self.has_warnings:
            text += '\n%sWarnings:\n' % tab
            text += self._join_items(self.warnings, indentation_level)

        if self.passed_checks is not None:
            text += '\n%sPassed checks:\n' % tab
            text += self._join_items(self.passed_checks, indentation_level)

        return text
