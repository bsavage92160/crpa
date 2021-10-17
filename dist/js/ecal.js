/*
 * Specific Javascript file associated to edit-calendar.php
 */

// Listen for click on toggle checkbox
$('#select-all').click(function(event) {   
	if(this.checked) {
        // Iterate each checkbox
        $(':checkbox').each(function() {
            if( !this.checked) $(this).click();
        });
    } else {
        $(':checkbox').each(function() {
            if( this.checked) $(this).click();
        });
    }
});

$('.calendar_table input').click(function( event ) {
  event.stopImmediatePropagation();
});

$('.calendar_table label').click(function( event ) {
  event.stopImmediatePropagation();
});

// Script en cliquant sur la fonction détail d'un jour du calendrier
$('.more_day').click(function( event ) {
	var wd = $(this).attr('data');
	var cus = $(this).closest('td').find('input[type=hidden][name^=cus]').val();
	var alb = $(this).closest('td').find('input[type=hidden][name^=alb]').val();
	var href = encodeURI('../inc/doEditCalendarMore.php?wd=' + wd + '&cus=' + cus + '&alb=' + alb);
	$('#myModal').find('.modal-content').load(href);
	$('#myModal').modal();
	event.stopImmediatePropagation();
});

$('.calendar_table').on('click', 'td:not(.case_empty)', function(event) {
	$(this).find('input[type=checkbox]').click();
});

$('.calendar_table input').on('change', function(event) {
    $(this).closest('td').toggleClass('case_inactive');
});

// Fonction permettant d'activer / désactiver le libellé de saisie du nom de l'activité dans la fenêtre modale
$('.modal-content').on('change', 'input[name=cust_act]', function(event) {
	if( this.checked )	$('#myModal').find('input[name=act_lbl]').attr('disabled', false);
	else				$('#myModal').find('input[name=act_lbl]').attr('disabled', true);
	$('#myModal').find('input[name=act_lbl]').focus();
});

// Fonction permettant en cliquant sur le bouton 'save' de la fenêtre modale
$('.modal-content').on('submit', '#moreDayModal', function(event) {
	event.preventDefault();
	saveDayDetails();
});
$('.modal-content').on('click', 'button[id=btnSave]', function(event) {
	saveDayDetails();
});
function saveDayDetails() {
	var myform = $("#moreDayModal");
	var formValues= myform.serialize();
	var actionUrl = myform.attr("action");
	var wd = $('#myModal').find('input[name=cust_wd]').val();
	var div = $('.calendar_table').find('input[name*=' + wd + ']').closest('.case_act').find('.details_day');
	$.post(actionUrl, formValues, function(data){
		div.html(data);
		$("#myModal .close").click()
	});
}