/*
 * Specific Javascript file associated to edit-releve.php
 */

String.prototype.replaceAt=function(index, char) {
    var a = this.split("");
    a[index] = char;
    return a.join("");
}

String.prototype.charAt=function(index) {
    var a = this.split("");
    return a[index];
}

$('#load_resa').click(function(event) {
	$('#confirmationModal').modal('show');
});

var _currentDivElt = null;
var _currentInputElt = null;
var _currentTdIndex = null;
var _currentChkElt = null;
var _currentPicker = null;

$('.colorPicker-swatch-container').click(function(event) {
	var color = $( this ).attr( 'data-color' );
	var val   = $( this ).attr( 'data-val' );
	if( _currentDivElt != null)	_currentDivElt.css('background-color', color);
	if( _currentInputElt != null && _currentTdIndex != null) {
		var vals = _currentInputElt.val();
		_currentInputElt.val(vals.replaceAt(_currentTdIndex, val));
	}
	if( _currentChkElt != null  && val > 0)	{
		_currentChkElt.removeClass('checked1 checked2 checked3 checked4 checked5');
		_currentChkElt.addClass('checked checked' + val);
	}
	$('#colorPicker-matin').hide();
	$('#colorPicker-soir').hide();
});

$('#releve-table > tbody').on('click', 'td:not(:first-child):not(.inactive_per)', function(event) {
	$('#colorPicker-matin').hide();
	$('#colorPicker-soir').hide();
	
	_currentInputElt = $( this ).closest('td').closest('tr').find('input[type=hidden][name^=rel]');
	_currentTdIndex  = $( this ).closest('td').attr( 'data-idx' );
	
	if( $( this ).find('span.ctm').hasClass( "checked" ) ) {
		$( this ).find('span.ctm').removeClass( "checked checked1 checked2 checked3 checked4 checked5" );
		$( this ).find('span.cto').css('background-color', '#fff');
		if( _currentInputElt != null && _currentTdIndex != null) {
			var vals = _currentInputElt.val();
			_currentInputElt.val(vals.replaceAt(_currentTdIndex, '0'));
		}
	} else {
		$( this ).find('span.ctm').addClass( "checked" );
		if( _currentInputElt != null && _currentTdIndex != null) {
			var vals = _currentInputElt.val();
			_currentInputElt.val(vals.replaceAt(_currentTdIndex, '1'));
		}
	}
});

$('#releve-table > tbody').on('contextmenu', 'td:not(:first-child):not(.inactive_per)', function(event) {
	event.preventDefault();
	event.stopPropagation();
	$('#colorPicker-matin').hide();
	$('#colorPicker-soir').hide();
	_currentDivElt   = $( this ).closest('td').find('span.cto');
	_currentInputElt = $( this ).closest('td').closest('tr').find('input[type=hidden][name^=rel]');
	_currentTdIndex  = $( this ).closest('td').attr( 'data-idx' );
	_currentChkElt   = $( this ).closest('td').find('span.ctm');
	_currentPicker   = null;
	if( $( this ).closest('td').find('span.ctb').hasClass('mat') ) {
		_currentPicker = $('#colorPicker-matin');
	} else if( $( this ).closest('td').find('span.ctb').hasClass('soi') ) {
		_currentPicker = $('#colorPicker-soir');
	}
	setPicker();
});

$('#releve-table > tbody').on('click', 'td', function(event) {
	$( this ).closest('.colorPicker-palette').hide();
});

$('.colorPicker-palette .close').on('click', function(event) {
	$('#colorPicker-matin').hide();
	$('#colorPicker-soir').hide();
});

$(window).resize(function(){
      setPicker();
});

function setPicker() {
	if( _currentInputElt == null || _currentDivElt == null || _currentTdIndex == null )
		return;
	var vals = _currentInputElt.val();
	var val = vals.charAt(_currentTdIndex);
	var pos = _currentDivElt.position();
	var offsetX = parseInt($('#page-wrapper').css('margin-left'));
	var x   = pos.left + offsetX;
	var y   = pos.top + _currentDivElt.height() + 125;
	if( x + 160 > ($(window).width()+window.scrollX) )	x = $(window).width() + window.scrollX - 160;
	$( _currentPicker ).css( { left: x + 'px', top: y + 'px' } );
	$( _currentPicker ).find('div div').removeClass("active");
	$( _currentPicker ).find('div div[data-val=' + val + ']').addClass("active");
	$( _currentPicker ).show();
}

/***********************************************************************************
 * Manage Infinite Scroll Pagination with AJAX
 ***********************************************************************************/
$(window).data('ajaxready', true);
$('#footer-tab').hide();
$('#loader').show();
appendReleveTable();

$('#nv-page-wrapper').scroll(function() {
	// On teste si ajaxready vaut false, auquel cas on stoppe la fonction
	if ($(window).data('ajaxready') == false) return;
	appendReleveTable();
});

$(document).ready(function(){
	
	// Use the jQuery to post form 
    $("form").on("submit", function(event){
        event.preventDefault();
 
        var formValues= $(this).serialize();
        var actionUrl = $(this).attr("action");
		
		$('#result').show();
		$('#result').find('.alert').html('Sauvegarde en cours ...');
		$('#result').find('.alert').removeClass('alert-success');
		$('#result').find('.alert').addClass('alert-warning');
 
        $.post(actionUrl, formValues, function(data){
            // Display the returned data in browser
            $('#result').find('.alert').html(data);
			$('#result').find('.alert').removeClass('alert-warning');
			$('#result').find('.alert').addClass('alert-success');
			$('#result').show();
			setTimeout(function() { $('#result').hide(); }, 2500);
        });
    });
	
    $("#result").on("click", function(event){
		$("#result").find('div').css("visibility", "hidden");
    });
});

function appendReleveTable() {
	$(window).data('ajaxready', false);
	var nextPage = parseInt($('#pageno').val()) + 1;
	var wd       = parseInt($('#wd').val());
	$.ajax({
		type: 'POST',
		url: '../scripts/server_processing_releve.php',
		data: { pageno: nextPage, wd: wd },
		success: function(data){
			if(data != ''){							 
				$('#releve-table tbody').append(data);
				$('#pageno').val(nextPage);
			} else {								 
				$('#loader').hide();
				$('#footer-tab').show();
			}
			$(window).data('ajaxready', true);
		}
	});
}