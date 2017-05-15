Lacuna Rest PKI change log
==========================

1.12.5 (2017-05-11)
-------------------

Database model update: No

- Add optional app setting DefaultTimestampTimeout to customize the default timeout when requesting timestamps
- Improve performance logs
- Update Lacuna PKI SDK to 1.16.7 to improve download logs


1.12.4 (2017-05-05)
-------------------

Database model update: No

- Fix bug that caused execution privileges over the site's folder to be required from the application


1.12.3 (2017-05-04)
-------------------

Database model update: No

- Fix bug on configuration of setting "Is on premises"
- Fix bug on hint of setting "Cryptographic keys"
- Fix bug on open/validate XML signatures API


1.12.2 (2017-04-26)
-------------------

Database model update: No

- Fix bug on certificate authentication with Web PKI


1.12.1 (2017-04-26)
-------------------

Database model update: No

- Fix bug on application startup that could cause double processing of background jobs


1.12.0 (2017-04-25)
-------------------

Database model update: **Yes**

- Add support for issuing timestamps through the REST API
- Add new API to open/validate XML signatures
- Add optional instrumentation with Application Insights
- Add optional app setting "AutoUpdateDatabase" to control whether the application will attempt to auto-update the database
- Add optional app setting "AllowInsecureMaintenance" to allow remote access to maintenance pages through HTTP
- Fix various issues occurring when running multiple instances of the application (high availability scenarios)
- Fix database concurrency issues occurring on high load situations
- Fix bug that occurred when the license for the Lacuna PKI SDK was changed
- Update Lacuna PKI SDK to 1.16.6


1.11.4 (2017-03-16)
-------------------

Database model update: No

- Update Lacuna PKI SDK to 1.16.5, thus:
	- Updated ICP-Brasil intermediate CAs
	- Changes on CAdES signature inspection to deal with incorrectly encoded timestamps


1.11.3 (2017-02-13)
-------------------

Database model update: No

- Alter XML policy "Nota Fiscal Eletrônica (NF-e)" to exclude signature elements IDs
- Update Lacuna PKI SDK to 1.16.2
	

1.11.2 (2017-01-20)
-------------------

Database model update: No

- Fix bug on database maintenance page

1.11.1 (2017-01-17)
-------------------

Database model update: No

- Fix bug on upload and download routes


1.11.0 (2017-01-17)
-------------------

Database model update: **Yes**

- Add support for multipart upload of files
- Add support for signing previously uploaded files, files from previous signatures and remote files
- Add support for downloading the signed file on a separate HTTP request ("download link")
- Add support for new PAdES visual representation tags (see [PadesTags.md](PadesTags.md))
- Add support for decoding all fields in X.509 names
- Add CAdES policy "Basic Electronic Signature (CAdES-BES) with signing-time and no CRLs"
- Fix bug that prevented system intermediate CAs from taking effect
- Fix bug that resetted the storage service to local everytime the application was restarted
- Update Lacuna PKI SDK to 1.16.0, thus:
	- Improve ICP-Brasil filds. Fields also return null if have default padding value
	- Improve Pades signature to load and validate adbe.pkcs7.sh1 PDF signatures
	- Improve internal downloads
	- Fix TSL UnderSupervision state as a valid state
	- Fix Certificate Store loading delay due certificate chain loading attempt
	- Fix CAdES archive timestamp encode
	- Fix PAdES visual representation empty line
	- Fix PDF marks and PAdES visual representation incorrect positioning in some rotated PDFs
	- Fix exception in some ICP-Brasil fields decode
	- Fix bug occurring in some PDF signatures
	- Fix CAdES complete revocation references attribute order
	- Update italian TSL uri and root


1.10.1 (2016-10-01)
-------------------

Database model update: No

- Add standard PAdES policies "Basic with ICP-Brasil certificates" and "PAdES-T with ICP-Brasil certificates"


1.10.0 (2016-09-27)
-------------------

Database model update: **Yes**

- Add support for performing CAdES detached signatures without uploading the data file
- Improve user management


1.9.1 (2016-09-22)
------------------

Database model update: No

- Fix bug preventing use of tags {{signerEmail}} and {{issuerCommonName}} on PAdES visual representation


1.9.0 (2016-08-25)
------------------

Database model update: **Yes**

- Add support for PDF marks
- Improve billing report


1.8.2 (2016-07-19)
------------------

Database model update: No

- Fix bug on CAdES co-signatures with timestamp


1.8.1 (2016-07-11)
------------------

Database model update: No

- Fix bug affecting some signatures of large PDFs
- Update Lacuna PKI SDK to 1.13.1


1.8.0 (2016-07-05)
------------------

Database model update: No

- Add new API to open/validate CAdES and PAdES signatures
- Add support for new ICP-Brasil PAdES signature policies AD-RB and AD-RT
- Add transaction history report
- Add support for tags {{signerEmail}} and {{issuerCommonName}} on PAdES visual representation
- Add property BinaryThumbprintSHA256 to CertificateModel
- Add support for creating users without a password (useful for application users)
- Add warning on system status when changing the storage when there are already stored blobs
- Increase maximum API request length to 100 MB
- Fix bug that prevented CAdES co-signatures
- Fix authorization bug on the download route for the security contexts' trusted root certificates
- Update Lacuna PKI SDK to 1.13.0


1.7.2 (2016-06-10)
------------------

Database model update: No

- Add detection of bad data passed by client applications:
	- Invalid/corrupt PDFs
	- Invalid PAdES visual representation parameters
- Fix bug on security context removal
- Fix bug causing unused ClientSideSignature records to be left behind in the database
- Fix bug that prevented admin users from viewing details of other users' events
- Update Lacuna PKI SDK to 1.12.2 (no bug fixes)


1.7.1 (2016-06-03)
------------------

Database model update: No

- Fix bug on download of a security context's trusted root certificate


1.7.0 (2016-06-03)
------------------

Database model update: **Yes**

- Add support for timestamping
- Add support for system-wide security contexts
- Add support for system-wide customized signature policies
- Add support for additional storage options for encrypted temporary files (storing on local filesystem is still supported):
	- Microsoft Azure Blob Storage
	- Amazon S3
- Add support for admins to generate access tokens for other users
- Add support for admins to view other users' events ("history")
- Add support for ICP-Brasil certificate fields "RG" and "OAB"
- Update Lacuna PKI SDK to 1.12.1, thus:
	- Add support for certificates with rare alternative SHA-1 with RSA signature algorithm OID (1.3.14.3.2.29)


1.6.4 (2016-05-05)
------------------

Database model update: **Yes**

- Store temporary encrypted files on local temp directory instead of storing on binary columns in the database
	- This change was done due to performance issues. The next release will add support for other storage options.


1.6.3 (2016-05-02)
------------------

Database model update: **Yes**

- Add support for customization of culture, format and time zone of the signing time in PAdES visual representation
- Update Lacuna PKI SDK to 1.12.0, thus:
	- Fix bug on encoding of ASN.1 structure AlgorithmIdentifier which caused the field "parameters" to be omitted instead
	  of being filled with the NULL value
	- No longer using the iTextSharp AGPL-licensed library
	- Fix bug on certificate revocation status validation which caused a stack overflow on rare OCSP validation scenarios
	- Fix bug on CRL decoding when the ReasonCode is present
	- Improve messages for certificate revocation status validation
	- Fix issue affecting validation of XML signatures having namespace declarations on the Signature element
	- Fix issue affecting positioning of PAdES visual representations in specific several-signers scenarios
	- Add ICP-Brasil trusted root "v5"


1.6.2 (2016-04-18)
------------------

Database model update: No

- Add support on PAdES visual representation for specifying a container inside the signature rectangle on which to place the text


1.6.1 (2016-02-22)
------------------

Database model update: No

- Fix bug causing delay on database access when running on Microsoft Azure


1.6.0 (2016-01-21)
------------------

Database model update: **Yes**

- Add support for XML signatures (XmlDSig/XAdES)
- Update Lacuna PKI SDK to 1.9.0, thus:
	- Improve certificate validation to check the PathLenConstraint extension


1.5.1 (2015-11-24)
------------------

Database model update: No

- Aesthetic changes only


1.5.0 (2015-11-23)
------------------

Database model update: **Yes**

- Add support for CAdES signatures
- Add transaction register (for future billing)
- Add support for Lacuna PKI SDK licenses with use restricted to REST PKI
- Improve removal of expired signature processes
- Update Lacuna PKI SDK to 1.8.0, thus:
	- Modify behavior of decoding of ICP-Brasil certificate fields to decode fields regardless of whether the
	  certificate appears to be an ICP-Brasil certificate or not
	- Modify behavior of decoding of CompanyName ICP-Brasil certificate field to return the company name when the certificate is
	  a ICP-Brasil company (PJ) certificate (previously the property only worked for ICP-Brasil application certificates)
	- Add support for ICP-Brasil CPF field on "OU" field of subject name having a space after the colon ("OU=CPF: xxxxxxxxxxx")

	
1.4.3 (2015-11-06)
------------------

Database model update: No

- Aesthetic changes only


1.4.2 (2015-11-06)
------------------

Database model update: No

- Aesthetic changes only


1.4.1 (2015-10-21)
------------------

Database model update: No

- Aesthetic changes only


1.4.0 (2015-10-21)
------------------

Database model update: No

- Add support on PAdES visual representation for horizontal text alignment to the right
- Set site culture to pt-BR (affects PAdES visual representation)
- Fix bug on PAdES signatures


1.3.1 (2015-10-14)
------------------

Database model update: No

- Improve logging to file so as to prevent indefinite file growth
- Add information about the Lacuna PKI SDK license on the system status screen
- Fix minor issue on javascript when Google Analytics is not being used
- Fix minor issue on log test dialog
- Update Lacuna PKI SDK to 1.6.0, thus:
	- Fix bug on logging which caused the "source" argument to have an incorrect value

1.3.0 (2015-10-13)
------------------

- First version released publicly
- Main features on this version:
	- Certificate authentication
	- PAdES signatures
