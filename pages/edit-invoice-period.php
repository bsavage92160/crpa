<?php
/**************************************************
 * Code transaction : q/einp
 **************************************************/  
 
require_once('../services/AccessManager.php');
session_start ();
if( isset($_SESSION ['user']) ) $user = $_SESSION ['user'];
if( !isset($user) ) header('location:../q/logi');

// Access control
if( !$user->isAdmin() && !$user->isSuper()) {
	echo "Acess denied on this page - Please contact your administrator";
	return;
}

require_once('../services/InvoicePeriodControler.php');
$obj=new InvoicePeriodControler();
$obj->initialize();
$obj->parse_request();

$msg_error = $obj->msg_error;

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
<link href="../dist/css/bootstrap-datetimepicker.min.css" rel="stylesheet" media="screen">
<link href="../dist/css/app.css"	rel="stylesheet" type="text/css">
<link href="../dist/css/einp.css"	rel="stylesheet" type="text/css">
<?php if( $obj->close ) { ?>
<style>
#result > div > div { font-size: 1.5rem; }
</style>
<?php } ?>
<title>Période de facturation</title>
</head>

<body>
<div id="wrapper">
<!-- Navigation -->
<?php include('../inc/doLeftbar.php'); ?>
<div id="page-wrapper">

<?php
	if( $obj->detail) {
		$activePer = $obj->isActiveInvoicePeriod($obj->periodId);
		$period = $obj->getInvoicePeriod($obj->periodId);
		ob_start();
		$totalAmount = $obj->build_list_invoice_view($activePer);
		$msg_error = $obj->msg_error;
		$out1 = ob_get_contents();
		ob_end_clean();
?>
	<table style="white-space: nowrap; width: 100%"><tbody>
		<tr>
			<td style="text-align: left; height: 35px;">
				<button class="btn btn-sm btn-default btn-ligh" style="border: none; height: 30px; font-size: 18px; line-height: 1;" onclick="location.href='?'"><i class="fa fa-angle-left"></i></button>
				<div style="font-size: 18px;font-weight: bold; display: inline-block;">Période de facturation du
					<?php echo substr($period->fromNoJour, -2) . "/" . substr($period->fromNoJour, 4, 2) . "/" . substr($period->fromNoJour, 0, 4) ; ?> au 
					<?php echo substr($period->toNoJour, -2) . "/" . substr($period->toNoJour, 4, 2) . "/" . substr($period->toNoJour, 0, 4); ?>
				</div>
			</td>
		</tr>
		<tr>
			<td style="vertical-align: top;">
				<div id="result" style=""><div><div><div><div class="alert alert-danger" ></div></div></div></div></div>
				<?php echo !$activePer ? "Liste des factures" : "Simulation la facturaton"; ?>
			</td>
		</tr>
		<tr>
			<td style="vertical-align: top; ">
				Montant total : <strong><?php echo  number_format($totalAmount, 2, ".", " "); ?> €</strong>
			</td>
		</tr>
	</tbody></table>
	<?php echo $out1; ?>

<?php } else { ?>
	<table style="white-space: nowrap; width: 100%">
		<tbody><tr>
			<td style="text-align: left; height: 35px;"><div style="font-size: 18px;font-weight: bold;">Période de facturation</div></td>
			<td style="width:100%; text-align:right;">
				<button type="button" class="btn btn-primary ls-modal" href="../inc/doEditInvoicePeriod.php?add=1"><span class="fa fa-edit" aria-hidden="true"></span> Add</button>
			</td>
		</tr></tbody>
	</table>
	<div id="result" style=""><div><div><div><div class="alert alert-danger" ></div></div></div></div></div>
	<table class="table custom">
		<thead><tr><th>N°</th><th>Intitulé</th><th>Date début</th><th>Date fin</th><th>Nb factures émises</th><th>Nb factures réglées</th><th>Montant total (€)</th><th>Montant réglé (€)</th><th>Statut</th><th colspan=2>Actions</th></tr></thead>
		<tbody>
		<?php $obj->build_period_view(); ?> 
		</tbody>
	</table>
<?php } ?>

	</div>
</div>

<!-- Modal -->
<div class="modal fade" id="confirmationModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title" id="exampleModalLongTitle">Confirmation de la clôture de la période de facturation</h5></div>
      <div class="modal-body">Après clôture, il ne sera plus possible de modifier les éléments de facturation de la période.<br/>Confirmez-vous cette clôture ?</div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
        <button type="button" class="btn btn-primary"  onclick="location.href='?in=<?php echo $obj->currentPeriodId; ?>&close=1'">Valider</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal HTML to add & edit period -->
<div style="width: 100%"-->
<div id="myModal" class="modal fade modal-dialog-centered">
<div class="modal-dialog" style="width: 80%;"><div class="modal-content"></div></div>
</div>
</div>

<script src="../dist/js/jquery.min.js"></script>
<script src="../dist/js/bootstrap.min.js"></script>
<script src="../dist/js/metisMenu.min.js"></script>
<script src="../dist/js/sb-admin-2.js"></script>
<script src="../dist/js/bootstrap-datetimepicker.js" charset="UTF-8"></script>
<script src="../dist/js/bootstrap-datetimepicker.fr.js" charset="UTF-8"></script>
<script src="../dist/js/app.js"></script>
<script src="../dist/js/einp.js"></script>

<?php if( $msg_error != "" ) { ?>
<script>
$(document).ready(function() {
	$('#result').find('.alert').html('<?php echo $msg_error; ?>');
	$('#result').show();
} );
</script>
<?php } ?>

</body>
</html>