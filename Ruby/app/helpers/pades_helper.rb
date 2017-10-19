module PadesHelper

    # This function contains examples of signature visual representation positions. This code is only in a separate
    # function in order to organize the various examples, you can pick the one that best suits your needs and use it
    # below directly without an encapsulating function.
    def get_visual_representation_position(sample_number)
        case sample_number

            when 1
                # Example #1: automatic positioning on footnote. This will insert the signature, and future signatures,
                # ordered as a footnote of the last page of the document
                return RestPki::PadesVisualPositioningPresets::get_footnote(get_restpki_client)

            when 2
                # Example #2: get the footnote positioning preset and customize it
                visual_position = RestPki::PadesVisualPositioningPresets::get_footnote(get_restpki_client)
                visual_position.auto.container.left = 2.54
                visual_position.auto.container.bottom = 2.54
                visual_position.auto.container.right = 2.54
                visual_position

            when 3
                # Example #3: automatic positioning on new page. This will insert the signature, and future signatures,
                # in a new page appended to the end of the document.
                return RestPki::PadesVisualPositioningPresets::get_new_page(get_restpki_client)

            when 4
                # Example #4: get the "new page" positioning preset and customize it
                visual_position = RestPki::PadesVisualPositioningPresets::get_new_page(get_restpki_client)
                visual_position.auto.container.left = 2.54
                visual_position.auto.container.top = 2.54
                visual_position.auto.container.right = 2.54
                visual_position.auto.signatureRectangleSize.width = 5
                visual_position.auto.signatureRectangleSize.height = 3
                visual_position

            when 5
                # Example #5: manual positioning
                return {
                    pageNumber: 0, # zero means the signature will be placed on a new page appended to the end of
                    # the document
                    measurementUnits: 'Centimeters',
                    # define a manual position of 5cm x 3cm, positioned at 1 inch from the left and bottom margins
                    manual: {
                        left: 2.54,
                        bottom: 2.54,
                        width: 5,
                        height: 3
                    }

                }
            when 6
                # Example #6: custom auto positioning
                return {
                    pageNumber: -1, # negative values represent pages counted from the end of the document (-1 is
                    # last page)
                    measurementUnits: 'Centimeters',
                    auto: {
                        # Specification of the container where the signatures will be placed, one after the other
                        container: {
                            # Specifying left and right (but no width) results in a variable-width container with the
                            # given margins
                            left: 2.54,
                            right: 2.54,
                            # Specifying bottom and height (but no top) results in a bottom-aligned fixed-height
                            # container
                            bottom: 2.54,
                            height: 12.31
                        },
                        # Specification of the size of each signature rectangle
                        signatureRectangleSize: {
                            width: 5,
                            height: 3
                        },
                        # The signatures will be placed in the container side by side. If there's no room left, the
                        # signatures will "wrap" to the next row. The value below specifies the vertical distance
                        # between rows
                        rowSpacing: 1
                    }
                }
            else
                return nil
        end

    end
end