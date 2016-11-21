from flask import Blueprint, send_from_directory

# Create a blueprint for this view for its routes to be reachable
blueprint = Blueprint('download', __name__)


@blueprint.route('/files/<filename>')
def get_file(filename):
    """
        This function's purpose is to download the sample file that is signed during the signature examples or download
        a upload file for signature or download a previously performed signature.
    """

    return send_from_directory('app_data', filename)
