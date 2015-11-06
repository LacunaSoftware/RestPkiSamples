REST PKI C# Sample
===================

This is the C# sample for a web application using REST PKI.

For other languages, please visit the [project root](https://github.com/LacunaSoftware/RestPkiSamples).

Running the sample
------------------

1. [Download the project](https://github.com/LacunaSoftware/RestPkiSamples/archive/master.zip)
   or clone the repository

2. Open the solution file `RestPkiCSharpSample.sln` on Visual Studio
   
3. Generate an API access token on the [REST PKI website](https://pki.rest/)

4. Paste your access token on the file `SampleSite\web.config`
   
5. Run the solution. Make sure your system allows automatic Nuget package restore (if it doesn't,
   manually restore the packages).

.NET client lib
---------------

The samples use a client lib which encapsulates the API calls to REST PKI. The lib is a Nuget package
targeting .NET Framework 4.5. If you need to support .NET Framework 4.0, please [contact us](https://webpki.lacunasoftware.com/#/Contact).

See also:

* [REST PKI .NET client lib on Nuget](https://www.nuget.org/packages/Lacuna.RestPki.Client)
