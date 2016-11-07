from config import app
from views import home, authentication, pades_signature, xml_signature, download, upload, cades_signature

if __name__ == '__main__':
    app.register_blueprint(authentication.blueprint)
    app.register_blueprint(cades_signature.blueprint)
    app.register_blueprint(download.blueprint)
    app.register_blueprint(home.blueprint)
    app.register_blueprint(pades_signature.blueprint)
    app.register_blueprint(upload.blueprint)
    app.register_blueprint(xml_signature.blueprint)

    app.run(host='localhost', debug=True)
