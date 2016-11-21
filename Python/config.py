import os
import sys
from importlib import reload

from flask import Flask

from util import APPDATA_FOLDER, STATIC_FOLDER
from views import authentication, cades_signature, download, home, pades_signature, upload, xml_signature

if sys.version_info[0] < 3:
    reload(sys)
    sys.setdefaultencoding('utf-8')

if not os.path.exists(APPDATA_FOLDER):
    os.makedirs(APPDATA_FOLDER)

# ----------------------------------------------------------------------------------------------------------------------
# Inicialize and configure the app
# ----------------------------------------------------------------------------------------------------------------------
app = Flask(__name__)
app.config['APPDATA_FOLDER'] = APPDATA_FOLDER
app.config['STATIC_FOLDER'] = STATIC_FOLDER

# ----------------------------------------------------------------------------------------------------------------------
# Register all view blueprints
# ----------------------------------------------------------------------------------------------------------------------
app.register_blueprint(authentication.blueprint)
app.register_blueprint(cades_signature.blueprint)
app.register_blueprint(download.blueprint)
app.register_blueprint(home.blueprint)
app.register_blueprint(pades_signature.blueprint)
app.register_blueprint(upload.blueprint)
app.register_blueprint(xml_signature.blueprint)
