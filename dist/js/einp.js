/*
 * Specific Javascript file associated to edit-invoice-period.php
 */

$(document).ready(function() {
    // Gestion de l'autofocus à l'ouverture de le fenêtre modal pour la mise à jour du Profil
	$('#myModal').on('shown.bs.modal', function () {
		$('#myModal').find('.modal-content').find('input[autofocus]').select();
	});
	
	// Use the jQuery to post form
	$('.modal-content').on('click', 'button[id=saveBtn]', function(event) {
		 $('#myForm').find('input[type=submit]').click();
	});
} );