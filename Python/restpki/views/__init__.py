from .authentication import blueprint as authentication
from .cades_signature import blueprint as cades_signature
from .download import blueprint as download
from .home import blueprint as home
from .pades_signature import blueprint as pades_signature
from .upload import blueprint as upload
from .xml_signature import blueprint as xml_signature

blueprints = {
    authentication,
    cades_signature,
    download,
    home,
    pades_signature,
    upload,
    xml_signature
}