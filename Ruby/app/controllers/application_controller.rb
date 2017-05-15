class ApplicationController < ActionController::Base
    # Prevent CSRF attacks by raising an exception.
    # For APIs, you may want to use :null_session instead.
    protect_from_forgery with: :null_session

    def index
    end

    def set_expired_page_headers
        now = DateTime.now
        expires = now - 3600.second
        headers['Expires'] = expires.strftime('%a, %d %b %Y %H:%M:%S GMT')
        headers['Last-Modified'] = now.strftime('%a, %d %b %Y %H:%M:%S GMT')
        headers['Cache-Control'] = 'private, no-store, max-age=0, no-cache, must-revalidate, post-check=0, pre-check=0'
        headers['Pragma'] = 'no-cache'
    end

end
