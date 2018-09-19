from flask import render_template
from flask import Blueprint


blueprint = Blueprint('home', __name__, url_prefix='/')


@blueprint.route('/')
def index():
    """

    This function will render the main page.

    """

    return render_template('home/index.html')
