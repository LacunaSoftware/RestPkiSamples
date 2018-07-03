from flask import render_template
from flask import make_response
from flask import request
from flask import Blueprint

from restpki.utils import get_restpki_client
from restpki.utils import get_expired_page_headers
from restpki.utils import get_security_context_id


blueprint = Blueprint('authentication', __name__, url_prefix='/authentication')


@blueprint.route('/')
def index():
    """

    This view function initiates an authentication with REST PKI and renders
    the authentication page.

    """

    try:

        # Get an instance of the Authentication class.
        auth = get_restpki_client().get_authentication()

        # Call the start_with_webpki() method, which initiates the
        # authentication. This yields the "token", a 22-character case-sensitive
        # URL-safe string, which represents this authentication process. We'll
        # use this value to call the signWithRestPki() method on the Web PKI
        # component (see signature-form.js) and also to call the
        # complete_with_webpki() method on the route /authentication/action.
        # This should not be mistaken with the API access token. We have
        # encapsulated the security context choice on util.py.
        token = auth.start_with_webpki(get_security_context_id())

        # The token acquired above can only be used for a single
        # authentication. In order to retry authenticating it is necessary to
        # get a new token. This can be a problem if the user uses the back
        # button of the browser, since the browser might show a cached page that
        # we rendered previously, with a now stale token. To prevent this from
        # happening, we force page expiration through HTTP headers to prevent
        # caching of the page.
        response = make_response(render_template('authentication/index.html',
                                                 token=token))
        response.headers = get_expired_page_headers()
        return response

    except Exception as e:
        return render_template('error.html', msg=e)


@blueprint.route('/action', methods=['POST'])
def action():
    """

    This view function receives the form submission from the template
    authentication/index.html. We'll call REST PKI to validate the
    authentication.

    """

    try:

        # Get the token for this authentication (rendered in a hidden input
        # field, see authentication/index.html template).
        token = request.form['token']

        # Get an instance of the Authentication class.
        auth = get_restpki_client().get_authentication()

        # Call the complete_with_webpki() method with the token, which finalizes
        # the authentication process. The call yields a ValidationResults
        # object, which denotes whether the authentication was successful or not
        # (we'll use it to render the page accordingly, see below).
        result = auth.complete_with_webpki(token)

        vr_html = str(result.validation_results)
        vr_html = vr_html.replace('\n', '<br/>')
        vr_html = vr_html.replace('\t', '&nbsp;&nbsp;&nbsp;&nbsp;')

        user_cert = None
        if result.validation_results.is_valid:
            # At this point, you have assurance that the certificate is valid
            # according to the SecurityContext specified on the method
            # start_with_webpki() and that the user is indeed the certificate's
            # subject. Now, you'd typically query your database for a user that
            # matches one of the certificate's fields, such as
            # user_cert.emailAddress or user_cert.pkiBrazil.cpf (the actual
            # field to be used as key depends on your application's business
            # logic) and set the user as authenticated with whatever web
            # security framework your application uses. For demonstration
            # purposes, we'll just render the user's certificate information.
            user_cert = result.certificate

        return render_template('authentication/action.html',
                               valid=result.validation_results.is_valid,
                               vr_html=vr_html,
                               user_cert=user_cert)

    except Exception as e:
        return render_template('error.html', msg=e)
