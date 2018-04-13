from flask import Blueprint


def _factory(view, url_prefix):
    import_name = 'app.views.{}'.format(view)
    return Blueprint(view, import_name, url_prefix=url_prefix)


authentication = _factory('authentication', '/authentication')
cades_signature = _factory('cades_signature', '/cades-signature')
download = _factory('download', '/files')
home = _factory('home', '/')
pades_signature = _factory('pades_signature', '/pades-signature')
upload = _factory('upload', '/upload')
xml_signature = _factory('xml_signature', '/xml-signature')

all_blueprints = {
    authentication,
    cades_signature,
    download,
    home,
    pades_signature,
    upload,
    xml_signature
}
