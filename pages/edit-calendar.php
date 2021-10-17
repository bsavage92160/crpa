<?php
/**************************************************
 * Code transaction : q/ecal
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

// Initialize CalendarControler
require_once('../services/CalendarControler.php');
$obj=new CalendarControler();
$obj->initialize();
$obj->parse_request();
?><!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr" lang="fr">
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="shortcut icon" href="../images/favicon.ico">
<link href="../dist/css/bootstrap.css" rel="stylesheet">
<link href="../dist/css/metisMenu.min.css" rel="stylesheet">
<link href="../dist/css/font-awesome.min.css" rel="stylesheet" type="text/css">
<link href="../dist/css/sb-admin-2.css" rel="stylesheet" type="text/css">
<link href="../dist/css/app.css"	rel="stylesheet" type="text/css">
<link href="../dist/css/ecal.css"	rel="stylesheet" type="text/css">
<title>Edition Calendrier CLAE</title>
</head>

<body>
<form method="post" >
<input type="hidden" name="wd" value="<?php echo $obj->currentNoJour; ?>" />
<div id="wrapper">

<!-- Navigation -->
<?php include('../inc/doLeftbar.php'); ?>

<div id="page-wrapper">
	<div id="result" style=""><div><div><div><div class="alert alert-success" ><?php echo $obj->success_msg; ?></div></div></div></div></div>

	<table style="width: 100%;table-layout: fixed;border: 1px;"><tr>
		<td style="text-align: left; height: 35px;"><div style="text-align:left;font-size: 18px;font-weight: bold;">Edition Calendrier CLAE</div></td>
		<td><div style="width:100%; text-align:right;font-style: italic;">
		<input type="submit" value="Enregistrer" name="submit" class="btn btn-success"></div>
	</td></tr></table>
	
	<!-- Tableau Calendrier -->
	<?php $obj->build_calendar(); ?>
	
	<table style="width: 100%;table-layout: fixed;border: 1px;"><tr>
		<td style="vertical-align: top;">
				<small><i>
				<label class="lb-sm"><input type="checkbox" onClick="Tout cocher" id="select-all" />&nbsp;Tout cocher / décocher</label> &nbsp;
				</i></small>
		</td>
		<td><div style="width:100%; text-align:right;font-style: italic;">
		<input type="submit" value="Enregistrer" name="submit" class="btn btn-success"></div>
	</td></tr></table>
</div>
</div>
</form>

<!-- Modal HTML -->
<div style="width: 100%"-->
<div id="myModal" class="modal fade modal-dialog-centered">
<div class="modal-dialog" style="width: 80%;"><div class="modal-content"></div></div>
</div>
</div>

<script src="../dist/js/jquery.min.js"></script>
<script src="../dist/js/bootstrap.min.js"></script>
<script src="../dist/js/metisMenu.min.js"></script>
<script src="../dist/js/sb-admin-2.js"></script>
<script src="../dist/js/app.js"></script>
<script src="../dist/js/ecal.js"></script>

<?php if( $obj->success_msg != "") { ?>
<script>
$('#result').show();
setTimeout(function() { $('#result').hide(); }, 1500);
</script>
<?php } ?>

</body>
</html>