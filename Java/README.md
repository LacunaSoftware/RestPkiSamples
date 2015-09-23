REST PKI Java Samples
=====================

This folder contains web applications written in Java that show how to use the
[REST PKI service](https://restpki.lacunasoftware.com/).

For other languages, please visit the [project root](https://github.com/LacunaSoftware/RestPkiSamples).

To run the samples, you will need an **API access token**. If you don't have one, register on the
[REST PKI website](https://restpki.lacunasoftware.com/) and generate a token.

Java sample using Spring MVC
----------------------------

A sample for a Java web application using the Spring MVC framework can be found in the folder
[sample-spring-mvc](sample-spring-mvc/). The sample uses spring boot to provide a self-contained web application,
not requiring a web server installed. The only requirement is **having a JDK installed**.

Steps to execute the sample:

1. [Download the project](https://github.com/LacunaSoftware/RestPkiSamples/archive/master.zip)
   or clone the repository

2. Generate an API access token on the [REST PKI website](https://restpki.lacunasoftware.com/)

3. Paste your access token on the file `Java/sample-spring-mvc/src/main/java/sample/util/Util.java`
   
4. In a command prompt, navigate to the folder `Java/sample-spring-mvc` and run the command
   `gradlew run` (on Linux `./gradlew run`). If you are using Windows, you can alternatively
   double-click the file `Run-Sample.bat`.
  
5. Once you see the message "Started Application in x.xxx seconds" (the on-screen percentage
   will *not* reach 100%), open a web browser and type in the URL http://localhost:8080/
   
Opening the project on Eclipse or IDEA
--------------------------------------

To open the project on Eclipse, run `gradlew eclipse` on the folder `sample-spring-mvc` and
then import the project from Eclipse.

To open the project on IntelliJ IDEA, run `gradlew idea` on the folder `sample-spring-mvc`
and then use the "Open" funcionality inside IDEA (works better than "Import").

Java client lib
---------------

A client lib in Java is available which encapsulates the REST calls to REST PKI.
The lib should be referenced as a dependency, as can be seen in the file build.gradle of the
Spring MVC sample:

	repositories {
		mavenCentral()
		maven {
			url  "http://dl.bintray.com/lacunasoftware/maven" 
		}
	} 

	dependencies {
		compile("com.lacunasoftware.restpki:restpki-client:1.0.0")
	}

And also on the file pom.xml in the same folder:

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

If your project uses Ivy to resolve dependencies, please visit the [package page on BinTray](https://bintray.com/lacunasoftware/maven/restpki-client)
and click on the link "SET ME UP!".

See also:

* [Javadoc for the Java client lib](https://restpki.lacunasoftware.com/Content/docs/java-client/)
