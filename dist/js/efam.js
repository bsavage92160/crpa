/*
 * Specific Javascript file associated to edit-famil.php
 */

$('.nav-tabs a').click(function (e) {
     e.preventDefault();
     var tab = $(this).attr('href');
	 if( tab == "#main") {
		 $("#editForm").find('input[name=general]').val('1');
		 $("#editForm").find('input[name=qfgrid]').val('0');
		 $("#editForm").find('input[name=access]').val('0');
		 $("#editForm").find('input[name=finance]').val('0');
	 } else if( tab == "#admin") {
		 $("#editForm").find('input[name=general]').val('0');
		 $("#editForm").find('input[name=qfgrid]').val('1');
		 $("#editForm").find('input[name=access]').val('0');
		 $("#editForm").find('input[name=finance]').val('0');
	 } else if( tab == "#access") {
		 $("#editForm").find('input[name=general]').val('0');
		 $("#editForm").find('input[name=qfgrid]').val('0');
		 $("#editForm").find('input[name=access]').val('1');
		 $("#editForm").find('input[name=finance]').val('0');
	 } else if( tab == "#finance") {
		 $("#editForm").find('input[name=general]').val('0');
		 $("#editForm").find('input[name=qfgrid]').val('0');
		 $("#editForm").find('input[name=access]').val('0');
		 $("#editForm").find('input[name=finance]').val('1');
	 }
});

$('input[type=text][name=faname]').on('change', function(event) {
	var val = $('input[type=text][name=pseudo]').val();
	if( val.trim() == '' )
		$('input[type=text][name=pseudo]').val($(this).val().toLowerCase());
});

$(".toggle-password").click(function() {
	$(this).toggleClass("fa-eye fa-eye-slash");
	var input = $($(this).attr("toggle"));
	if (input.attr("type") == "password") {
		input.attr("type", "text");
	} else {
		input.attr("type", "password");
	}
});

$(document).ready(function(){
	// Remove FreeWHA ads !!
	$('a[href*=freewebhostingarea]').closest('div').remove();
	
	
	// Script déclenché à la sélection d'une nouvelle année dans la feneêtre modale 'Grille de scolarité'
	$('#financialTabYearSelector').on('change', 'select[name=y]', function(event) {
		var y = $('#financialTabYearSelector select[name=y]').val();
		$('#financialTabChangeYear').find('input[name=y]').val(y);
		$('#financialTabChangeYear').closest('form').submit();
	});
});

//----------------------------------------
//Dedicated to Financial Form
//----------------------------------------
$('select').each(function(){
	$(this).find('option[value="'+$(this).attr("value")+'"]').prop('selected', true);
});

$('input[type=file]').change(function() {
    if(this.files[0].size > 2097152){
       alert("File size limited to 2 Mo !");
       this.value = "";
    };
});

$('.form_date').datetimepicker({
	language:  'fr',
	weekStart: 1,
	todayBtn:  1,
	autoclose: 1,
	todayHighlight: 1,
	startView: 2,
	minView: 2,
	forceParse: 0
});

//----------------------------------------
//Dedicated to Datetime picker for birthday
//----------------------------------------
$(".form_date").find('input[type=text]').change(function() {
	var dt = String($(this).val());
	var i = dt.indexOf('/');
	var d = '00' + dt.substring(0, i);
	var j = dt.indexOf('/', i+1);
	var m = '00' + dt.substring(i+1, j);
	var y = '0000' + dt.substring(j+1);
	var ndt = y.substring(y.length-4) + m.substring(m.length-2) + d.substring(d.length-2);
	$(this).parent().parent().find('input[id^="inDT_"]').val(ndt);
});