<?php
/**************************************************
 * Code transaction : q/erlv
 **************************************************/  

require_once('../services/AccessManager.php');
session_start ();
if( isset($_SESSION ['user']) ) $user = $_SESSION ['user'];
if( !isset($user) ) header('location:../q/logi');

// Access control
if( !$user->isAdmin() && !$user->isSuper() && !$user->isAnim() ) {
	echo "Acess denied on this page - Please contact your administrator";
	return;
}

// Initialize ReleveControler
require_once('../services/ReleveControler.php');
$obj=new ReleveControler();
$obj->initialize();
//echo $obj->currentNoJour;
$obj->parse_loadres_request();
//$obj->parse_request();

?><!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr" lang="fr"><head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="shortcut icon" href="../images/favicon.ico">
<link href="../dist/css/bootstrap.css" rel="stylesheet">
<link href="../dist/css/metisMenu.min.css" rel="stylesheet">
<link href="../dist/css/font-awesome.min.css" rel="stylesheet" type="text/css">
<link href="../dist/css/sb-admin-2.css" rel="stylesheet" type="text/css">
<link href="../dist/css/colorPicker.css" rel="stylesheet">
<link href="../dist/css/bootstrap-datetimepicker.min.css" rel="stylesheet" media="screen">
<link href="../dist/css/app.css" rel="stylesheet" type="text/css">
<link href="../dist/css/erlv.css" rel="stylesheet" type="text/css">
<title>Pointage Activités CLAE</title>

<body style="overflow-x: hidden; overflow-y: hidden;">

<form method="post" action="../scripts/server_processing_releve_form.php">
<input type="hidden" name="wd" id="wd" value="<?php echo $obj->currentNoJour; ?>" />

<div id="wrapper">

<!-- Navigation -->
<?php include('../inc/doLeftbar.php'); ?>

<div id="page-wrapper" style="min-height: 627px; padding-right: 0px;">
<div><div>
	<div id="result" style=""><div><div><div><div class="alert" ></div></div></div></div></div>
	
	<table style="white-space: nowrap; width: 100%">
		<tbody><tr>
			<td style="text-align: left; height: 35px;"><div style="font-size: 18px;font-weight: bold;">Pointage Activités CLAE</div></td>
			<td style="vertical-align:middle;text-align: right">
				<button type="button" class="btn btn-default btn-sm ls-modal" style="border:none;" href="../inc/doEditLoadReservationForm.php?wd=<?php echo $obj->currentNoJour; ?>"><i class="glyphicon glyphicon-download-alt"></i>&nbsp;Chargement des données de réservation</button>
			</td>
		</tr>
	</tbody></table>
	<?php
	$limit = "LIMIT 0, 25";
	$obj->build_calendar(true, $limit);
	?>
	<div style="width:100%; text-align:center; font-style: italic;">
		<input type="hidden" id="pageno" value="1">
		<img id="loader" src="../images/ajax-loader.gif" style="height: 50px; display: none;" alt="Ajax loading ...">
	</div>
	<table id="footer-tab" style="width: 100%; table-layout: fixed; border: 1px; margin-bottom: 10px; display: table;"><tbody><tr>
		<td><div style="width:100%; text-align:right; font-style: italic;"><input type="submit" value="Enregistrer" name="submit" class="btn btn-success"></div></td>
	</tr></tbody></table>
</div></div>
</div></div></div>
</form>

<!-- Modal HTML -->
<div style="width: 100%"-->
<div id="myModal" class="modal fade modal-dialog-centered">
<div class="modal-dialog"><div class="modal-content"></div></div>
</div>
</div>


<div class="colorPicker-palette dropdown-menu" id="colorPicker-matin" style="position: absolute; display: none; padding: 0px;">
	<div style="display: inline-flex; width: 100%; flex-wrap: nowrap; justify-content: space-between; padding: 2px;">
		<div><b>Cas particulier ...</b></div>
		<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
	</div>
	<div style="padding: 4px;">
		<div class="colorPicker-swatch-container" data-color="#ffffff" data-val="1"><div class="colorPicker-swatch" style="background: rgb(255, 255, 255);">&nbsp;&nbsp;&nbsp;&nbsp;</div><div class="colorPicker-swatch-text">N/A</div></div>
		<div class="colorPicker-swatch-container" data-color="#00ff00" data-val="2"><div class="colorPicker-swatch" style="background: rgb(0, 255, 0);">&nbsp;&nbsp;&nbsp;&nbsp;</div><div class="colorPicker-swatch-text">Gratuit</div></div>
	</div>
</div>

<div class="colorPicker-palette dropdown-menu" id="colorPicker-soir" style="position: absolute; display: none; padding: 0px;">
	<div style="display: inline-flex; width: 100%; flex-wrap: nowrap; justify-content: space-between; padding: 2px;">
		<div><b>Cas particulier ...</b></div>
		<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
	</div>
	<div style="padding: 2px;">
		<div class="colorPicker-swatch-container" data-color="#ffffff" data-val="1"><div class="colorPicker-swatch" style="background: rgb(255, 255, 255);">&nbsp;&nbsp;&nbsp;&nbsp;</div><div class="colorPicker-swatch-text">N/A</div></div>
		<div class="colorPicker-swatch-container" data-color="#ffff00" data-val="3"><div class="colorPicker-swatch" style="background: rgb(255, 255, 0);">&nbsp;&nbsp;&nbsp;&nbsp;</div><div class="colorPicker-swatch-text">Retard 15'</div></div>
		<div class="colorPicker-swatch-container" data-color="#ff9900" data-val="4"><div class="colorPicker-swatch" style="background: rgb(255, 153, 0);">&nbsp;&nbsp;&nbsp;&nbsp;</div><div class="colorPicker-swatch-text">Retard 30'</div></div>
		<div class="colorPicker-swatch-container" data-color="#ff0000" data-val="5"><div class="colorPicker-swatch" style="background: rgb(255, 0, 0);">&nbsp;&nbsp;&nbsp;&nbsp;</div><div class="colorPicker-swatch-text">Retard 1h</div></div>
	</div>
</div>

<script src="../dist/js/jquery.min.js"></script>
<script src="../dist/js/bootstrap.min.js"></script>
<script src="../dist/js/metisMenu.min.js"></script>
<script src="../dist/js/sb-admin-2.js"></script>
<script src="../dist/js/moment.js" charset="UTF-8"></script>
<script src="../dist/js/bootstrap-datetimepicker.js" charset="UTF-8"></script>
<script src="../dist/js/bootstrap-datetimepicker.fr.js" charset="UTF-8"></script>
<script src="../dist/js/app.js"></script>
<script src="../dist/js/erlv.js"></script>
<script>
	$('.form_date')
	.datetimepicker({
        language:  'fr',
        weekStart: 1,
        todayBtn:  1,
		autoclose: 1,
		todayHighlight: 1,
		startView: 2,
		minView: 2,
		forceParse: 0,
		endDate: new Date(),
    })
	.on('changeDate', function(ev){
		var wd = moment(ev.date.valueOf()).format('YYYYMMDD');
		location.href = '?wd=' + wd;
	})
	;
</script>
</body>
</html>