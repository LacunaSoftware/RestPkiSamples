import os
import sys

from importlib import reload

from flask import Flask

if sys.version_info[0] < 3:
    reload(sys)
    sys.setdefaultencoding('utf-8')

APPDATA_FOLDER = 'app_data'
STATIC_FOLDER = 'static'
TEMPLATE_DOCUMENT = 'SampleDocument.pdf'
XML_DOCUMENT = 'SampleDocument.xml'
NFE_SAMPLE = 'SampleNFe.xml'

app = Flask(__name__, template_folder='templates')
app.config['APPDATA_FOLDER'] = APPDATA_FOLDER
app.config['STATIC_FOLDER'] = STATIC_FOLDER

if not os.path.exists(APPDATA_FOLDER):
    os.makedirs(APPDATA_FOLDER)
