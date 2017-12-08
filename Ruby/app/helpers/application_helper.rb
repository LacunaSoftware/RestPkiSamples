module ApplicationHelper

    def get_sample_doc_path
        Rails.root.join('public', 'uploads', 'SampleDocument.pdf')
    end

    def get_sample_xml_path
        Rails.root.join('public', 'uploads', 'SampleDocument.xml')
    end

    def get_sample_nfe_path
        Rails.root.join('public', 'uploads', 'SampleNFe.xml')
    end

    def get_sample_manifesto_path
        Rails.root.join('public', 'uploads', 'EventoManifesto.xml')
    end

    def get_batch_doc_path(id)
        Rails.root.join('public', 'uploads', "0#{id.to_i % 10}.pdf")
    end

    def get_pdf_stamp_content
        content = nil
        stamp_path = Rails.root.join('app', 'assets', 'images', 'PdfStamp.png')
        File.open(stamp_path, 'rb') do |file|
            content = file.read
        end
        content
    end

    def set_expired_page_headers
        now = DateTime.now
        expires = now - 3600.second
        headers['Expires'] = expires.strftime('%a, %d %b %Y %H:%M:%S GMT')
        headers['Last-Modified'] = now.strftime('%a, %d %b %Y %H:%M:%S GMT')
        headers['Cache-Control'] = 'private, no-store, max-age=0, no-cache, must-revalidate, post-check=0, pre-check=0'
        headers['Pragma'] = 'no-cache'
    end

    # This method is called by all pages to determine the security context to be used.
    #
    # Security contexts dictate witch root certification authorities are trusted during
    # certificate validation. In your API calls, you can use one of the standard security
    # contexts or reference one of your custom contexts.
    def get_security_context_id

        unless Rails.env.production?

            # Lacuna Test PKI (for development purposes only!)
            #
            # This security context trusts ICP-Brasil certificates as well as certificates on
            # Lacuna Software's test PKI. Use it to accept the test certificates provided by
            # Lacuna Software.
            #
            # THIS SHOULD NEVER BE USED ON A PRODUCTION ENVIRONMENT!
            return '803517ad-3bbc-4169-b085-60053a8f6dbf'
            # Notice for On Premises users: this security context might not exist on your
            # installation, if you encounter an error please contact developer support.
        end

        # In production, accepting only certificates from ICP-Brasil
        RestPki::StandardSecurityContexts::PKI_BRAZIL
    end
end
