REST PKI PHP Samples
====================

This folder contains web applications written in PHP that show how to use the
[REST PKI service](https://pki.rest/).

For other languages, please visit the [repository root](https://github.com/LacunaSoftware/RestPkiSamples).

To run the samples, you will need an **API access token**. If you don't have one, register on the
[REST PKI website](https://pki.rest/) and generate a token.

You should choose between one of the sample projects in this folder based on your PHP version:

* PHP 5.5+: [Standard sample](#standard-sample)
* PHP 5.3 and 5.4: [Legacy sample for PHP 5.3 and 5.4](#legacy-sample-for-php-53-and-54)
* PHP 5.2: [Legacy sample for PHP 5.2](#legacy-sample-for-php-52)

Standard sample
---------------

The standard PHP sample can be found in the folder [standard](standard/). To run the sample:

1. [Download the project](https://github.com/LacunaSoftware/RestPkiSamples/archive/master.zip)
   or clone the repository

2. Generate an API access token on the [REST PKI website](https://pki.rest/)

3. Paste your access token on the file `util.php`

4. In a command prompt, navigate to the folder `PHP/standard` and run the command
   `composer install` to download the dependencies (if you don't have Composer installed, get it [here](https://getcomposer.org/))
   
5. Setup a website on your local HTTP server pointing to the PHP folder
  
6. Open the index.php file on the browser on the corresponding URL (depending on the previous step)

Notice: the standard sample requires **PHP 5.5+**. If you're using another version of PHP, please see
below.

Legacy sample for PHP 5.3 and 5.4
---------------------------------

The folder [legacy](legacy/) contains a sample which will run in PHP 5.3+. This should only be used
if you're using PHP version 5.3 or 5.4.

The steps to execute the sample are the same as with the standard sample.

Legacy sample for PHP 5.2
---------------------------------

The folder [legacy52](legacy52/) contains a sample which will run in PHP 5.2. This should only be used
if you're using PHP version 5.2.

The steps to execute the sample are the same as with the standard sample.

Dependencies
------------

The sample projects depend on [Rest PKI client lib for PHP](https://github.com/LacunaSoftware/RestPkiPhpClient) library, which in turn requires **PHP 5.5** or
greater (with the exception of the legacy samples, which depends on other older libraries that still support
older versions of PHP).

This dependency is specified in the file `composer.json`:

	{
		"require": {
			"lacuna/restpki-client": "^2.1.0"
		}
	}


Secure communication with REST PKI (HTTPS)
------------------------------------------

For simplification purposes, the sample projects communicate with REST PKI using plain HTTP (unencrypted communcation).
However, in production code it is essential to use HTTPS to communicate with REST PKI, otherwise your API access token
and your documents will be sent in the open.

To do that, you should edit the file `util.php`, commenting the line with the HTTP url and uncommenting the line with the HTTPS url:

	// -----------------------------------------------------------------------------------------------------------
	// IMPORTANT NOTICE: in production code, you should use HTTPS to communicate with REST PKI, otherwise your API
	// access token, as well as the documents you sign, will be sent to REST PKI unencrypted.
	// -----------------------------------------------------------------------------------------------------------
	//$restPkiUrl = 'http://pki.rest/';
	$restPkiUrl = 'https://pki.rest/'; // <--- USE THIS IN PRODUCTION!

Once you've done this, you might start seeing the following error when executing your code:

> An error has occurred on the server: cURL error 60: SSL certificate problem: unable to get local issuer certificate (see http://curl.haxx.se/libcurl/c/libcurl-errors.html)

That means your PHP is not configured with a list of trusted root certification authorities, which is necessary to
establish a secure SSL connection with REST PKI. To fix this, follow these steps:

1. Download the file [http://curl.haxx.se/ca/cacert.pem](http://curl.haxx.se/ca/cacert.pem) and save it somewhere in your PHP server (for instance `C:\Program Files (x86)\PHP\cacert.pem`)

2. Edit the `php.ini` file

3. Locate the `[curl]` section

4. Add the following line (change the path accordingly):

		curl.cainfo = "C:\Program Files (x86)\PHP\cacert.pem"
	
You don't necessarily need to use the PEM file specified on step 1, there are other options such as
[generating a PEM file yourself](http://www.swiftsoftwaregroup.com/configuring-phpcurl-root-certificates-windows-server/)
based on the OS's trusted certificate roots.

See also
--------

* [Test certificates](../TestCertificates.md)
* [Samples in other programming languages](https://github.com/LacunaSoftware/RestPkiSamples)
