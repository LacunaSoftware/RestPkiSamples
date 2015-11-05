REST PKI Python Samples
=======================

This folder contains web applications written in Python that show how to use the
[REST PKI service](https://restpki.lacunasoftware.com/).

For other languages, please visit the [project root](https://github.com/LacunaSoftware/RestPkiSamples).

To run the samples, you will need an **API access token**. If you don't have one, register on the
[REST PKI website](https://restpki.lacunasoftware.com/) and generate a token.

Running the sample
------------------

To run the sample:

1. [Download the project](https://github.com/LacunaSoftware/RestPkiSamples/archive/master.zip)
   or clone the repository

2. Generate an API access token on the [REST PKI website](https://restpki.lacunasoftware.com/)

3. Paste your access token on the file `demo.py`
   
4. Optional: create and activate a virtualenv

```
$ virtualenv env
$ . ./env/bin/activate
```

5. Install dependencies

```
$ pip install -r requirements.txt
```

6. Run application

```
$ python demo.py
```

Notice: the sample is written for Python 2.7, but it should work on Python 3.x.

