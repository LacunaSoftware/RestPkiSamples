REST PKI Ruby Sample
====================
This folder contains a web application written in Ruby using the Rails framework, that shows how to use the 
[REST PKI service](https://pki.rest/).

For other languages, please visit the [repository root](https://github.com/LacunaSoftware/RestPkiSamples).

To run the sample, you will need an **API access token**. If you don't have one, register on the 
[REST PKI website](https://pki.rest/) and generate a token.

Running the sample
------------------
To run the sample:
1. [Download the project](https://github.com/LacunaSoftware/RestPkiSamples/archive/master.zip)
    or clone the repository
2. Generate an API access token on the [REST PKI website](https://pki.rest/)
3. Paste your access token on the file `restpki.rb` on Rails initializers folder
4. Install dependencies: `bundle intall`
5. Run application: `rails server`
6. Access the URL [http://localhost:3000](http://localhost:3000)

See also
--------
* [Test certificates](../TestCertificates.md)
* [Samples in other programming languages](https://github.com/LacunaSoftware/RestPkiSamples)
