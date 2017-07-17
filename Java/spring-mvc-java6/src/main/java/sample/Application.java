package sample;

import org.springframework.context.annotation.Configuration;
import org.springframework.context.annotation.ComponentScan;
import org.springframework.boot.autoconfigure.EnableAutoConfiguration;
import org.springframework.boot.SpringApplication;
import sample.util.Util;

import java.io.File;
import java.io.IOException;

@Configuration
@EnableAutoConfiguration
@ComponentScan
public class Application {

	private static File tempFolderPath;

	public static File getTempFolderPath() {
		return tempFolderPath;
	}

	public static void main(String[] args) throws IOException {

		// Temporary folder used to store uploaded files and signed PDFs and CMSs. The use of a temporary directory is
		// solely for simplification purposes. In actual production code, the storage would typically be performed by your
		// application's database.
		tempFolderPath = Util.createTempDirectory("RestPkiSample");

		SpringApplication.run(Application.class, args);
	}

}
