from flask import Blueprint, send_from_directory

blueprint = Blueprint('download', __name__)


@blueprint.route('/files/<filename>')
def get_file(filename):
    return send_from_directory('app_data', filename)