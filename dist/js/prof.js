/*
 * Specific Javascript file associated to profile.php
 */

$(document).ready(function(){
	
	// Gestion du cas d'un unique représentant légal
	$('.modal-content').on('click', 'input[id=only1LegalRepresentative]', function(event) {
		$('select[name=gender2]').prop('disabled', this.checked);
		$('input[name=name2]').prop('readonly', this.checked);
		$('input[name=firstname2]').prop('readonly', this.checked);
		$('input[name=email2]').prop('readonly', this.checked);
		$('select[name=link2]').prop('disabled', this.checked);
		$('input[name=prof2]').prop('readonly', this.checked);
		$('input[name=phone21]').prop('readonly', this.checked);
		$('input[name=phone22]').prop('readonly', this.checked);
		$('input[name=address2]').prop('readonly', this.checked);
		$('input[name=cp2]').prop('readonly', this.checked);
		$('input[name=city2]').prop('readonly', this.checked);
	});
	
	// Gestion de l'autofocus à l'ouverture de le fenêtre modal pour la mise à jour du Profil
	$('#myModal').on('shown.bs.modal', function () {
		$('#myModal').find('.modal-content').find('input[autofocus]').select();
	});
	
	// Use the jQuery to post form
	$('.modal-content').on('click', 'button[id=btnSaveEditFam]', function(event) {
		submitEditFamily();
	});
	$('.modal-content').on('click', 'button[id=btnSaveQFGrid]', function(event) {
		submitSaveQFGrid();
	});
	$('.modal-content').on('click', 'button[id=btnValidQFGrid]', function(event) {
		submitValidQFGrid();
	});
	
	// Use the jQuery to post form 
    $('.modal-content').on('submit', '.form-tab', function(event) {
        event.preventDefault();
		var formName = $('.modal-content').find('input[type=submit]').attr('name');
		if ( formName == 'btnSubmitEditFam' ) {
			submitEditFamily();
		} else if ( formName == 'btnSubmitQFGrid' ) {
			submitSaveQFGrid();
		} else if ( formName == 'btnValidQFGrid' ) {
			submitValidQFGrid();
		}
    });
	
	// Script to manage eye on password input
	$('.modal-content').on('click', '.toggle-password', function(event) {
		$(this).toggleClass("fa-eye fa-eye-slash");
		var input = $($(this).attr("toggle"));
		if (input.attr("type") == "password") {
			input.attr("type", "text");
		} else {
			input.attr("type", "password");
		}
	});
	
	// Script déclenché à la sélection d'une nouvelle année dans la feneêtre modale 'Grille de scolarité'
	$('.modal-content').on('change', '#financialTabYearSelector select[name=y]', function(event) {
		var y = $('#financialTabYearSelector select[name=y]').val();
		var href = '../inc/doEditGrilleScolarite.php?y=' + y;
		$('#myModal').find('.modal-content').load(href, function() {
			// This gets executed when the content is loaded
			$('.modal-content select').each(function(){
				$(this).find('option[value="'+$(this).attr("value")+'"]').prop('selected', true);
			});
			
		});
	});
});

// Function to submit form doEditFamily.php
function submitEditFamily() {
	var myform = $(".form-tab");
	var formValues= myform.serialize();
	var actionUrl = myform.attr("action");
	
	$.post(actionUrl, formValues, function(data){
		// Display the returned data in browser
		//$("#result").html(data);
		var obj = JSON.parse(data);
		var msg_error = obj['msg_error'];
		var msg_success = obj['msg_success'];
		if( msg_success != '' ) {
			$('.modal-content').find('.result').find('.alert').html(msg_success);
			$('.modal-content').find('.result').find('.alert').removeClass('alert-danger');
			$('.modal-content').find('.result').find('.alert').addClass('alert-success');
			$('.modal-content').find('.result').show();
			setTimeout(function() { $('#myModal').modal('hide'); }, 1000);
		} else {
			$('.modal-content').find('.result').find('.alert').html(msg_error);
			$('.modal-content').find('.result').find('.alert').removeClass('alert-success');
			$('.modal-content').find('.result').find('.alert').addClass('alert-danger');
			$('.modal-content').find('.result').show();
		}
	});
}


// Function to submit form doEditGrilleScolarite.php
function submitSaveQFGrid() {
	var myform = $(".form-tab");
	var formValues= myform.serializefiles();
	var actionUrl = myform.attr("action");
	
	$.ajax({
		url: actionUrl,
		type: "POST",
		processData: false,
		contentType: false,
		data: formValues,
        success: function (data, status) {
			$('#myModal').find('.modal-content').html(data);
        },
        error: function (xhr, desc, err) {
            console.log('error');
        }
	});
	$("#myModal").scrollTop(0);
}

// Function to validate form doEditGrilleScolarite.php
function submitValidQFGrid() {
	$('#myModal').find('.modal-content').find('input[name=qfFamValid]').val('1');
	submitSaveQFGrid();
}

//USAGE: $("#form").serializefiles();
(function($) {
	$.fn.serializefiles = function() {
		var obj = $(this);
		/* ADD FILE TO PARAM AJAX */
		var formData = new FormData();
		$.each($(obj).find("input[type='file']"), function(i, tag) {
			$.each($(tag)[0].files, function(i, file) {
				formData.append(tag.name, file);
			});
		});
		var params = $(obj).serializeArray();
		$.each(params, function (i, val) {
			formData.append(val.name, val.value);
		});
		return formData;
	};
})(jQuery);