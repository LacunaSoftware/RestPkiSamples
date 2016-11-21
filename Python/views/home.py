from flask import render_template, Blueprint

# Create a blueprint for this view for its routes to be reachable
blueprint = Blueprint('home', __name__)


@blueprint.route('/')
def index():
    """
        This function will render the main page
    """

    return render_template('index.html')
