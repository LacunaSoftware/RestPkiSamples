import os
import sys
from imp import reload
from importlib import import_module

from flask import Flask

from config import config
from app.blueprints import all_blueprints

# Folders location
APP_ROOT_FOLDER = os.path.abspath(os.path.dirname(__file__))
STATIC_FOLDER = os.path.join(APP_ROOT_FOLDER, 'static')
APPDATA_FOLDER = os.path.join(APP_ROOT_FOLDER, 'app_data')
TEMPLATE_FOLDER = os.path.join(APP_ROOT_FOLDER, 'templates')

if sys.version_info[0] < 3:
    reload(sys)
    sys.setdefaultencoding('utf-8')

if not os.path.exists(APPDATA_FOLDER):
    os.makedirs(APPDATA_FOLDER)


def create_app(config_name):
    """

    Inicialize and configure the app.

    """
    app = Flask(__name__, template_folder=TEMPLATE_FOLDER,
                static_folder=STATIC_FOLDER)

    # Add environment configuration
    app.config.from_object(config[config_name])
    config[config_name].init_app(app)
    app.config['APPDATA_FOLDER'] = APPDATA_FOLDER

    # Register all blueprints
    for bp in all_blueprints:
        import_module(bp.import_name)
        app.register_blueprint(bp)

    # Return the application instance.
    return app
