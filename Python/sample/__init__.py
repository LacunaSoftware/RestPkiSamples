import os
import sys

from imp import reload
from flask import Flask

from sample.views import blueprints

if sys.version_info[0] < 3:
    reload(sys)
    sys.setdefaultencoding('utf-8')


def create_app(config_obj=None):
    app = Flask(__name__)
    app.config.from_object(config_obj)

    # Folders location
    app_root_folder = os.path.abspath(os.path.dirname(__file__))
    appdata_folder = os.path.join(app_root_folder, 'app_data')

    if not os.path.exists(appdata_folder):
        os.makedirs(appdata_folder)

    # Add environment configuration
    app.config['APPDATA_FOLDER'] = appdata_folder

    register_blueprints(app)
    register_extensions(app)

    return app


def register_extensions(app):
    """Register Flask extensions."""
    pass


def register_blueprints(app):
    """Register application's blueprints"""
    for bp in blueprints:
        app.register_blueprint(bp)
