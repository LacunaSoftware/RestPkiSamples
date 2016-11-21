import os
import uuid

from flask import Blueprint
from flask import render_template, request
from werkzeug.utils import secure_filename, redirect

from util import APPDATA_FOLDER

# Create a blueprint for this view for its routes to be reachable
blueprint = Blueprint('upload', __name__)


@blueprint.route('/upload/<goto>', methods=['GET', 'POST'])
def upload(goto):
    """
        This function allows the user to upload a file to be signed. Once the file is uploaded, we save it to the
        app_data folder and redirect the user to cades-signature or pades-signature view passing the filename on the
        "userfile" URL argument.
    """

    if request.method == 'POST':
        userfile = request.files['userfile']
        filename = '%s_%s' % (str(uuid.uuid1()), secure_filename(userfile.filename))
        userfile.save(os.path.join(APPDATA_FOLDER, filename))
        return redirect('/%s/%s' % (goto, filename))
    else:
        return render_template('upload.html')
