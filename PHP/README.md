REST PKI PHP Sample
===================

This is the PHP sample for a web application using REST PKI.

For other languages, please visit the [project root](https://github.com/LacunaSoftware/RestPkiSamples).

Troubleshooting
---------------

If you get the following error when executing the sample:

	Fatal error: Uncaught exception 'GuzzleHttp\Exception\RequestException' with message 'cURL error 60: SSL certificate problem: unable to get local issuer certificate

It means you don't have the CURLOPT_CAINFO option configured in your php.ini file. This option tells PHP to look in a certain PEM file for a list
of the trusted root certificates. One file commonly used can be found [here](http://curl.haxx.se/ca/cacert.pem).

You can choose other files or, if you're using Windows Server, you can even
[generate one yourself](http://www.swiftsoftwaregroup.com/configuring-phpcurl-root-certificates-windows-server/)
based on the OS's trusted certificate roots.
	
Whatever file you use, you must save on on the server and then update your php.ini file:

	curl.cainfo=path-to-your-pem-file
	
