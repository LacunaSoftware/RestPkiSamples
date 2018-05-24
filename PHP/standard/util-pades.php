<?php

use Lacuna\RestPki\Color;
use Lacuna\RestPki\PadesVisualPositioningPresets;
use Lacuna\RestPki\PadesMeasurementUnits;
use Lacuna\RestPki\PdfMark;
use Lacuna\RestPki\PdfMarkImage;
use Lacuna\RestPki\PdfMarkImageElement;
use Lacuna\RestPki\PdfMarkTextElement;
use Lacuna\RestPki\PdfTextSection;
use Lacuna\RestPki\PdfTextStyle;

require __DIR__ . '/vendor/autoload.php';

function getVisualRepresentation($client)
{
    // Create a visual representation.
    $visualRepresentation = [

        'text' => [

            // For a full list of the supported tags, see: https://github.com/LacunaSoftware/RestPkiSamples/blob/master/PadesTags.md
            'text' => 'Signed by {{name}} ({{national_id}})',
            'fontSize' => 13.0,
            // Specify that the signing time should also be rendered.
            'includeSigningTime' => true,
            // Optionally set the horizontal alignment of the text ('Left' or 'Right'), if not set the default is Left.
            'horizontalAlign' => 'Left',
            // Optionally set the container within the signature rectangle on which to place the text. By default, the
            // text can occupy the entire rectangle (how much of the rectangle the text will actually fill depends on the
            // length and font size). Below, we specify that the text should respect a right margin of 1.5 cm.
            'container' => [
                'left' => 0.2,
                'top' => 0.2,
                'right' => 0.2,
                'bottom' => 0.2
            ]
        ],
        'image' => [

            // We'll use as background the image content/PdfStamp.png.
            'resource' => [
                'content' => base64_encode(file_get_contents('content/PdfStamp.png')),
                'mimeType' => 'image/png'
            ],
            // Align the image to the right.
            'horizontalAlign' => 'Right',
            // Align the image to the center.
            'verticalAlign' => 'Center',
        ],
        // Position of the visual representation. We get the footnote position preset.
        'position' => PadesVisualPositioningPresets::getFootnote($client)
    ];

    // It's possible to customize the position presets. For this sample, we will customize the representation
    // container's size to fit the background image.
    $visualRepresentation['position']->auto->container->height = 4.94;
    $visualRepresentation['position']->auto->signatureRectangleSize->width = 8.0;
    $visualRepresentation['position']->auto->signatureRectangleSize->height = 4.94;

    return $visualRepresentation;
}


// This function is called by pades-signature.php. It contains examples of PDF marks, visual elements of arbitrary
// content placed in every page. This code is only in a separate function in order to organize the various examples,
// you can pick the one that best suits your needs and use it below directly without an encapsulating function.
/**
 * @param $sampleNumber
 * @return PdfMark|null
 */
function getPdfMark($sampleNumber)
{

    switch ($sampleNumber) {

        case 1:
            // Example #1: A sample text and image are placed at the bottom of every page.
            // First, we create the mark object. It contains no elements, being a simple empty box.
            $mark = new PdfMark();

            // Here, we set the mark's position in every page.
            $mark->container = [
                // Specifying the width (but no left nor right) results in a horizontally centered fixed-width
                // container.
                'width' => 8,
                // Specifying bottom and height (but no top) results in a bottom-aligned fixed-height container.
                'bottom' => 0.2,
                'height' => 0.6
            ];
            // This example has no background and no borders, so we don't set BackgroundColor nor BorderColor.

            // First, the image.
            $element = new PdfMarkImageElement();
            // We'll position it to the right of the text.
            $element->relativeContainer = [
                // Specifying right and width (but no left) results in a right-aligned fixed-width container.
                'right' => 0,
                'width' => 1,
                // Specifying top and bottom (but no height) results in a variable-height container with the given
                // margins.
                'top' => 0,
                'bottom' => 0
            ];

            // We'll use the image at 'content/PdfStamp.png'.
            $element->image = new PdfMarkImage(getPdfStampContent(), "image/png");
            // Opacity is an integer from 0 to 1000 (0 is completely transparent, 100 is completely opaque).
            $element->image->opacity = 75;
            array_push($mark->elements, $element);


            // Then, the text.
            $element = new PdfMarkTextElement();
            // We center the text.
            $element->relativeContainer = [
                // Specifying left and right (but no width) results in a variable-width container with the given
                // margins.
                'left' => 1,
                'right' => 0,
                // Specifying just the height results in a vertically centered fixed-height container.
                'height' => 0.5
            ];

            // First, a simple message.
            $section = new PdfTextSection();
            // We set the text.
            $section->text = "This document was digitally signed with ";
            // Its color.
            $section->color = new Color("#000000"); // Black
            // Its size.
            $section->fontSize = 8;
            // And the style.
            $section->style = PdfTextStyle::NORMAL;
            // Then add the first text section.
            array_push($element->textSections, $section);

            // And a bold ending.
            $section = new PdfTextSection();
            // We set the text.
            $section->text = "RestPKI";
            // Its color.
            $section->color = new Color("#000000"); // Black
            // Its size.
            $section->fontSize = 8;
            // And the style.
            $section->style = PdfTextStyle::BOLD;
            // Then add the second text section.
            array_push($element->textSections, $section);
            array_push($mark->elements, $element);

            return $mark;

        case 2:
            // Example #2: An image will be placed at the bottom of every page.
            // First, we create the mark object. It contains no elements, being a simple empty box.
            $mark = new PdfMark();
            // Then, we set the mark's position in every page.
            $mark->container = [
                // Specifying right and width (but no left) results in a right-aligned fixed-width container.
                'right' => 1,
                'width' => 2.54,
                // Specifying bottom and height (but no top) results in a bottom-aligned fixed-height container.
                'bottom' => 1,
                'height' => 2.54
            ];
            // After that, its border must be configured.
            $mark->borderWidth = 0.02;
            $mark->borderColor = new Color("#000000"); // Black

            // Add a single image element.
            $element = new PdfMarkImageElement();
            // We'll make the image fill the entire mark, leaving space for the border.
            $element->relativeContainer = [
                'left' => 0.1,
                'right' => 0.1,
                'top' => 0.1,
                'bottom' => 0.1
            ];
            // We'll use the 'Content/PdfStamp.png' as background.
            $element->image = new PdfMarkImage(getPdfStampContent(), "image/png");
            // Opacity is an integer from 0 to 100 (0 is completely transparent, 100 is completely opaque).
            $element->image->opacity = 50;
            array_push($mark->elements, $element);

            return $mark;

        case 3:
            // Example #3: 'Signed with RestPKI' is printed at the top of every page in a blue horizontal bar.
            // First, we create the mark object. It contains no elements, being a simple empty box.
            $mark = new PdfMark();
            // Then, we set the mark's position in every page.
            $mark->container = [
                // Specifying left and right (but no width) results in a variable-width container with the given
                // margins.
                'left' => 0,
                'right' => 0,
                // Specifying top and height (but no bottom) results in a top-aligned fixed-height container.
                'top' => 0.5,
                'height' => 1
            ];
            // We'll not need a border, just a background color.
            $mark->backgroundColor = new Color(0, 128, 192, 50);

            // Add a single text element.
            $element = new PdfMarkTextElement();
            // We center the text.
            $element->relativeContainer = [
                // Specifying just the width results in a horizontally centered fixed-width container.
                'width' => 5,
                // Specifying just the height results in a vertically centered fixed-height container.
                'height' => 1
            ];
            // This example has a single section.
            $section = new PdfTextSection();
            // We set the text.
            $section->text = "Signed with RestPKI";
            // Its color.
            $section->color = new Color("#FFFFFF"); // White
            // Its size.
            $section->fontSize = 12;
            // And the style.
            $section->style = PdfTextStyle::BOLD;
            // Then add the text section.
            array_push($element->textSections, $section);
            array_push($mark->elements, $element);

            return $mark;

        case 4:
            // Example #4: Same as example #3, but written vertically on the right side of each page
            // First, we create the mark object. It contains no elements, being a simple empty box.
            $mark = new PdfMark();
            // Then, we set the mark's position in every page.
            $mark->container = [
                // Specifying right and width (but no left) results in a right-aligned fixed-width container.
                'right' => 0.5,
                'width' => 1,
                // Specifying top and bottom (but no height) results in a variable-height container with the given
                // margins.
                'top' => 0,
                'bottom' => 0
            ];
            // We'll not need a border, just a background color.
            $mark->backgroundColor = new Color(0, 128, 192, 50);

            // Add a single text element.
            $element = new PdfMarkTextElement();
            // We center the text.
            $element->relativeContainer = [
                // Specifying just the height (but not top or bottom) results in a vertically centered fixed-height
                // container.
                'height' => 5,
                // Specifying just the width (but not left or right) results in a horizontally centered fixed-width
                // container.
                'width' => 1
            ];
            // 90 degrees rotation (counter clockwise).
            $element->rotation = 90;
            // This example has a single section.
            $section = new PdfTextSection();
            // We set the text.
            $section->text = "Signed with RestPKI";
            // Its color.
            $section->color = new Color("#FFFFFF"); // White
            // Its size.
            $section->fontSize = 12;
            // And the style.
            $section->style = PdfTextStyle::BOLD;
            // Then add the text section.
            array_push($element->textSections, $section);
            array_push($mark->elements, $element);

            return $mark;

        default:
            return null;

    }
}
