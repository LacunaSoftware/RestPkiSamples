from flask import send_from_directory
from flask import current_app
from flask import Blueprint


blueprint = Blueprint('download', __name__, url_prefix='/files')


@blueprint.route('/<filename>')
def get_file(filename):
    """

    This function's purpose is to download the sample file that is signed during
    the signature examples or download a upload file for signature or download a
    previously performed signature.

    """

    return send_from_directory(current_app.config['APPDATA_FOLDER'], filename)


@blueprint.route('/doc/<file_id>')
def get_doc(file_id):
    """

    This function's purpose is to download the sample file that is signed during
    the signature examples or download a upload file for signature or download a
    previously performed signature.

    """

    return send_from_directory(current_app.static_folder,
                               "%02d.pdf" % (int(file_id) % 10))
