class UploadController < ApplicationController

    def index
        flash[:rc] = params[:rc]
    end

    def action
        begin

            # Check that a file was indeed uploaded
            if params[:upload_form].nil?
                raise 'No file upload'
            end
            userfile = params[:upload_form][:userfile]


            extension = File.extname(userfile.original_filename)

            filename = SecureRandom.hex(10).to_s + extension
            File.open(Rails.root.join('public', 'uploads', filename), 'wb') do |file|
                file.write(userfile.read)
            end

            redirect_to :controller => flash[:rc], :action => 'index', :userfile => filename
        rescue => ex
            @errors = ex.error.to_hash
            render 'layouts/error'
        end
    end
end
