<?php

require_once 'RestPkiLegacy52.php';

function getVisualRepresentation($client)
{
    // Create a visual representation.
    $visualRepresentation = array(

        'text' => array(

            // For a full list of the supported tags, see: https://github.com/LacunaSoftware/RestPkiSamples/blob/master/PadesTags.md
            'text' => 'Signed by {{name}} ({{national_id}})',
            'fontSize' => 13.0,
            // Specify that the signing time should also be rendered.
            'includeSigningTime' => true,
            // Optionally set the horizontal alignment of the text ('Left' or 'Right'), if not set the default is Left.
            'horizontalAlign' => 'Left',
            // Optionally set the container within the signature rectangle on which to place the text. By default, the
            // text can occupy the entire rectangle (how much of the rectangle that text will actually fill depends on
            // the length and font size). Below, we specify that the text should respect a right margin of 1.5 cm.
            'container' => array(
                'left' => 0.2,
                'top' => 0.2,
                'right' => 0.2,
                'bottom' => 0.2
            )
        ),
        'image' => array(

            // We'll use as background the image content/PdfStamp.png.
            'resource' => array(
                'content' => base64_encode(file_get_contents('content/PdfStamp.png')),
                'mimeType' => 'image/png'
            ),
            // Align the image to the right.
            'horizontalAlign' => 'Right',
            // Align the image to the center.
            'verticalAlign' => 'Center'
        ),
        // Position of the visual representation. We get the footnote position preset.
        'position' => RestPkiPadesVisualPositioningPresets::getFootnote($client)
    );

    // Its possible to customize th position presets. For this sample, we will customize the representation container's
    // size to fit the background image.
    $visualRepresentation['position']->auto->container->height = 4.94;
    $visualRepresentation['position']->auto->signatureRectangleSize->width = 8.0;
    $visualRepresentation['position']->auto->signatureRectangleSize->height = 4.94;

    return $visualRepresentation;
}