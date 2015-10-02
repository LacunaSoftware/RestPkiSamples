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

> An error has occurred on the server: cURL error 60: SSL certificate problem: unable to get local issuer certificate (see http://curl.haxx.se/libcurl/c/libcurl-errors.html)

It means your PHP is not configured with a list of trusted root certification authorities, which is necessary to
establish a secure SSL connection with REST PKI. To fix this, follow these steps:

1. Download the file [http://curl.haxx.se/ca/cacert.pem](http://curl.haxx.se/ca/cacert.pem) and save it somewhere in your PHP server (for instance `C:\Program Files (x86)\PHP\cacert.pem`)

2. Edit the `php.ini` file

3. Locate the `[curl]` section

4. Add the following line (change the path accordingly):

		curl.cainfo = "C:\Program Files (x86)\PHP\cacert.pem"
	
You don't necessarily need to use the PEM file specified on step 1, there are other options such as
[generating a PEM file yourself](http://www.swiftsoftwaregroup.com/configuring-phpcurl-root-certificates-windows-server/)
based on the OS's trusted certificate roots.

**Workaround:** if you are having trouble fixing this issue, a workaround is to edit the file `PHP/api/util.php` and change the REST PKI
url to `http://pki.rest/` (use http instead of https). This is not recommended, however, because in this case the communcation
with REST PKI would be unencrypted.
