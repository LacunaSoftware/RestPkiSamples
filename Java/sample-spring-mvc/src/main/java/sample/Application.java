package sample;

import org.springframework.boot.SpringApplication;
import org.springframework.boot.autoconfigure.SpringBootApplication;

import java.io.IOException;
import java.nio.file.Files;
import java.nio.file.Path;

@SpringBootApplication
public class Application {

	private static Path tempFolderPath;

	public static Path getTempFolderPath() {
		return tempFolderPath;
	}

	public static void main(String[] args) throws IOException {

		// Temporary folder used to store uploaded files and signed PDFs and CMSs. The use of a temporary directory is
		// solely for simplification purposes. In actual production code, the storage would typically be performed by your
		// application's database.
		tempFolderPath = Files.createTempDirectory("RestPkiSample");

		SpringApplication.run(Application.class, args);
	}

}
