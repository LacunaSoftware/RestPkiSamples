import os
import uuid

from flask import Blueprint
from flask import render_template, request
from werkzeug.utils import secure_filename, redirect

from config import app

blueprint = Blueprint('upload', __name__)


@blueprint.route('/upload/<goto>', methods=['GET', 'POST'])
def upload(goto):
    if request.method == 'POST':
        userfile = request.files['userfile']
        filename = '%s_%s' % (str(uuid.uuid1()), secure_filename(userfile.filename))
        userfile.save(os.path.join(app.config['APPDATA_FOLDER'], filename))
        return redirect('/%s/%s' % (goto, filename))
    else:
        return render_template('upload.html')
