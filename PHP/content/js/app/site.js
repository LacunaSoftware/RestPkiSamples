function addAlert(type, message) {
	$('#messagesPanel').append(
		'<div class="alert alert-' + type + ' alert-dismissible">' +
		'<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>' +
		'<span>' + message + '</span>' +
		'</div>');
}

function log(message, obj) {
	if (console.log) {
		console.log(message, obj);
	}
}

function onWebPkiError(message, error, origin) {
	$.unblockUI();
	log('An error has occurred on the signature browser component: ' + message, error);
	addAlert('danger', 'An error has occurred on the signature browser component: ' + message);
}

function onServerError(jqXHR, textStatus, errorThrown) {
	$.unblockUI();
	var error;
	if (jqXHR && jqXHR.responseJSON && jqXHR.responseJSON.message) {
		error = jqXHR.responseJSON.message;
	} else {
		error = errorThrown;
	}
	log('An error has occurred on the server: ' + error);
	addAlert('danger', 'An error has occurred on the server: ' + error);
}

