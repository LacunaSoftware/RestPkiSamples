using Lacuna.RestPki.Api.PadesSignature;
using Lacuna.RestPki.Client;
using System;
using System.Collections.Generic;
using System.Linq;
using System.Threading.Tasks;

namespace NewAngular2.Classes {
	public class PadesVisualElements {

		// This function is called by the Pades Signature Starter action (see PadesSignatureController.cs).
		// It contains examples of signature visual representation positionings.
		public static PadesVisualPositioning GetVisualPositioning(RestPkiClient client, int sampleNumber) {

			switch (sampleNumber) {

				case 1:
					// Example #1: automatic positioning on footnote. This will insert the signature, and future signatures,
					// ordered as a footnote of the last page of the document
					return PadesVisualPositioning.GetFootnote(client);

				case 2:
					// Example #2: get the footnote positioning preset and customize it
					var footnotePosition = PadesVisualPositioning.GetFootnote(client);
					footnotePosition.Container.Left = 2.54;
					footnotePosition.Container.Bottom = 2.54;
					footnotePosition.Container.Right = 2.54;
					return footnotePosition;

				case 3:
					// Example #3: automatic positioning on new page. This will insert the signature, and future signatures,
					// in a new page appended to the end of the document.
					return PadesVisualPositioning.GetNewPage(client);

				case 4:
					// Example #4: get the "new page" positioning preset and customize it
					var newPagePos = PadesVisualPositioning.GetNewPage(client);
					newPagePos.Container.Left = 2.54;
					newPagePos.Container.Top = 2.54;
					newPagePos.Container.Right = 2.54;
					newPagePos.SignatureRectangleSize.Width = 5;
					newPagePos.SignatureRectangleSize.Height = 3;
					return newPagePos;

				case 5:
					// Example #5: manual positioning
					// The first parameter is the page number. Zero means the signature will be placed on a new page appended to the end of the document
					return new PadesVisualManualPositioning(0, new PadesVisualRectangle() {
						// define a manual position of 5cm x 3cm, positioned at 1 inch from  the left and bottom margins
						Left = 2.54,
						Bottom = 2.54,
						Width = 5,
						Height = 3
					});

				case 6:
					// Example #6: custom auto positioning
					return new PadesVisualAutoPositioning() {
						PageNumber = -1, // negative values represent pages counted from the end of the document (-1 is last page)
											  // Specification of the container where the signatures will be placed, one after the other
						Container = new PadesVisualRectangle() {
							// Specifying left and right (but no width) results in a variable-width container with the given margins
							Left = 2.54,
							Right = 2.54,
							// Specifying bottom and height (but no top) results in a bottom-aligned fixed-height container
							Bottom = 2.54,
							Height = 12.31
						},
						// Specification of the size of each signature rectangle
						SignatureRectangleSize = new PadesSize(5, 3),
						// The signatures will be placed in the container side by side. If there's no room left, the signatures
						// will "wrap" to the next row. The value below specifies the vertical distance between rows
						RowSpacing = 1
					};

				default:
					return null;
			}
		}

		// This function is called by the Pades Signature Starter action (see PadesSignatureController.cs).
		// It contains examples of PDF marks, visual elements of arbitrary content placed in every page.
		public static PdfMark GetPdfMark(Storage storage, int sampleNumber) {

			switch (sampleNumber) {

				case 1:
					// Example #1: A sample text and image are placed at the bottom of every page.
					// First, we create the mark object. It contains no elements, being a simple empty box.
					var mark = new PdfMark() {
						// Here, we set the mark's position in every page.
						Container = new PadesVisualRectangle() {
							// Specifying the width (but no left nor right) results in a horizontally centered fixed-width container
							Width = 8,
							// Specifying bottom and height (but no top) results in a bottom-aligned fixed-height container
							Bottom = 0.2,
							Height = 0.6
						}
						// This example has no background and no borders, so we don't set BackgroundColor nor BorderColor
					};

					// First, the image.
					mark.Elements.Add(new PdfMarkImageElement() {
						// We'll position it to the right of the text.
						RelativeContainer = new PadesVisualRectangle() {
							// Specifying right and width (but no left) results in a right-aligned fixed-width container
							Right = 0,
							Width = 1,
							// Specifying top and bottom (but no height) results in a variable-height container with the given margins
							Top = 0,
							Bottom = 0
						},
						// We'll use the image at 'Content/PdfStamp.png'.
						Image = new PdfMarkImage(storage.GetPdfStampContent(), "image/png") {
							// Opacity is an integer from 0 to 100 (0 is completely transparent, 100 is completely opaque).
							Opacity = 75,
						}
					});

					// Then, the text.
					mark.Elements.Add(new PdfMarkTextElement() {
						// We center the text.
						RelativeContainer = new PadesVisualRectangle() {
							// Specifying left and right (but no width) results in a variable-width container with the given margins
							Left = 1,
							Right = 0,
							// Specifying just the height results in a vertically centered fixed-height container
							Height = 0.5
						},
						// Then add the text sections.
						TextSections = {
							// First, a simple message.
							new PdfTextSection() {
								// We set the text.
								Text = "This document was digitally signed with ",
								// Its color. (only supported on upcoming .NET Core 2.0)
								//Color = Color.Black,
								// Its size.
								FontSize = 8,
								// And the style.
								Style = PdfTextStyle.Normal
							},
							// And a bold ending.
							new PdfTextSection() {
								// We set the text.
								Text = "RestPKI",
								// Its color. (only supported on upcoming .NET Core 2.0)
								//Color = Color.Black,
								// Its size.
								FontSize = 8,
								// And the style.
								Style = PdfTextStyle.Bold
							}
						}
					});

					return mark;

				case 2:
					// Example #2: An image will be placed at the bottom of every page.
					// First, we create the mark object. It contains no elements, being a simple empty box.
					mark = new PdfMark() {
						// Then, we set the mark's position in every page.
						Container = new PadesVisualRectangle() {
							// Specifying right and width (but no left) results in a right-aligned fixed-width container
							Right = 1,
							Width = 2.54,
							// Specifying bottom and height (but no top) results in a bottom-aligned fixed-height container
							Bottom = 1,
							Height = 2.54
						},
						// After that, its border must be configured.
						BorderWidth = 0.02,
						// (only supported on upcoming .NET Core 2.0)
						//BorderColor = Color.Black
					};

					// Add a single image element
					mark.Elements.Add(new PdfMarkImageElement() {
						// We'll make the image fill the entire mark, leaving space for the border
						RelativeContainer = new PadesVisualRectangle() {
							Left = 0.1,
							Right = 0.1,
							Top = 0.1,
							Bottom = 0.1
						},
						// We'll use the 'Content/PdfStamp.png' as background.
						Image = new PdfMarkImage(storage.GetPdfStampContent(), "image/png") {
							// Opacity is an integer from 0 to 100 (0 is completely transparent, 100 is completely opaque).
							Opacity = 50,
						}
					});

					return mark;

				case 3:
					// Example #3: 'Signed with RestPKI' is printed at the top of every page in a blue horizontal bar.
					// First, we create the mark object. It contains no elements, being a simple empty box.
					mark = new PdfMark() {
						// Then, we set the mark's position in every page.
						Container = new PadesVisualRectangle() {
							// Specifying left and right (but no width) results in a variable-width container with the given margins
							Left = 0,
							Right = 0,
							// Specifying top and height (but no bottom) results in a top-aligned fixed-height container
							Top = 0.5,
							Height = 1
						},
						// We'll not need a border, just a background color. (only supported on upcoming .NET Core 2.0)
						//BackgroundColor = Color.FromArgb(127, 0, 128, 192)
					};

					// Add a single text element.
					mark.Elements.Add(new PdfMarkTextElement() {
						// We center the text.
						RelativeContainer = new PadesVisualRectangle() {
							// Specifying just the width results in a horizontally centered fixed-width container
							Width = 5,
							// Specifying just the height results in a vertically centered fixed-height container
							Height = 1
						},
						// Then add the text sections.
						TextSections = {
							// This example has a single section.
							new PdfTextSection() {
								// We set the text.
								Text = "Signed with RestPKI",
								// Its color. (only supported on upcoming .NET Core 2.0)
								//Color = Color.White,
								// Its size.
								FontSize = 12,
								// And the style.
								Style = PdfTextStyle.Bold
							}
						}
					});

					return mark;

				default:
					return null;

			}
		}

	}
}
