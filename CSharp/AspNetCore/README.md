REST PKI samples in ASP.NET Core
================================

This folder contains an ASP.NET Core web application that shows how to use the [REST PKI service](https://pki.rest/).
The [REST PKI client package](https://www.nuget.org/packages/Lacuna.RestPki.Client/) is available for .NET Standard 1.3
and thus **is compatible with .NET Core 1.0 and 1.1** (as well as Xamarin, Mono and UWP).

To run the samples, you will need an **API access token**. If you don't have one, register on the
[REST PKI website](https://pki.rest/) and generate a token.

Running the sample
------------------

**The project can only be executed on [Visual Studio 2017](https://www.visualstudio.com/vs/visual-studio-2017/).**

1. [Download the project](https://github.com/LacunaSoftware/RestPkiSamples/archive/master.zip)
   or clone the repository

2. Open the solution file `CoreWebApp.sln` on Visual Studio 2017
   
3. Generate an API access token on the [REST PKI website](https://pki.rest/)

4. Paste your access token on the file `CoreWebApp\appsettings.json`
   
5. Run the solution. Make sure your system allows automatic Nuget package restore (if it doesn't,
   manually restore the packages).

See also
--------

* [Test certificates](../TestCertificates.md)
* [Samples in other C# technologies](../)
* [Samples in other programming languages](https://github.com/LacunaSoftware/RestPkiSamples)
* [REST PKI .NET client lib on Nuget](https://www.nuget.org/packages/Lacuna.RestPki.Client)
