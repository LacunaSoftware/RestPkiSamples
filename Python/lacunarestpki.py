# REST PKI client lib for Python
# This file contains classes that encapsulate the calls to the REST PKI API.

import requests
import base64
import json

class StandardSecurityContexts:
	PKI_BRAZIL = '201856ce-273c-4058-a872-8937bd547d36'
	PKI_ITALY = 'c438b17e-4862-446b-86ad-6f85734f0bfe'
	WINDOWS_SERVER = '3881384c-a54d-45c5-bbe9-976b674f5ec7'

class StandardSignaturePolicies:
	PADES_BASIC = '78d20b33-014d-440e-ad07-929f05d00cdf'

class RestPkiClient:
	endpointUrl = ''
	access = ''
	headers = dict()

	def __init__(self, endpointUrl, accessToken):
		self.endpointUrl = endpointUrl
		self.accessToken = accessToken
		self.headers['Authorization'] = 'Bearer %s' % self.accessToken
		self.headers['Accept'] = 'application/json'
		self.headers['Content-Type'] = 'application/json'

	def get_creds(self):
		return headers

	def post(self, url, data=None, headers=None):
		response = requests.post('%s%s' % (self.endpointUrl, url), data=json.dumps(data), headers=self.headers)
		response.raise_for_status()
		return response

	def get(self, url, params=None, headers=None):
		response = requests.get('%s%s' % (self.endpointUrl, url), params=params, headers=self.headers)
		response.raise_for_status()
		return response

	def get_authenticator(self):
		return Authenticator(self)

class Authenticator:
	rest = None
	certificate = None
	done = False

	def __init__(self, restClient):
		self.rest = restClient

	def start_with_webpki(self, securityContextId):
		response = self.rest.post('Api/Authentications', data = {'securityContextId': securityContextId})
		return response.json().get('token', None)

	def complete_with_webpki(self, token):
		response = self.rest.post('Api/Authentications/%s/Finalize' % token)
		self.certificate = response.json().get('certificate', None)
		self.done = True
		return ValidationResults(response.json().get('validationResults', None))

	def get_certificate(self):
		if not self.done:
			raise Exception('The method get_certificate() can only be called after calling the complete_with_webpki() method')

		return self.certificate

class PadesSignatureStarter:
	rest = None
	pdfContent = None
	securityContextId = None
	signaturePolicyId = None
	visualRepresentation = None

	def __init__(self, restClient):
		self.rest = restClient

	def set_pdf_to_sign(self, localPdfPath):
		f = open(localPdfPath, 'rb')
		self.pdfContent = f.read()
		f.close()

	def start_with_webpki(self):
		if self.pdfContent == None:
			raise Exception('The PDF to sign was not set')

		if self.signaturePolicyId == None:
			raise Exception('The signature policy was not set')

		data = dict()
		data['pdfToSign'] = base64.b64encode(self.pdfContent)
		data['signaturePolicyId'] = self.signaturePolicyId
		data['securityContextId'] = self.securityContextId
		data['visualRepresentation'] = self.visualRepresentation

		response = self.rest.post('Api/PadesSignatures', data = data)
		return response.json().get('token', None)

class PadesSignatureFinisher:
	rest = None
	token = ''
	done = False
	signedPdf = None
	certificate = None

	def __init__(self, restClient):
		self.rest = restClient

	def finish(self):
		if self.token == '':
			raise Exception('The token was not set')

		response = self.rest.post('Api/PadesSignatures/%s/Finalize' % self.token)
		self.signedPdf = base64.b64decode(response.json().get('signedPdf', None))
		self.certificate = response.json().get('certificate', None)
		self.done = True

	def get_certificate(self):
		if not self.done:
			raise Exception('The method get_certificate() can only be called after calling the finish() method')

		return self.certificate

	def write_signed_pdf_to_path(self, localPdfPath):
		if not self.done:
			raise Exception('The method write_signed_pdf_to_path() can only be called after calling the finish() method')

		f = open(localPdfPath, 'wb')
		f.write(self.signedPdf)
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

		if (model['innerValidationResults'] != None):
			self.innerValidationResults = ValidationResults(model['innerValidationResults'])

	def __str__(self):
		return self.to_string(0)

	def to_string(self, indentationLevel):
		text = ''
		text += self.message

		if self.detail != None and len(self.detail) > 0:
			text += ' (%s)' % self.detail

		if self.innerValidationResults != None:
			text += '\n'
			text += ''
			text += self.innerValidationResults.to_string(indentationLevel + 1)

		return text


class PadesVisualPositioningPresets:
	cachedPresets = dict()

	@staticmethod
	def get_footnote(restClient, pageNumber=None, rows=None):
		urlSegment = 'Footnote'

		if pageNumber != None:
			urlSegment += '?pageNumber=%s' % pageNumber

		if rows != None:
			urlSegment += '?rows=%s' % rows

		return PadesVisualPositioningPresets.get_preset(restClient, urlSegment)

	@staticmethod
	def get_new_page(restClient):
		return PadesVisualPositioningPresets.get_preset(restClient, 'NewPage')

	@staticmethod
	def get_preset(restClient, urlSegment):
		if urlSegment in PadesVisualPositioningPresets.cachedPresets:
			return PadesVisualPositioningPresets.cachedPresets[urlSegment]

		preset = restClient.get('Api/PadesVisualPositioningPresets/%s' % urlSegment)
		PadesVisualPositioningPresets.cachedPresets[urlSegment] = preset.json()
		return preset.json()

	@staticmethod
	def get_visual_representation_position(restClient, sampleNumber):
		if sampleNumber == 1:
			# // Example #1: automatic positioning on footnote. This will insert the signature, and future signatures,
			# // ordered as a footnote of the last page of the document
			return PadesVisualPositioningPresets.get_footnote(restClient)
		elif sampleNumber == 2:
			# // Example #2: get the footnote positioning preset and customize it
			visualPosition = PadesVisualPositioningPresets.get_footnote(restClient)
			visualPosition.auto.container.left = 2.54
			visualPosition.auto.container.bottom = 2.54
			visualPosition.auto.container.right = 2.54
			return visualPosition;
		elif sampleNumber == 3:
			# // Example #3: automatic positioning on new page. This will insert the signature, and future signatures,
			# // in a new page appended to the end of the document.
			return PadesVisualPositioningPresets.get_new_page(restClient)
		elif sampleNumber == 4:
			# // Example #4: get the "new page" positioning preset and customize it
			visualPosition = PadesVisualPositioningPresets.get_new_page(restClient)
			visualPosition.auto.container.left = 2.54
			visualPosition.auto.container.top = 2.54
			visualPosition.auto.container.right = 2.54
			visualPosition.auto.signatureRectangleSize.width = 5
			visualPosition.auto.signatureRectangleSize.height = 3
			return visualPosition
		elif sampleNumber == 5:
			# // Example #5: manual positioning
			return {
				'pageNumber': 0, #// zero means the signature will be placed on a new page appended to the end of the document
				'measurementUnits': 'Centimeters',
				#// define a manual position of 5cm x 3cm, positioned at 1 inch from the left and bottom margins
				'manual': {
					'left': 2.54,
					'bottom': 2.54,
					'width': 5,
					'height': 3
				}
			}
		elif sampleNumber == 6:
			# // Example #6: custom auto positioning
			return {
				'pageNumber': -1, #// negative values represent pages counted from the end of the document (-1 is last page)
				'measurementUnits': 'Centimeters',
				'auto': {
					#// Specification of the container where the signatures will be placed, one after the other
					'container': {
						# // Specifying left and right (but no width) results in a variable-width container with the given margins
						'left': 2.54,
						'right': 2.54,
						# // Specifying bottom and height (but no top) results in a bottom-aligned fixed-height container
						'bottom': 2.54,
						'height': 12.31
					},
					# // Specification of the size of each signature rectangle
					'signatureRectangleSize': {
						'width': 5,
						'height': 3
					},
					# // The signatures will be placed in the container side by side. If there's no room left, the signatures
					# // will "wrap" to the next row. The value below specifies the vertical distance between rows
					'rowSpacing': 1
				}
			};
		else:
			return None



class ValidationResults:
	errors = None
	warnings = None
	passedChecks = None

	def __init__(self, model):
		self.errors = self.convert_items(model['errors'])
		self.warnings = self.convert_items(model['warnings'])
		self.passedChecks = self.convert_items(model['passedChecks'])

	def is_valid(self):
		return len(self.errors) == 0

	def get_checks_performed(self):
		return len(self.errors) + len(self.warnings) + len(self.passedChecks)

	def has_errors(self):
		return not len(self.errors) == 0

	def has_warnings(self):
		return not len(self.warnings) == 0

	def convert_items(self, items):
		converted = list()
		for item in items:
			converted.append(ValidationItem(item))

		return converted

	def join_items(self, items, indentationLevel):
		text = ''
		isFirst = True
		tab = '\t' * indentationLevel

		for item in items:
			if isFirst:
				isFirst = False
			else:
				text += '\n'

			text += '%s-' % tab
			text += item.to_string(indentationLevel)

		return text

	def get_summary(self, indentationLevel = 0):
		tab = '\t' * indentationLevel
		text = '%sValidation results: ' % tab

		if (self.get_checks_performed() == 0):
			text += 'no checks performed'
		else:
			text += '%s checks performed' % self.get_checks_performed()

			if self.has_errors():
				text += ', %s errors' % len(self.errors)
			if self.has_warnings():
				text += ', %s warnings' % len(self.warnings)
			if len(self.passedChecks) > 0:
				if not self.has_errors() and not self.has_warnings():
					text += ', all passed'
				else:
					text += ', %s passed' % len(self.passedChecks)

		return text

	def __unicode__(self):
		return self.to_string(0)

	def to_string(self, indentationLevel):
		tab = '\t' * indentationLevel
		text = ''
		text += self.get_summary(indentationLevel)

		if self.has_errors():
			text += '\n%sErrors:\n' % tab
			text += self.join_items(self.errors, indentationLevel)

		if self.has_warnings():
			text += '\n%sWarnings:\n' % tab
			text += self.join_items(self.warnings, indentationLevel)

		if self.passedChecks != None:
			text += '\n%sPassed checks:\n' % tab
			text += self.join_items(self.passedChecks, indentationLevel)

		return text
