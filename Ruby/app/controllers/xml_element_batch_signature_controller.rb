class XmlElementBatchSignatureController < ApplicationController
    include ApplicationHelper

    # This route only does the renderization of the elements' ids of the XML file (EventoManifesto.xml) on the page,
    # which will be signed. The page will handle each element id one by one and will call the server synchronously to
    # start and complete each signature. It has to be synchronous, because a signature needs the xml document's content,
    # containing the previous signature.
    def index
        begin

            # It is up to your application's business logic to determine which element ids will compose the batch.
            @elements_ids = Array.new

            (1..10).each do |i|
                @elements_ids.push("ID2102100000000000000000000000000000000000008916%02d" % i)
            end

        rescue => ex

            @error = ex
            render 'layouts/_error'

        end
    end

    # This route initiates a XML element signature using REST PKI and return the token, which identifies the signature's
    # process.
    def start

        # Retrieve the element id from the URL
        element_id = params[:elemId]

        # Retrieve, if specified, file to be signed. If not specified, the original file is assumed
        file_id = params[:fileId]

        # Instantiate the XmlElementSignatureStarter class, responsible for receiving the signature elements and start
        # the signature process (see config/initializers/restpki.rb)
        signature_starter = RestPki::XmlElementSignatureStarter.new(get_restpki_client)

        # Set the signature policy
        signature_starter.signature_policy_id = RestPki::StandardSignaturePolicies::XML_ICPBR_NFE_PADRAO_NACIONAL
        # Note: Depending on the signature policy chosen above, setting the security context below may be mandatory (this
        # is not the case for ICP-Brasil policies, which will automatically use the PkiBrazil security context if none
        # is passed)

        # Optionally, set a SecurityContext to be used to determine trust in the certificate chain
        signature_starter.security_context_id = '803517ad-3bbc-4169-b085-60053a8f6dbf'

        # Logic to use a single file for the batch signature. Acts together with "complete" route.
        if not file_id.to_s.empty?
            signature_starter.set_xml_tosign_from_path(Rails.root.join('public', 'uploads', params[:fileId]))
        else
            signature_starter.set_xml_tosign_from_path(get_sample_manifesto_path)
        end

        # Set the ID of the element to be signed
        signature_starter.element_tosign_id = element_id

        # Call the :start_with_webpki method, which initiates the signature. This yields the token, a 43-character
        # case-sensitive URL-safe string, which identifies this signature process. We'll use this value to call the
        # sign_with_restpki method on the Web PKI component (see signature-form.js) and also to complete the signature
        # after the form is submitted (see method create below). This should not be mistaken with the
        # API access token.
        token = signature_starter.start_with_webpki

        render :json => token.to_json

    end

    # This action receives the form submission from the signature page. We'll call REST PKI to complete the signature.
    def complete

        # Retrieve the token from the URL
        token = params[:token]

        # Instantiate the XmlSignatureFinisher class, responsible for completing the signature
        # process (see config/initializers/restpki.rb)
        signature_finisher = RestPki::XmlSignatureFinisher.new(get_restpki_client)

        # Set the token
        signature_finisher.token = token

        # Call the finish method, which finalizes the signature process
        signed_bytes = signature_finisher.finish

        # At this point, you'd typically store the signed XML on your database. For demonstration purposes, we'll
        # store the XML on a temporary folder publicly accessible and render a link to it.

        filename = SecureRandom.hex(10).to_s + '.xml'
        File.open(Rails.root.join('public', 'uploads', filename), 'wb') do |file|
            file.write(signed_bytes)
        end

        render :json => filename.to_json
    end
end
