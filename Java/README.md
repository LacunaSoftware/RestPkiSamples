REST PKI Java Samples
=====================

This folder contains web applications written in Java that show how to use the
[REST PKI service](https://pki.rest/).

For other languages, please visit the [project root](https://github.com/LacunaSoftware/RestPkiSamples).

To run the samples, you will need an **API access token**. If you don't have one, register on the
[REST PKI website](https://pki.rest/) and generate a token.

Default sample
--------------

A sample for a Java web application using the Spring MVC framework can be found in the folder
[sample-spring-mvc](sample-spring-mvc/). The sample uses spring boot to provide a self-contained web application,
not requiring a web server installed. The only requirement is **having a JDK installed**.

Steps to execute the sample:

1. [Download the project](https://github.com/LacunaSoftware/RestPkiSamples/archive/master.zip)
   or clone the repository

2. Generate an API access token on the [REST PKI website](https://pki.rest/)

3. Paste your access token on the file `Java/sample-spring-mvc/src/main/java/sample/util/Util.java`
   
4. In a command prompt, navigate to the folder `Java/sample-spring-mvc` and run the command
   `gradlew run` (on Linux `./gradlew run`). If you are using Windows, you can alternatively
   double-click the file `Run-Sample.bat`.
  
5. Once you see the message "Started Application in x.xxx seconds" (the on-screen percentage
   will *not* reach 100%), open a web browser and go the URL [http://localhost:8080/](http://localhost:8080/)
   
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
   
Opening the samples on Eclipse or IDEA
--------------------------------------

To open one of the samples on Eclipse, run `gradlew eclipse` on the sample's folder and then
then import the project from Eclipse.

To open one of the samples on IntelliJ IDEA, run `gradlew idea` on the sample's folder
and then use the "Open" funcionality inside IDEA (works better than "Import").

Java client lib
---------------

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
		compile("com.lacunasoftware.restpki:restpki-client:1.0.0")
	}

If you project uses Maven, please refer to the file [pom.xml](sample-spring-mvc/pom.xml) instead:

	<dependencies>
		...
		<dependency>
			<groupId>com.lacunasoftware.restpki</groupId>
			<artifactId>restpki-client</artifactId>
			<version>1.0.0</version>
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

See also:

* [Javadoc for the Java client lib](https://pki.rest/Content/docs/java-client/)
