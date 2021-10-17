<?php
/**************************************************
 * Code transaction : q/epay
 **************************************************/  
 
require_once('../services/AccessManager.php');
session_start ();
if( isset($_SESSION ['user']) ) $user = $_SESSION ['user'];
if( !isset($user) ) header('location:../q/logi');

// Access control
if( !$user->isAdmin() && !$user->isSuper() ) {
	echo "Acess denied on this page - Please contact your administrator";
	return;
}

require_once('../services/PaymentControler.php');
$obj=new PaymentControler();
$obj->initialize($user->getUserId());
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
<link href="../dist/css/epay.css"	rel="stylesheet" type="text/css">
<title>Saisie d'un réglement</title>
</head>

<body>

<div id="wrapper">

<!-- Navigation -->
<?php include('../inc/doLeftbar.php'); ?>

<div id="page-wrapper">
	<table style="white-space: nowrap; width: 100%;margin:0;">
		<tr><td style="text-align: left; height: 35px;"><div style="font-size: 18px;font-weight: bold;">Saisie d'un réglement</div></td></tr>
	</table>
	<form id="paymentForm" method="post" >
	<input type="hidden" id="stateInput" name="state" value="<?php echo $obj->state; ?>" />
	<div class="tab-content">
		<div class="container-fluid border rounded-top bg-light" style="padding-top:10px;margin-bottom:10px;">
			<table class="form-tab"><tbody>
				<tr>
					<td>
						<label for="invoiceNo">N° Facture</label>
						<input type="text" class="form-control form-control-sm" id="invoiceNo" name="invoiceNo" value="<?php echo $obj->invoiceNoStr; ?>" placeholder="N° Facture" required <?php if( ($obj->msg_error2 == "") && ($obj->msg_error3 == "") ) echo "autofocus"; ?> <?php if( $obj->state > 0) echo "disabled"; ?>/>
						<small class="form-text text-danger"><?php if( $obj->msg_error1 <> "") echo $obj->msg_error1; ?></small>
					</td>
					<td>
						<label for="amount">Montant réglé (€)</label>
						<input type="text" class="form-control form-control-sm" id="amount" name="amount" value="<?php echo $obj->amountStr; ?>" placeholder="Montant réglé" <?php if( ($obj->msg_error1 == "") && ($obj->msg_error2 <> "") ) echo "autofocus"; ?> <?php if( $obj->state > 0) echo "disabled"; ?>/>
						<small class="form-text text-danger"><?php if( $obj->msg_error2 <> "") echo $obj->msg_error2; ?></small>
					</td>
				</tr>
				<tr>
					<td colspan=2>
						<label for="comment">Commentaires</label>
						<input type="text" class="form-control form-control-sm" id="comment" name="comment" value="<?php echo $obj->comment; ?>" placeholder="Chèque N°XXX, Ticket CESU, Espèces..." <?php if( $obj->msg_error3 <> "" ) echo "autofocus"; ?> <?php if( $obj->state > 0) echo "disabled"; ?>/>
						<small class="form-text text-danger"><?php if( $obj->msg_error3 <> "") echo $obj->msg_error3; ?></small>
					</td>
				</tr>
			</tbody></table>
		</div>
		<table style="width: 100%;table-layout: fixed;border: 1px;"><tr>
			<td><small class="text-success"><?php if( $obj->msg_success <> "") echo $obj->msg_success; ?></small></td>
			<td><div style="width:100%; text-align:right;font-style: italic;">
<?php if( $obj->state == 1 ) { ?>
			<input type="hidden" name="invoiceNo" value="<?php echo $obj->invoiceNoStr; ?>"/>
			<input type="hidden" name="amount" value="<?php echo $obj->amountStr; ?>"/>
			<input type="hidden" name="comment" value="<?php echo $obj->comment; ?>"/>
			<small class="form-text text-danger">Confirmer la saisie ? >>>&nbsp;&nbsp;</small>
			<button type="button" class="btn btn-light" onclick="cancel();">Annuler</button>&nbsp;
			<input type="submit" value="Enregistrer" name="submit" class="btn btn-success" autofocus/></div>
<?php } else { ?>
			<input type="submit" value="Enregistrer" name="submit" class="btn btn-success" /></div>
<?php } ?>
			</td></tr>
		</table>
		&nbsp;<br/>
		<?php $obj->generate_html_invoice(); ?>
	</div>
</form>
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
<script src="../dist/js/app.js"></script>
<script src="../dist/js/epay.js" ></script>
</body>
</html>