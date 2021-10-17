/*
 * Specific Javascript file
 */

$(document).ready(function(){
	// Remove FreeWHA ads !!
	$('a[href*=freewebhostingarea]').closest('div').remove();
});

//Sript to load external content for modal
$('.ls-modal').on('click', function(e){
	e.preventDefault();
	$('#myModal').modal('show').find('.modal-content').load($(this).attr('href'), function() {
		
		// This gets executed when the content is loaded
		$('.modal-content select').each(function(){
			$(this).find('option[value="'+$(this).attr("value")+'"]').prop('selected', true);
		});
		
	});
});

// Script to automaticaly select the option associated to value attributes in select
$('select').each(function(){
	$(this).find('option[value="'+$(this).attr("value")+'"]').prop('selected', true);
});