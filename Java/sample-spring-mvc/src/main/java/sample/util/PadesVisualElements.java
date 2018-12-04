package sample.util;

import com.lacunasoftware.restpki.*;
import sample.util.Util;

import java.awt.*;
import java.io.IOException;

public class PadesVisualElements {

	// This method is called by the PAdES samples. It contains an example of signature visual
	// representation. This is only in a separate in order to organize the various examples.
	public static PadesVisualRepresentation getVisualRepresentation() throws IOException, RestException {

		// Create a visual representation.
		PadesVisualRepresentation visualRepresentation = new PadesVisualRepresentation();

		// For a full list of supported tags, see:
		// https://github.com/LacunaSoftware/RestPkiSamples/blob/master/PadesTags.md
		PadesVisualText text = new PadesVisualText("Signed by {{name}} ({{national_id}})");
		text.setFontSize(13.0);
		// Specify that the signing time should also be rendered.
		text.setIncludeSigningTime(true);
		// Optionally, set the horizontal alignment of the text. If not set, the default is Left.
		text.setHorizontalAlign(PadesTextHorizontalAlign.Left);
		// Optionally, set the container within the signature rectangle on which to place the text.
		// By default, the text can occupy the entire rectangle (how much of the rectangle the text
		// will actually fill depends on the length and font size). Below, we specify that the text
		// should respect a right margin of 1.5 cm.
		PadesVisualRectangle container = new PadesVisualRectangle();
		container.setLeft(0.2);
		container.setTop(0.2);
		container.setRight(0.2);
		container.setBottom(0.2);
		text.setContainer(container);
		visualRepresentation.setText(text);

		PadesVisualImage image = new PadesVisualImage(Util.getPdfStampContent(), "image/png");
		// Align image to the right horizontally.
		image.setHorizontalAlign(PadesHorizontalAlign.Right);
		// Align image to the center vertically.
		image.setVerticalAlign(PadesVerticalAlign.Center);
		visualRepresentation.setImage(image);

		// Position of the visual representation. We get the footnote position preset and customize
		// it.
		PadesVisualAutoPositioning visualPositioning = PadesVisualPositioning.getFootnote(Util.getRestPkiClient());
		visualPositioning.getContainer().setHeight(4.94);
		visualPositioning.getSignatureRectangleSize().setWidth(8.0);
		visualPositioning.getSignatureRectangleSize().setHeight(4.94);
		visualRepresentation.setPosition(visualPositioning);

		return visualRepresentation;
	}

	// This method is called by actions that use PadesSignatureStarter or PadesSignatureStarter2.
	// It contains examples of PDF marks, visual elements of arbitrary content placed in every
	// page. This code is only in a separate function in order to organize the various examples,
	// you can pick the one that best suits your needs and use it below directly without an
	// encapsulating function.
	public static PdfMark getPdfMark(int sampleNumber) {
		PdfMark mark;
		PdfMarkImageElement elementImage;
		PdfMarkTextElement elementText;
		PdfTextSection section;

		switch (sampleNumber) {
			case 1:
				// Example #1: A sample text and image are placed at the bottom of every page.
				// First, we create the mark object. It contains no elements, being a simple empty
				// box.
				mark = new PdfMark();

				// Here, we set the mark's position in every page.
				mark.setContainer(new PadesVisualRectangle());
				// Specifying the width (but no left nor right) results in a horizontally centered
				// fixed-width container.
				mark.getContainer().setWidth(8.0);
				// Specifying bottom and height (but no top) results in a bottom-aligned
				// fixed-height container.
				mark.getContainer().setBottom(0.2);
				mark.getContainer().setHeight(0.6);
				// This example has no background and no borders, so we don't set BackgroundColor
				// nor BorderColor.

				// First, the image.
				elementImage = new PdfMarkImageElement();
				// We'll position it to the right of the text.
				elementImage.setRelativeContainer(new PadesVisualRectangle());
				// Specifying right and width (but no left) results in a right-aligned fixed-width
				// container.
				elementImage.getRelativeContainer().setRight(0.0);
				elementImage.getRelativeContainer().setWidth(1.0);
				// Specifying top and bottom (but no height) results in a variable-height container
				// with the given margins.
				elementImage.getRelativeContainer().setTop(0.0);
				elementImage.getRelativeContainer().setBottom(0.0);

				// We'll use the image at 'content/PdfStamp.png'.
				try {
					elementImage.setImage(new PdfMarkImage(Util.getPdfStampContent(), "image/png"));
				} catch (IOException e) {
					throw new RuntimeException("Error trying to recovery the PDF stamp");
				}
				// Opacity is an double from 0 to 255 (0 is completely transparent, 255 is
				// completely opaque).
				elementImage.getImage().setAlpha(190);
				mark.addElement(elementImage);

				// Then, the text.
				elementText = new PdfMarkTextElement();
				// We center the text.
				elementText.setRelativeContainer(new PadesVisualRectangle());
				// Specifying left and right (but no width) results in a variable-width container
				// with the given margins.
				elementText.getRelativeContainer().setLeft(1.0);
				elementText.getRelativeContainer().setRight(0.0);
				// Specifying just the height results in a vertically centered fixed-height
				// container.
				elementText.getRelativeContainer().setHeight(0.5);

				// First, a simple message.
				section = new PdfTextSection();
				// We set the text.
				section.setText("This document was digitally signed with ");
				// Its color.
				section.setColor(Color.BLACK);
				// Its size.
				section.setFontSize(8.0);
				// And the style.
				section.setStyle(PdfTextStyle.Bold);

				// Then add the second text section.
				elementText.addTextSection(section);
				mark.addElement(elementText);

				return mark;

			case 2:
				// Example #2: An image will be placed at the bottom of every page.
				// First, we create the mark object. It contains no elements, being a simple empty
				// box.
				mark = new PdfMark();

				// Then, we set the mark's position in every page.
				mark.setContainer(new PadesVisualRectangle());
				// Specifying right and width (but no left) results in a right-aligned fixed-width
				// container.
				mark.getContainer().setRight(1.0);
				mark.getContainer().setWidth(2.54);
				// Specifying bottom and height (but no top) results in a bottom-aligned
				// fixed-height container.
				mark.getContainer().setBottom(1.0);
				mark.getContainer().setHeight(2.54);
				// After that, its border must be configured.
				mark.setBorderWidth(0.02);
				mark.setBorderColor(Color.BLACK);

				// Add a single image element.
				elementImage = new PdfMarkImageElement();
				// We center the text.
				elementImage.setRelativeContainer(new PadesVisualRectangle());
				// We'll make the image fill the entire mark, leaving space for the border.
				elementImage.getRelativeContainer().setLeft(0.1);
				elementImage.getRelativeContainer().setRight(0.1);
				elementImage.getRelativeContainer().setTop(0.1);
				elementImage.getRelativeContainer().setBottom(0.1);

				// We'll use the 'Content/PdfStamp.png' as background.
				try {
					elementImage.setImage(new PdfMarkImage(Util.getPdfStampContent(), "image/png"));
				} catch (IOException e) {
					throw new RuntimeException("Error trying to recovery the PDF stamp");
				}
				// Opacity is an double from 0 to 255 (0 is completely transparent, 255 is
				// completely opaque).
				elementImage.getImage().setAlpha(128);

				mark.addElement(elementImage);

				return mark;

			case 3:
				// Example #3: 'Signed with RestPKI' is printed at the top of every page in a blue
				// horizontal bar.
				// First, we create the mark object. It contains no elements, being a simple empty
				// box.
				mark = new PdfMark();
				// Then, we set the mark's position in every page.
				mark.setContainer(new PadesVisualRectangle());
				// Specifying left and right (but no width) results in a variable-width container
				// with the given margins.
				mark.getContainer().setLeft(0.0);
				mark.getContainer().setRight(0.0);
				// Specifying top and height (but no bottom) results in a top-aligned fixed-height
				// container.
				mark.getContainer().setTop(0.5);
				mark.getContainer().setHeight(1.0);
				// We'll not need a border, just a background color.
				mark.setBackgroundColor(new Color(0, 128, 192, 128));

				// Add a single text element.
				elementText = new PdfMarkTextElement();
				// We center the text.
				elementText.setRelativeContainer(new PadesVisualRectangle());
				// Specifying just the width results in a horizontally centered fixed-width
				// container.
				elementText.getRelativeContainer().setWidth(5.0);
				// Specifying just the height results in a vertically centered fixed-height
				// container.
				elementText.getRelativeContainer().setHeight(1.0);

				// This example has a single section.
				section = new PdfTextSection();
				// We set the text.
				section.setText("Signed with RestPKI");
				// Its color.
				section.setColor(Color.WHITE);
				// Its size.
				section.setFontSize(12.0);
				// And the style.
				section.setStyle(PdfTextStyle.Bold);

				// Then add the text section.
				elementText.addTextSection(section);
				mark.addElement(elementText);

				return mark;

			case 4:
				// Example #4: Same as example #3, but written vertically on the right side of
				// each page. First, we create the mark object. It contains no elements, being a
				// simple empty box.
				mark = new PdfMark();
				// Then, we set the mark's position in every page.
				mark.setContainer(new PadesVisualRectangle());
				// Specifying right and width (but no left) results in a right-aligned fixed-width
				// container.
				mark.getContainer().setRight(0.5);
				mark.getContainer().setWidth(1.0);
				// Specifying top and bottom (but no height) results in a variable-height container
				// with the given margins.
				mark.getContainer().setTop(0.0);
				mark.getContainer().setBottom(0.0);
				// We'll not need a border, just a background color.
				mark.setBackgroundColor(new Color(0, 128, 192, 127));

				// Add a single text element.
				elementText = new PdfMarkTextElement();
				// We center the text.
				elementText.setRelativeContainer(new PadesVisualRectangle());
				// Specifying just the height (but not top or bottom) results in a vertically
				// centered fixed-height container.
				elementText.getRelativeContainer().setHeight(5.0);
				// Specifying just the width (but not left or right) results in a horizontally
				// centered fixed-width container.
				elementText.getRelativeContainer().setWidth(1.0);
				// 90 degrees rotation (counter clockwise).
				elementText.setRotation(90);

				// This example has a single section.
				section = new PdfTextSection();
				// We set the text.
				section.setText("Signed with RestPKI");
				// Its color.
				section.setColor(Color.WHITE);
				// Its size.
				section.setFontSize(12.0);
				// And the style.
				section.setStyle(PdfTextStyle.Bold);

				// Then add the text section.
				elementText.addTextSection(section);
				mark.addElement(elementText);

				return mark;

			default:
				return null;

		}
	}
}
