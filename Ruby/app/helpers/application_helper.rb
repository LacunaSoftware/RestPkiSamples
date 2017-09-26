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

    def get_sample_doc_path_from_id(id)
        Rails.root.join('public', 'uploads', "#{id}.pdf")
    end

    def get_pdf_stamp_content
        content = nil
        File.open(Rails.root.join('app/assets', 'images', 'PdfStamp.png'), 'rb') do |file|
            content = file.read
        end
        content
    end
end
