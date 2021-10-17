<?php
/**************************************************
 * Code transaction : q/ersa
 **************************************************/  

require_once('../services/AccessManager.php');
session_start ();
if( isset($_SESSION ['user']) ) $user = $_SESSION ['user'];
if( !isset($user) ) header('location:../q/logi');

// Initialize Reservation Controler
require_once('../services/ReservationControler.php');
$obj=new ReservationControler();
$obj->initialize( $user->isAdmin() || $user->isSuper(), $user->getFamilyId());

// Control child access
 if( $user->isFamily() ) {
	 if( !$user->canAccessToChild($obj->currentChildId) ) {
		$obj->currentChildId = $obj->getDefaultChildId();
	 }
}

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
<link href="../dist/css/colorPicker.css" rel="stylesheet" >
<link href="../dist/css/app.css"	rel="stylesheet" type="text/css">
<link href="../dist/css/ersa.css"	rel="stylesheet" type="text/css">
<style>
/*.alert {width:500px; height:30px; padding-top: 0px; padding-bottom: 0px; vertical-align: middle; display: inherit;}*/
</style>
<title>Réservation Activités CLAE</title>
</head>

<body>
<div id="wrapper">

<!-- Navigation -->
<?php include('../inc/doLeftbar.php'); ?>

<div id="page-wrapper">
	<div id="result" style=""><div><div><div><div class="alert alert-success" ><?php echo $obj->success_msg; ?></div></div></div></div></div>
	
	<table style="white-space: nowrap; width: 100%;margin:0;">
		<tr><td style="text-align: left; height: 35px;"><div style="font-size: 18px;font-weight: bold;">Réservation Activités CLAE</div></td></tr>
		<tr><td><?php $obj->prebuild_calendar(); ?></td></tr>
	</table>
	
	<form method="post" >
	<?php $obj->build_calendar(); ?> 
	<table style="width: 100%;table-layout: fixed;border: 1px;"><tr>
		<td style="vertical-align: top;">
				<small><i>
				<label class="lb-sm">Tout cocher sur ce mois &nbsp;&nbsp;&nbsp;</label>
				<label class="lb-sm"><input type="checkbox" onClick="Tout cocher" id="select-all-mat" />&nbsp;Matin</label> &nbsp;
				<label class="lb-sm"><input type="checkbox" onClick="Tout cocher" id="select-all-soi" />&nbsp;Soir</label> &nbsp;
				<label class="lb-sm"><input type="checkbox" onClick="Tout cocher" id="select-all-mid" />&nbsp;Mercredi Midi</label> &nbsp;
				<label class="lb-sm"><input type="checkbox" onClick="Tout cocher" id="select-all-rep" />&nbsp;Mercredi Repas</label> &nbsp;
				<label class="lb-sm"><input type="checkbox" onClick="Tout cocher" id="select-all-apm" />&nbsp;Mercredi Après-Midi</label>
				</i></small>
				<br>
				<?php echo $obj->build_same_choice_forms(); ?>
		</td>
		<td style="vertical-align: top;">
			<div style="width:100%; text-align:right;font-style: italic;">
			<button class="btn btn-sm btn-primary" style="font-size: 14px; font-style: normal;" onclick="" disabled><span class="fa fa-repeat" aria-hidden="true"></span>&nbsp;&nbsp;Périodicité</button>
			</div>
		</td>
	</tr>
	</table>
	</form>
	<br/>&nbsp;&nbsp;
			
</div>
</div>
<script src="../dist/js/jquery.min.js"></script>
<script src="../dist/js/bootstrap.min.js"></script>
<script src="../dist/js/metisMenu.min.js"></script>
<script src="../dist/js/sb-admin-2.js"></script>
<script src="../dist/js/app.js"></script>
<script src="../dist/js/ersa.js"></script>

<?php if( $obj->success_msg != "") { ?>
<script>
$('#result').show();
setTimeout(function() { $('#result').hide(); }, 1500);
</script>
<?php } ?>

</body>
</html>