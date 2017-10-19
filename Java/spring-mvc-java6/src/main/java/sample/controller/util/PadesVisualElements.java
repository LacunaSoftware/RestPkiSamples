package sample.controller.util;

import com.lacunasoftware.restpki.*;
import sample.util.Util;

import java.awt.*;
import java.io.IOException;

public class PadesVisualElements {
    // This method is called by the get() method. It contains examples of signature visual representation positionings.
    public static PadesVisualPositioning getVisualRepresentationPosition(int sampleNumber) throws RestException {

        switch (sampleNumber) {

            case 1:
                // Example #1: automatic positioning on footnote. This will insert the signature, and future signatures,
                // ordered as a footnote of the last page of the document
                return PadesVisualPositioning.getFootnote(Util.getRestPkiClient());

            case 2:
                // Example #2: get the footnote positioning preset and customize it
                PadesVisualAutoPositioning footnotePosition = PadesVisualPositioning.getFootnote(Util.getRestPkiClient());
                footnotePosition.getContainer().setLeft(2.54);
                footnotePosition.getContainer().setBottom(2.54);
                footnotePosition.getContainer().setRight(2.54);
                return footnotePosition;

            case 3:
                // Example #3: automatic positioning on new page. This will insert the signature, and future signatures,
                // in a new page appended to the end of the document.
                return PadesVisualPositioning.getNewPage(Util.getRestPkiClient());

            case 4:
                // Example #4: get the "new page" positioning preset and customize it
                PadesVisualAutoPositioning newPagePos = PadesVisualPositioning.getNewPage(Util.getRestPkiClient());
                newPagePos.getContainer().setLeft(2.54);
                newPagePos.getContainer().setTop(2.54);
                newPagePos.getContainer().setRight(2.54);
                newPagePos.setSignatureRectangleSize(new PadesSize(5, 3));
                return newPagePos;

            case 5:
                // Example #5: manual positioning
                PadesVisualRectangle pos = new PadesVisualRectangle();
                // define a manual position of 5cm x 3cm, positioned at 1 inch from the left and bottom margins
                pos.setWidthLeftAnchored(5.0, 2.54);
                pos.setHeightBottomAnchored(3.0, 2.54);
                return new PadesVisualManualPositioning(
                        0, // Page number. Zero means the signature will be placed on a new page appended to the end of the document
                        PadesMeasurementUnits.Centimeters,
                        pos // reference to the manual position defined above
                );

            case 6:
                // Example #6: custom auto positioning
                PadesVisualRectangle container = new PadesVisualRectangle();
                // Specification of the container where the signatures will be placed, one after the other
                container.setHorizontalStretch(2.54, 2.54); // variable-width container with the given margins
                container.setHeightBottomAnchored(12.31, 2.54); // bottom-aligned fixed-height container
                return new PadesVisualAutoPositioning(
                        -1, // Page number. Negative values represent pages counted from the end of the document (-1 is last page)
                        PadesMeasurementUnits.Centimeters,
                        container, // Reference to the container defined above
                        new PadesSize(5.0, 3.0), // Specification of the size of each signature rectangle
                        1.0 // The signatures will be placed in the container side by side. If there's no room left, the signatures
                        // will "wrap" to the next row. This value specifies the vertical distance between rows
                );

            default:
                return null;
        }
    }

    // This function is called by PadesSignatureController and BatchSignatureController. It contains examples of PDF
    // marks, visual elements of arbitrary content placed in every page. This code is only in a separate function in
    // order to organize the various examples, you can pick the one that best suits your needs and use it below directly
    // without an encapsulating function.
    public static PdfMark getPdfMark(int sampleNumber) {
        PdfMark mark;
        PdfMarkImageElement elementImage;
        PdfMarkTextElement elementText;
        PdfTextSection section;

        switch (sampleNumber) {
            case 1:
                // Example #1: A sample text and image are placed at the bottom of every page.
                // First, we create the mark object. It contains no elements, being a simple empty box.
                mark = new PdfMark();

                // Here, we set the mark's position in every page.
                mark.setContainer(new PadesVisualRectangle());
                // Specifying the width (but no left nor right) results in a horizontally centered fixed-width container
                mark.getContainer().setWidth(8.0);
                // Specifying bottom and height (but no top) results in a bottom-aligned fixed-height container
                mark.getContainer().setBottom(0.2);
                mark.getContainer().setHeight(0.6);
                // This example has no background and no borders, so we don't set BackgroundColor nor BorderColor

                // First, the image
                elementImage = new PdfMarkImageElement();
                // We'll position it to the right of the text.
                elementImage.setRelativeContainer(new PadesVisualRectangle());
                // Specifying right and width (but no left) results in a right-aligned fixed-width container
                elementImage.getRelativeContainer().setRight(0.0);
                elementImage.getRelativeContainer().setWidth(1.0);
                // Specifying top and bottom (but no height) results in a variable-height container with the given margins
                elementImage.getRelativeContainer().setTop(0.0);
                elementImage.getRelativeContainer().setBottom(0.0);

                // We'll use the image at 'content/PdfStamp.png'.
                try {
                    elementImage.setImage(new PdfMarkImage(Util.getPdfStampContent(), "image/png"));
                } catch (IOException e) {
                    throw new RuntimeException("Error trying to recovery the PDF stamp");
                }
                // Opacity is an double from 0 to 255 (0 is completely transparent, 255 is completely opaque).
                elementImage.getImage().setAlpha(190);
                mark.addElement(elementImage);

                // Then, the text.
                elementText = new PdfMarkTextElement();
                // We center the text.
                elementText.setRelativeContainer(new PadesVisualRectangle());
                // Specifying left and right (but no width) results in a variable-width container with the given margins
                elementText.getRelativeContainer().setLeft(1.0);
                elementText.getRelativeContainer().setRight(0.0);
                // Specifying just the height results in a vertically centered fixed-height container
                elementText.getRelativeContainer().setHeight(0.5);

                // First, a simple message
                section = new PdfTextSection();
                // We set the text.
                section.setText("This document was digitally signed with ");
                // Its color
                section.setColor(Color.BLACK);
                // Its size
                section.setFontSize(8.0);
                // And the style
                section.setStyle(PdfTextStyle.Bold);

                // Then add the second text section.
                elementText.addTextSection(section);
                mark.addElement(elementText);

                return mark;

            case 2:
                // Example #2: An image will be placed at the bottom of every page.
                // First, we create the mark object. It contains no elements, being a simple empty box.
                mark = new PdfMark();

                // Then, we set the mark's position in every page.
                mark.setContainer(new PadesVisualRectangle());
                // Specifying right and width (but no left) results in a right-aligned fixed-width container
                mark.getContainer().setRight(1.0);
                mark.getContainer().setWidth(2.54);
                // Specifying bottom and height (but no top) results in a bottom-aligned fixed-height container
                mark.getContainer().setBottom(1.0);
                mark.getContainer().setHeight(2.54);
                // After that, its border must be configured.
                mark.setBorderWidth(0.02);
                mark.setBorderColor(Color.BLACK);

                // Add a single image element
                elementImage = new PdfMarkImageElement();
                // We center the text.
                elementImage.setRelativeContainer(new PadesVisualRectangle());
                // We'll make the image fill the entire mark, leaving space for the border
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
                // Opacity is an double from 0 to 255 (0 is completely transparent, 255 is completely opaque).
                elementImage.getImage().setAlpha(128);

                mark.addElement(elementImage);

                return mark;

            case 3:
                // Example #3: 'Signed with RestPKI' is printed at the top of every page in a blue horizontal bar.
                // First, we create the mark object. It contains no elements, being a simple empty box.
                mark = new PdfMark();
                // Then, we set the mark's position in every page.
                mark.setContainer(new PadesVisualRectangle());
                // Specifying left and right (but no width) results in a variable-width container with the given margins
                mark.getContainer().setLeft(0.0);
                mark.getContainer().setRight(0.0);
                // Specifying top and height (but no bottom) results in a top-aligned fixed-height container
                mark.getContainer().setTop(0.5);
                mark.getContainer().setHeight(1.0);
                // We'll not need a border, just a background color.
                mark.setBackgroundColor(new Color(0, 128, 192, 128));

                // Add a single text element.
                elementText = new PdfMarkTextElement();
                // We center the text.
                elementText.setRelativeContainer(new PadesVisualRectangle());
                // Specifying just the width results in a horizontally centered fixed-width container
                elementText.getRelativeContainer().setWidth(5.0);
                // Specifying just the height results in a vertically centered fixed-height container
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
                // Example #4: Same as example #3, but written vertically on the right side of each page
                // First, we create the mark object. It contains no elements, being a simple empty box.
                mark = new PdfMark();
                // Then, we set the mark's position in every page.
                mark.setContainer(new PadesVisualRectangle());
                // Specifying right and width (but no left) results in a right-aligned fixed-width container
                mark.getContainer().setRight(0.5);
                mark.getContainer().setWidth(1.0);
                // Specifying top and bottom (but no height) results in a variable-height container with the given margins
                mark.getContainer().setTop(0.0);
                mark.getContainer().setBottom(0.0);
                // We'll not need a border, just a background color.
                mark.setBackgroundColor(new Color(0, 128, 192, 127));

                // Add a single text element.
                elementText = new PdfMarkTextElement();
                // We center the text.
                elementText.setRelativeContainer(new PadesVisualRectangle());
                // Specifying just the height (but not top or bottom) results in a vertically centered fixed-height container
                elementText.getRelativeContainer().setHeight(5.0);
                // Specifying just the width (but not left or right) results in a horizontally centered fixed-width container
                elementText.getRelativeContainer().setWidth(1.0);
                // 90 degrees rotation (counter clockwise)
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
