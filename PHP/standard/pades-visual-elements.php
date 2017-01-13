<?php

namespace Lacuna;

use Lacuna\RestPki\Client\Color;
use Lacuna\RestPki\Client\PadesVisualPositioningPresets;
use Lacuna\RestPki\Client\PadesMeasurementUnits;
use Lacuna\RestPki\Client\PdfMark;
use Lacuna\RestPki\Client\PdfMarkImage;
use Lacuna\RestPki\Client\PdfMarkImageElement;
use Lacuna\RestPki\Client\PdfMarkTextElement;
use Lacuna\RestPki\Client\PdfTextSection;
use Lacuna\RestPki\Client\PdfTextStyle;

require __DIR__ . '/vendor/autoload.php';

// This class contains settings for signature visual positioning and PDF marks, which are options when performing
// PAdES signatures.
class PadesVisualElements
{

    // This function is called by pades-signature.php. It contains examples of signature visual representation
    // positionings. This code is only in a separate function in order to organize the various examples, you can pick
    // the one that best suits your needs and use it below directly without an encapsulating function.
    static function getVisualRepresentationPosition($sampleNumber)
    {

        switch ($sampleNumber) {

            case 1:
                // Example #1: automatic positioning on footnote. This will insert the signature, and future signatures,
                // ordered as a footnote of the last page of the document
                return PadesVisualPositioningPresets::getFootnote(getRestPkiClient());

            case 2:
                // Example #2: get the footnote positioning preset and customize it
                $visualPosition = PadesVisualPositioningPresets::getFootnote(getRestPkiClient());
                $visualPosition->auto->container->left = 2.54;
                $visualPosition->auto->container->bottom = 2.54;
                $visualPosition->auto->container->right = 2.54;
                return $visualPosition;

            case 3:
                // Example #3: automatic positioning on new page. This will insert the signature, and future signatures,
                // in a new page appended to the end of the document.
                return PadesVisualPositioningPresets::getNewPage(getRestPkiClient());

            case 4:
                // Example #4: get the "new page" positioning preset and customize it
                $visualPosition = PadesVisualPositioningPresets::getNewPage(getRestPkiClient());
                $visualPosition->auto->container->left = 2.54;
                $visualPosition->auto->container->top = 2.54;
                $visualPosition->auto->container->right = 2.54;
                $visualPosition->auto->signatureRectangleSize->width = 5;
                $visualPosition->auto->signatureRectangleSize->height = 3;
                return $visualPosition;

            case 5:
                // Example #5: manual positioning
                return [
                    'pageNumber' => 0, // zero means the signature will be placed on a new page appended to the end of
                    // the document
                    'measurementUnits' => PadesMeasurementUnits::CENTIMETERS,
                    // define a manual position of 5cm x 3cm, positioned at 1 inch from the left and bottom margins
                    'manual' => [
                        'left' => 2.54,
                        'bottom' => 2.54,
                        'width' => 5,
                        'height' => 3
                    ]
                ];

            case 6:
                // Example #6: custom auto positioning
                return [
                    'pageNumber' => -1, // negative values represent pages counted from the end of the document (-1 is
                    // last page)
                    'measurementUnits' => PadesMeasurementUnits::CENTIMETERS,
                    'auto' => [
                        // Specification of the container where the signatures will be placed, one after the other
                        'container' => [
                            // Specifying left and right (but no width) results in a variable-width container with the
                            // given margins
                            'left' => 2.54,
                            'right' => 2.54,
                            // Specifying bottom and height (but no top) results in a bottom-aligned fixed-height
                            // container
                            'bottom' => 2.54,
                            'height' => 12.31
                        ],
                        // Specification of the size of each signature rectangle
                        'signatureRectangleSize' => [
                            'width' => 5,
                            'height' => 3
                        ],
                        // The signatures will be placed in the container side by side. If there's no room left, the
                        // signatures will "wrap" to the next row. The value below specifies the vertical distance
                        // between rows
                        'rowSpacing' => 1
                    ]
                ];

            default:
                return null;
        }
    }

    // This function is called by pades-signature.php. It contains examples of PDF marks, visual elements of arbitrary
    // content placed in every page. This code is only in a separate function in order to organize the various examples,
    // you can pick the one that best suits your needs and use it below directly without an encapsulating function.
    /**
     * @param $sampleNumber
     * @return PdfMark|null
     */
    static function getPdfMark($sampleNumber)
    {

        switch ($sampleNumber) {

            case 1:
                // Example #1: A sample text and image are placed at the bottom of every page.
                // First, we create the mark object. It contains no elements, being a simple empty box.
                $mark = new PdfMark();

                // Here, we set the mark's position in every page.
                $mark->container = [
                    // Specifying the width (but no left nor right) results in a horizontally centered fixed-width container
                    'width' => 8,
                    // Specifying bottom and height (but no top) results in a bottom-aligned fixed-height container
                    'bottom' => 0.2,
                    'height' => 0.6
                ];
                // This example has no background and no borders, so we don't set BackgroundColor nor BorderColor

                // First, the image.
                $element = new PdfMarkImageElement();
                // We'll position it to the right of the text.
                $element->relativeContainer = [
                    // Specifying right and width (but no left) results in a right-aligned fixed-width container
                    'right' => 0,
                    'width' => 1,
                    // Specifying top and bottom (but no height) results in a variable-height container with the given margins
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
                    // Specifying left and right (but no width) results in a variable-width container with the given margins
                    'left' => 1,
                    'right' => 0,
                    // Specifying just the height results in a vertically centered fixed-height container
                    'height' => 0.5
                ];

                // First, a simple message.
                $section = new PdfTextSection();
                // We set the text.
                $section->text = "This document was digitally signed with ";
                // Its color.
                $section->color = new Color("#000000"); // Black
                // Its Size.
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
                // Its Size.
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
                    // Specifying right and width (but no left) results in a right-aligned fixed-width container
                    'right' => 1,
                    'width' => 2.54,
                    // Specifying bottom and height (but no top) results in a bottom-aligned fixed-height container
                    'bottom' => 1,
                    'height' => 2.54
                ];
                // After that, its border must be configured.
                $mark->borderWidth = 0.02;
                $mark->borderColor = new Color("#000000"); // Black

                // Add a single image element
                $element = new PdfMarkImageElement();
                // We'll make the image fill the entire mark, leaving space for the border
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
                    // Specifying left and right (but no width) results in a variable-width container with the given margins
                    'left' => 0,
                    'right' => 0,
                    // Specifying top and height (but no bottom) results in a top-aligned fixed-height container
                    'top' => 0.5,
                    'height' => 1
                ];
                // We'll not need a border, just a background color.
                $mark->backgroundColor = new Color(0, 128, 192, 50);

                // Add a single text element.
                $element = new PdfMarkTextElement();
                // We center the text.
                $element->relativeContainer = [
                    // Specifying just the width results in a horizontally centered fixed-width container
                    'width' => 5,
                    // Specifying just the height results in a vertically centered fixed-height container
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
                    // Specifying right and width (but no left) results in a right-aligned fixed-width container
                    'right' => 0.5,
                    'width' => 1,
                    // Specifying top and bottom (but no height) results in a variable-height container with the given margins
                    'top' => 0,
                    'bottom' => 0
                ];
                // We'll not need a border, just a background color.
                $mark->backgroundColor = new Color(0, 128, 192, 50);

                // Add a single text element.
                $element = new PdfMarkTextElement();
                // We center the text.
                $element->relativeContainer = [
                    // Specifying just the height (but not top or bottom) results in a vertically centered fixed-height container
                    'height' => 5,
                    // Specifying just the width (but not left or right) results in a horizontally centered fixed-width container
                    'width' => 1
                ];
                // 90 degrees rotation (counter clockwise)
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
}