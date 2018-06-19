function addAlert(type, message) {
	$('#messagesPanel').append(
		'<div class="alert alert-' + type + ' alert-dismissible">' +
		'<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>' +
		'<span>' + message + '</span>' +
        '</div>'
    );
}
