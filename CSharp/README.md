REST PKI C# Samples
===================

This folder contains web applications written in C# that show how to use the
[REST PKI service](https://pki.rest/).

For other languages, please visit the [repository root](https://github.com/LacunaSoftware/RestPkiSamples).

To run the samples, you will need an **API access token**. If you don't have one, register on the
[REST PKI website](https://pki.rest/) and generate a token.

Default sample (ASP.NET MVC)
----------------------------

A sample using **ASP.NET MVC** can be found in the folder [MVC](MVC/).

Steps to execute the sample:

1. [Download the project](https://github.com/LacunaSoftware/RestPkiSamples/archive/master.zip)
   or clone the repository

2. Open the desired project folder -- [MVC](MVC/) or one of the other projects (see below)

3. Open the solution file (.sln) on Visual Studio
   
4. Generate an API access token on the [REST PKI website](https://pki.rest/)

5. Paste your access token on the file `web.config`
   
6. Run the solution. Make sure your system allows automatic Nuget package restore (if it doesn't,
   manually restore the packages).

ASP.NET Web Forms sample
------------------------

A sample using **ASP.NET Web Forms** can be found in the folder [WebForms](WebForms/).
The steps to execute the sample are the same as for the default sample.

ASP.NET Core Web application sample
-----------------------------------

A sample using the new **ASP.NET Core** technology can be found in the folder [AspNetCore](AspNetCore/).
See the folder's [README](AspNetCore/README.md) for more information.

Visual Studio 2008 sample
-------------------------

If you use an older version of Visual Studio which cannot open the MVC and the Web Forms sample, use the
project for **Visual Studio 2008** located in the folder [VS2008](VS2008/). The steps to execute the sample
are the same as for the default sample.

Windows Presentation Foundation (WPF) sample
--------------------------------------------

A sample .NET desktop application using the Windows Presentation Foundation (WPF) technology is available
on the folder [WPF](WPF/). The steps to execute the sample are similar to the default sample, with the
difference that the API access token is located on the file `Util.cs`.

.NET client lib
---------------

The samples use the Nuget package [Lacuna.RestPki.Client](https://www.nuget.org/packages/Lacuna.RestPki.Client/),
a library which encapsulates the API calls to REST PKI. It supports .NET Frameworks 3.5, 4.0 and 4.5 as well as
.NET Standard 1.3 (for usage on .NET Core, Xamarin, Mono and UWP).

See also
--------

* [Test certificates](../TestCertificates.md)
* [Samples in other programming languages](https://github.com/LacunaSoftware/RestPkiSamples)
* [REST PKI .NET client lib on Nuget](https://www.nuget.org/packages/Lacuna.RestPki.Client)
