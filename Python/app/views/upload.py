import os
import uuid
from flask import render_template, request
from werkzeug.utils import secure_filename, redirect

from app import APPDATA_FOLDER
from app.blueprints import upload
from app.util import create_app_data


@upload.route('/<goto>', methods=['GET', 'POST'])
def upload(goto):
    """

    This function allows the user to upload a file to be signed. Once the file
    is uploaded, we save it to the app_data folder and redirect the user to
    cades-signature or pades-signature view passing the filename on the
    "userfile" URL argument.

    """

    if request.method == 'POST':
        userfile = request.files['userfile']

        # Generate a unique filename
        filename = '%s_%s' % (str(uuid.uuid1()), secure_filename(
            userfile.filename))

        # Move the file to the "app_data" with the unique filename. Make sure
        # the "app_data" folder exists (static/util.py)
        create_app_data()
        userfile.save(os.path.join(APPDATA_FOLDER, filename))

        # Redirect the user to the name of the file as a
        return redirect('/%s/%s' % (goto, filename))
    else:
        return render_template('upload/index.html')
