from base64 import b64encode
from lacunarestpki import PadesVisualPositioningPresets

from restpki.utils import get_pdf_stamp_content
from restpki.utils import get_restpki_client


def get_visual_representation():
    """

    This function is called by PAdES samples. It contains an example of
    signature visual representation. This is only in a separate function in
    order to organize the various examples.

    """

    # Create a visual representation.
    visual_representation = {

        'text': {

            # For a full list of the supported tags, see:
            # https://github.com/LacunaSoftware/RestPkiSamples/blob/master/PadesTags.md
            'text': 'Signed by {{name}} ({{national_id}})',
            'fontSize': 13.0,
            # Specify that the signing time should also be rendered.
            'includeSigningTime': True,
            # Optionally set the horizontal alignment of the text ('Left' or
            # 'Right'), if not set the default is 'Left'.
            'horizontalAlign': 'Left',
            # Optionally set the container within the signature rectangle on
            # which to place the text. By default, the text can occupy the
            # entire rectangle (how much of the rectangle the text will actually
            # fill depends on the length and font size). Below, we specify that
            # the text should respect a right margin of 1.5 cm.
            'container': {
                'left': 0.2,
                'top': 0.2,
                'right': 0.2,
                'bottom': 0.2
            }
        },
        'image': {

            # We'll use as background the image static/PdfStamp.png
            'resource': {
                'content': b64encode(get_pdf_stamp_content()),
                'mimeType': 'image/png'
            },
            # Align the image to the right horizontally.
            'horizontalAlign': 'Right',
            # Align the image to the center vertically.
            'verticalAlign': 'Center'
        },
    }

    # Position of the visual representation. We get the footnote position preset
    # and customize it.
    visual_positioning = \
        PadesVisualPositioningPresets.get_footnote(get_restpki_client())
    visual_positioning['auto']['container']['height'] = 4.94
    visual_positioning['auto']['signatureRectangleSize']['width'] = 8.0
    visual_positioning['auto']['signatureRectangleSize']['height'] = 4.94
    visual_representation['position'] = visual_positioning

    return visual_representation
