from flask import send_from_directory

from app.blueprints import download
from app import APPDATA_FOLDER


@download.route('/<filename>')
def get_file(filename):
    """

    This function's purpose is to download the sample file that is signed during
    the signature examples or download a upload file for signature or download a
    previously performed signature.

    """

    return send_from_directory(APPDATA_FOLDER, filename)
