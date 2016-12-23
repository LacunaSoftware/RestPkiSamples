REST PKI Python Sample
======================

This folder contains a web application written in Python using the Flask framework, that shows how to use the
[REST PKI service](https://pki.rest/). The sample application should work on Python versions 2.7 and 3.5.

For other languages, please visit the [repository root](https://github.com/LacunaSoftware/RestPkiSamples).

To run the samples, you will need an **API access token**. If you don't have one, register on the
[REST PKI website](https://pki.rest/) and generate a token.

Running the sample
------------------

To run the sample:

1. [Download the project](https://github.com/LacunaSoftware/RestPkiSamples/archive/master.zip)
   or clone the repository

2. Generate an API access token on the [REST PKI website](https://pki.rest/)

3. Paste your access token on the file `demo.py`
   
4. Install dependencies: `pip install -r requirements.txt`

5. Run application: `python demo.py`

6. Access the URL [http://localhost:5000](http://localhost:5000)

Optionally, you can create and activate a "virtualenv" to avoid mixing library versions:

    virtualenv env
    . ./env/bin/activate
    pip install -r requirements.txt
    python demo.py
    deactivate

See also
--------

* [Test certificates](../TestCertificates.md)
* [Samples in other programming languages](https://github.com/LacunaSoftware/RestPkiSamples)
