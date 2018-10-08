REST PKI Java Samples
=====================

This folder contains web applications written in Java that show how to use the
[REST PKI service](https://pki.rest/).

For other languages, please visit the [repository root](https://github.com/LacunaSoftware/RestPkiSamples).

To run the samples, you will need an **API access token**. If you don't have one, register on the
[REST PKI website](https://pki.rest/) and generate a token.

Default sample
--------------

A sample for a Java web application using the Spring MVC framework can be found in the folder
[sample-spring-mvc](sample-spring-mvc/). The sample uses spring boot to provide a self-contained web application,
not requiring a web server installed. The only requirement is **having a JDK installed**.

To run this project, you can use the following tools:

**Using Gradle**

1. [Download the project](https://github.com/LacunaSoftware/RestPkiSamples/archive/master.zip)
   or clone the repository

1. Generate an API access token on the [REST PKI website](https://pki.rest/)

1. Paste your access token on the file `Java/sample-spring-mvc/src/main/resources/application.properties`
   
1. In a command prompt, navigate to the folder `Java/sample-spring-mvc` and run the command
   `gradlew bootRun` (on Linux `./gradlew bootRun`). If you are using Windows, you can alternatively
   double-click the file `Run-Sample.bat`.
  
1. Once you see the message "Started Application in x.xxx seconds" (the on-screen percentage
   will *not* reach 100%), open a web browser and go the URL http://localhost:60963
   
> If you are on Linux, you may have to add the execution permission to *gradrew* file by executing the command
`chmod +x gradlew`.

**Using Maven**

1. [Download the project](https://github.com/LacunaSoftware/RestPkiSamples/archive/master.zip)
   or clone the repository

1. Generate an API access token on the [REST PKI website](https://pki.rest/)

1. Paste your access token on the file `Java/sample-spring-mvc/src/main/resources/application.properties`

1. In a command prompt, navigate to the folder `Java/sample-spring-mvc` and run the command
   `mvn spring-boot:run`. To run this command, it's necessary to have the Apache Maven installed.
   
1. Once you see the message "Started Application in x.xxx seconds", open a web browser and go the URL
   http://localhost:60963
   
On-premises installations with restricted access
------------------------------------------------

If you want to use the functionality of REST PKI but are not comfortable or cannot use it as a cloud service,
you can also host it yourself, which is called an "on-premises installation".

On-premises installations can be publicly accessible or not, depending on how you install the product on your
environment. We recommend that you make the installation publicly accessible, because that simplifies your
application's code, since that way the Web Pki component running in your users' browsers can communicate
directly with your REST PKI installation, which simplifies your application code. In this case, you should write
your code based on the default sample (see above).

However, if your REST PKI installation must have restricted access, please write your code based on the
sample contained in the folder [on-premises-restricted](on-premises-restricted/). The steps to execute the
sample are the same as for the default sample.

Java 6 sample
-------------

If you want to use an older version than Java 7, please use the sample project for Java 6, which can be found 
in the folder [spring-mvc-java6](spring-mvc-java6/). This sample uses another library exclusively made to work 
with Java 6 (see [Client lib for Java 6](#client-lib-for-java-6) section below). The steps to execute the sample are
the same as for the default sample, except for the URL to access the sample, which is http://localhost:60458 in this
sample. If you want to use Java 7 or greater, we recommend using the [Default sample](#default-sample).
   
Opening the samples on Eclipse or IDEA
--------------------------------------

To open one of the samples on Eclipse, run `gradlew eclipse` on the sample's folder and then
then import the project from Eclipse.

To open one of the samples on IntelliJ IDEA, run `gradlew idea` on the sample's folder
and then use the "Open" funcionality inside IDEA (works better than "Import").

Client lib for Java 7 or greater
---------------------------------

The samples use a client lib which encapsulates the API calls to REST PKI.
The lib should be **referenced as a dependency**, as can be seen in the file [build.gradle](sample-spring-mvc/build.gradle)
of each sample:

	repositories {
		mavenCentral()
		maven {
			url  "http://dl.bintray.com/lacunasoftware/maven" 
		}
	} 

	dependencies {
		compile("com.lacunasoftware.restpki:restpki-client:1.9.2")
	}

If you project uses Maven, please refer to the file [pom.xml](sample-spring-mvc/pom.xml) instead:

	<dependencies>
		...
		<dependency>
			<groupId>com.lacunasoftware.restpki</groupId>
			<artifactId>restpki-client</artifactId>
			<version>1.9.2</version>
		</dependency>
		...
	</dependencies>
	...
	<repositories>
		<repository>
			<id>lacuna.repository</id>
			<name>lacuna repository</name>
			<url>http://dl.bintray.com/lacunasoftware/maven</url>
		</repository>
	</repositories>

If your project uses another tool for dependency resolution (e.g. Ivy), please visit the
[package page on BinTray](https://bintray.com/lacunasoftware/maven/restpki-client) and click on
the link "SET ME UP!".

Client lib for Java 6
---------------------

The samples use a client lib which encapsulates the API calls to REST PKI.
The lib should be **referenced as a dependency**, as can be seen in the file [build.gradle](spring-mvc-java6/build.gradle)
of each sample:

	repositories {
		mavenCentral()
		maven {
			url  "http://dl.bintray.com/lacunasoftware/maven" 
		}
	}

	dependencies {
		compile("com.lacunasoftware.restpki:restpki-client-java6:1.9.0")
	}

If you project uses Maven, please refer to the file [pom.xml](spring-mvc-java6/pom.xml) instead:

	<dependencies>
		...
		<dependency>
			<groupId>com.lacunasoftware.restpki</groupId>
			<artifactId>restpki-client-java6</artifactId>
			<version>1.9.0</version>
		</dependency>
		...
	</dependencies>
	...
	<repositories>
		<repository>
			<id>lacuna.repository</id>
			<name>lacuna repository</name>
			<url>http://dl.bintray.com/lacunasoftware/maven</url>
		</repository>
	</repositories>

If your project uses another tool for dependency resolution (e.g. Ivy), please visit the
[package page on BinTray](https://bintray.com/lacunasoftware/maven/restpki-client-java6) and click on
the link "SET ME UP!".

Using a proxy server
--------------------

If your environment requires you to use a proxy server in order to access external resources,
follow these steps:

1. Create a file named `gradle.properties` on the project's folder (same folder as the `gradle.build` file)
2. Paste the following code on the file, setting the appropriate values (remove the lines regarding username
   and password if your proxy server does not require authentication):
```
systemProp.http.proxyHost=www.somehost.org
systemProp.https.proxyHost=www.somehost.org
systemProp.http.proxyPort=80
systemProp.https.proxyPort=80
systemProp.http.proxyUser=username
systemProp.https.proxyUser=username
systemProp.http.proxyPassword=password
systemProp.https.proxyPassword=password
systemProp.http.nonProxyHosts=localhost
systemProp.https.nonProxyHosts=localhost
```
3. Edit the file `Java/sample-spring-mvc/src/main/java/sample/util/Util.java` and uncomment the lines
   regarding proxy authentication.

**Known issue:** Gradle may fail to build the project returning error message "Received status code 407 from
server: Proxy authorization required" if your proxy server requires authentication AND if it supports NTLM
authentication AND if you're trying to authenticate with basic (non-NTLM) credentials. In this case, either
use NTLM credentials or disable NTLM authentication on your proxy server.

Troubleshooting
---------------

If you are using a Java version prior to 7u75 or 8u31, you may get an error saying:

	REST action POST: https://pki.rest/Api/xxxxx unreachable
	
This happens because the root CA certificate of our SSL certificate chain was only added to the Java
trusted root certificates on the aforementioned versions. To fix this, update your Java to a current version.

If you don't wish to update your Java, you may alter the file `Java/sample-spring-mvc/src/main/java/sample/util/Util.java`
and switch the REST PKI address to "http://pki.rest/" (with "http" instead of "https"). However, this fix
should only be used while on development, since your API access token, as well as the documents you sign,
will be sent to REST PKI unencrypted.

See also
--------

* [Test certificates](../TestCertificates.md)
* [Samples in other programming languages](https://github.com/LacunaSoftware/RestPkiSamples)
* [Javadoc for the Java client lib](https://docs.lacunasoftware.com/content/javadocs/restpki-client/)
