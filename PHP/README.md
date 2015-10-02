REST PKI PHP Sample
===================

This is a sample web application in PHP that shows how to use the
[REST PKI service](https://restpki.lacunasoftware.com/).

For other languages, please visit the [project root](https://github.com/LacunaSoftware/RestPkiSamples).

To run the samples, you will need an **API access token**. If you don't have one, register on the
[REST PKI website](https://restpki.lacunasoftware.com/) and generate a token.

Running the sample
------------------

1. [Download the project](https://github.com/LacunaSoftware/RestPkiSamples/archive/master.zip)
   or clone the repository

2. Generate an API access token on the [REST PKI website](https://restpki.lacunasoftware.com/)

3. Paste your access token on the file `PHP/api/util.php`
   
4. Setup a website on your local HTTP server pointing to the PHP folder
  
5. Open the index.php file on the browser on the corresponding URL (depending on the previous step)

Dependencies
------------

The project depends on the GuzzleHttp library, which in turn requires **PHP 5.5** or
greater. If you need a sample for an older version of PHP, please [contact us](https://webpki.lacunasoftware.com/#/Contact).

This dependency is specified in the file `composer.json`:

	{
		"require": {
			"guzzlehttp/guzzle": "~6.0"
		}
	}

If you are not familiar with Composer, the PHP package manager, [click here](https://getcomposer.org/).

Troubleshooting
---------------

If you get the following error when executing the sample:

> cURL error 60: SSL certificate problem: unable to get local issuer certificate

It means you don't have the CURLOPT_CAINFO option configured in your php.ini file. This option tells PHP to look in a certain PEM file for a list
of the trusted root certificates. One file commonly used can be found [here](http://curl.haxx.se/ca/cacert.pem).

You can choose other files or, if you're using Windows Server, you can even
[generate one yourself](http://www.swiftsoftwaregroup.com/configuring-phpcurl-root-certificates-windows-server/)
based on the OS's trusted certificate roots.
	
Whatever file you use, you must save on on the server and then update your `php.ini` file:

	curl.cainfo=path-to-your-pem-file
	
