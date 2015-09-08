REST PKI Samples
================

This project contains sample web applications demonstrating the use of the REST PKI service in
different programming languages.

To run the samples, you will need an API access token. If you don't have one, register on the
REST PKI website and generate a token.

	https://restpki.lacunasoftware.com/

Java sample using Spring MVC
----------------------------

A sample for a Java web application using the Spring MVC framework can be found in the folder
Java/sample-spring-mvc. The sample uses spring boot to provide a self-contained web application,
not requiring a web server installed. The only requirement is having a JDK installed.

Steps to execute the sample:

1. Edit the file Java/sample-spring-mvc/src/main/java/sample/Util.java and paste your access
   token.
   
2. Navigate to the folder Java/sample-spring-mvc and run the command "gradlew run". If you are
   using Windows, you can alternatively execute the Run-Sample.bat file.
  
3. Once you see the message "Started Application in x.xxx seconds" (the on-screen percentage
   will NOT reach 100%), open a web browser and type in the URL http://localhost:8080/
   
In order to open the project on IntelliJ IDEA, run "gradlew idea" and then use the "Open"
funcionality inside IDEA (works better than "Import"). In order to open the project on Eclipse,
run "gradlew eclipse" and then import the project from Eclipse.

The source code is thoroughly documented, explaining in detail the calls to REST PKI and
also the integration with the Web PKI component for performing the necessary client-side
computations.

Java client SDK
---------------

For applications in Java, a JAR is available which encapsulates the REST calls to REST PKI.
The JAR should be referenced as a dependency, as can be seen in the file build.gradle of the
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

If your project uses Maven or Ivy to resolve dependencies, please visit the following URL
for instructions on how to include Lacuna Software's BinTray repository on your build file.

	https://bintray.com/lacunasoftware/maven/restpki-client

The documentation for the Java client SDK can be found at

	https://restpki.lacunasoftware.com/Content/docs/java-client/
