from flask import render_template

from app.blueprints import home


@home.route('/')
def index():
    """

    This function will render the main page.

    """

    return render_template('home/index.html')
