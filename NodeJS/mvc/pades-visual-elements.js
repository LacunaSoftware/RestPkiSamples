const { PadesVisualPositioningPresets } = require('restpki-client');

const { Util } = require('./util');

class PadesVisualElements {

  static getVisualRepresentation() {

     let visualRepresentation = {
        text: {

           // For a full list of the supported tags, see:
           // https://github.com/LacunaSoftware/RestPkiSamples/blob/master/PadesTags.md
           text: 'Signed by {{name}} ({{national_id}})',
           fontSize: 13.0,
           // Specify that the signing time should also be rendered.
           includeSigningTime: true,
           // Optionally set the horizontal alignment of the text ('Left' or
           // 'Right'), if not set the default is Left.
           horizontalAlign: 'Left',
           // Optionally set the container within the signature rectangle on
           // which to place the text. By default, the text can occupy the
           // entire rectangle (how much of the rectangle the text will actually
           // fill depends on the length and font size). Below, we specify that
           // the text should respect a right margin of 1.5 cm.
           container: {
              left: 0.2,
              top: 0.2,
              right: 0.2,
              bottom: 0.2
           }

        },
        image: {

           // We'll use as background the image content/PdfStamp.png
           resource: {
              content: new Buffer(util.getPdfStampContent()).toString('base64'), // Base-64 encoded!
              mimeType: 'image/png'
           },

           // Align the image to the right horizontally.
           horizontalAlign: 'Right',
           // Align the image to the center vertically.
           verticalAlign: 'Center'
        }
     };

     return new Promise(function (resolve, reject) {

        // Position of the visual representation. We get the footnote position
        // preset and customize it.
        PadesVisualPositioningPresets.getFootnote(Util.getRestPkiClient())
        .then((visualPositioning) => {
            visualPositioning.auto.container.height = 4.94;
            visualPositioning.auto.signatureRectangleSize.width = 8.0;
            visualPositioning.auto.signatureRectangleSize.height = 4.94;

            // Add position to visual representation.
            visualRepresentation.position = visualPositioning;

            resolve(visualRepresentation);
        })
        .catch((err) => reject(err));

     });
  }
}

exports.PadesVisualElements = PadesVisualElements;