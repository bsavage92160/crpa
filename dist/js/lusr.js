/*
 * Specific Javascript file associated to listStyleType-user.php
 */
var editor; // use a global for the submit and return data rendering in the examples

$(document).ready(function() {
	
    $('#user-table').DataTable( {
        "displayStart": _displayStart,
		"oSearch": {"sSearch": _sSearch},
		"ordering": false,
		"processing": true,
        "serverSide": true,
        "ajax": {
            "url": "../scripts/server_processing_user.php",
            "data": function ( d ) {
				d.active = $('#chkActive').is(':checked') ? '1' : '0';
				d.desactive = $('#chkInactive').is(':checked') ? '1' : '0';
            }
        },
		"columns": [
                { "data": "id" },
                { "data": "na" },
                { "data": "fn" },
				{ "data": "ma" },
                { "data": "an" },
                { "data": "ad" },
        ],
        "columnDefs": [ {
            "targets": 0,
			"render": function (data, type, row, meta) {
				var bkparam = 'st=' + meta.settings._iDisplayStart +
							  '&sr=' + meta.settings.oPreviousSearch.sSearch;
				bkparam = encodeURIComponent(bkparam);
				return '<a href="edit-user.php?id=' + row['id'] + '&bk=lstus&bkparam=' + bkparam + '">' + data + '</a>';
			},
		}],
		"lengthMenu": [ 10, 20, 50, 75, 100 ],
		"pageLength": 20,
    } );
	
	
	$('#user-table').on('draw.dt', function(){ 
		$('#user-table').Tabledit({
			url: 'example.php',
			columns: {
				identifier: [0, 'id'],
				editable: [[1, 'na'], [2, 'fn'], [3, 'ma'], [4, 'an'], [5, 'ad'] ]
			},
            buttons: {
                edit: {
                    class: 'btn btn-sm btn-default my-btn',
                    html: '<span class="glyphicon glyphicon-pencil"></span>',
                    action: 'edit'
                },
                delete: {
                    class: 'btn btn-sm btn-default my-btn',
                    html: '<span class="glyphicon glyphicon-trash"></span>',
                    action: 'delete'
                },
            },
			onDraw: function() {
				console.log('onDraw()');
			},
			onSuccess: function(data, textStatus, jqXHR) {
				console.log('onSuccess(data, textStatus, jqXHR)');
				console.log(data);
				console.log(textStatus);
				console.log(jqXHR);
			},
			onFail: function(jqXHR, textStatus, errorThrown) {
				console.log('onFail(jqXHR, textStatus, errorThrown)');
				console.log(jqXHR);
				console.log(textStatus);
				console.log(errorThrown);
			},
			onAlways: function() {
				console.log('onAlways()');
			},
			onAjax: function(action, serialize) {
				console.log('onAjax(action, serialize)');
				console.log(action);
				console.log(serialize);
			},
		});
	});
	
	$('#user-table_filter label input').focus();
} );

$('#chkActive').click(function(event) {
	$('#user-table').DataTable().ajax.reload();
});

$('#chkInactive').click(function(event) {
	$('#user-table').DataTable().ajax.reload();
});