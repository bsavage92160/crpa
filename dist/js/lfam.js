/*
 * Specific Javascript file associated to list-family.php
 */

$(document).ready(function() {
    $('#family-table').DataTable( {
        displayStart: _displayStart,
		oSearch: {"sSearch": _sSearch},
		ordering: true,
		order: [[ 1, "asc" ]],
		processing: true,
        serverSide: true,
        ajax: {
            url: "../scripts/server_processing_family.php",
            data: function ( d ) {
				d.active = $('#chkActive').is(':checked') ? '1' : '0';
				d.desactive = $('#chkInactive').is(':checked') ? '1' : '0';
            }
        },
		columns: [
                { data: "id", },
                { data: "na", },
                { data: "f1", orderable: false, },
                { data: "f2", orderable: false, },
                { data: "ch", orderable: false, },
                { data: "qf", },
				{ data: "bl", render: $.fn.dataTable.render.number( ' ', '.', 2, '', ' €') },
        ],
        columnDefs: [ {
            targets: 1,
			render: function (data, type, row, meta) {
				var _fa = row['id'];
				var _bkparam = 'st=' + meta.settings._iDisplayStart +
							  '&sr=' + meta.settings.oPreviousSearch.sSearch +
							  '&ac=' + ($('#chkActive').is(':checked') ? '1' : '0') +
							  '&in=' + ($('#chkInactive').is(':checked') ? '1' : '0');
				var href = "javascript:editFamily('" + _fa + "', " +
												 "'" + _bkparam + "')";
				return '<a href="' + href + '">' + data + '</a>';
			},
		}],
		lengthMenu: [[20, 50, 100, -1], [20, 50, 100, "All"]],
		pageLength: 20,
		rowCallback: function(row, data, index) {
			if( data['ac'] == 0 ) {
				$('td:eq(0)', row).addClass('inactive');
				$('td:eq(1)', row).addClass('inactive');
				$('td:eq(2)', row).addClass('inactive');
				$('td:eq(3)', row).addClass('inactive');
				$('td:eq(4)', row).addClass('inactive');
				$('td:eq(5)', row).addClass('inactive');
				$('td:eq(6)', row).addClass('inactive');
			}
		},
		dom: 'lBfrtip',
		buttons: [
			'excel',
        ],
    } );
	$('#family-table_filter label input').focus();
} );

$('#chkActive').click(function(event) {
	$('#family-table').DataTable().ajax.reload();
});

$('#chkInactive').click(function(event) {
	$('#family-table').DataTable().ajax.reload();
});

function editFamily(_fa, _bkparam) {
	$('#efamForm').find('input[name=fa]').val(_fa);
	$('#efamForm').find('input[name=bkparam]').val(_bkparam);
	$('#efamForm').submit();
}