<?php
/**************************************************
 * Code transaction : q/efam
 **************************************************/  
 
require_once('../services/AccessManager.php');
session_start ();
if( isset($_SESSION ['user']) ) $user = $_SESSION ['user'];
if( !isset($user) ) header('location:../q/logi');

// Access Family Item
require_once('../services/FamilyControler.php');
$obj=new FamilyControler();
$obj->initialize( $user->isAdmin() || $user->isSuper() );

// Access control
if( !$user->isAdmin() && !$user->isSuper() && !$user->isAnim() ) {
	$obj->setFamilyId($user->getFamilyId());
	$obj->add = false;
}
	
// Load data of this page
$obj->load();

// Parse request
$obj->parse_request();

// Load data of this page
if( $obj->msg_error == "") {
	$obj->load();
	$user->updateAccessToChild();
}
if( $obj->qfgrid->msg_error == "")
	$obj->qfgrid->load();

$editable = "readonly disabled";
if( $obj->edit || $obj->add ) $editable = "";


// Traitement de l'URL de retour
$backParamInputs = "";
if( $obj->back != "" ) {
	$backParamInputs .= '<input type="hidden" name="bk" value="' . $obj->back . '" >';
	$prms = explode("&", $obj->backParam);
	foreach($prms as $p) {
		$q = explode("=", $p);
		if( count($q) > 1 ) {
			$backParamInputs .= '<input type="hidden" name="' . $q[0] . '" value="' . $q[1] . '" >';
		}
	}
}
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
<link href="../dist/css/efam.css"	rel="stylesheet" type="text/css">
<title>Edition Fiche Famille</title>
</head>

<body>

<div id="wrapper">

<!-- Navigation -->
<?php include('../inc/doLeftbar.php'); ?>

<div id="page-wrapper">
<?php 
//
//!!!!!!!!!!!!!!!!!!!!!!! A VERIFIER !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
//
if( $obj->add) {


	?>
	<form method="post" >
<?php }?>
	<table style="white-space: nowrap; width: 100%;margin:0;">
		<tr>
			<td style="text-align: left; height: 35px; display: flex;">
			<?php if( $obj->back != "" ) { ?>
				<form method="post" action="<?php echo $obj->backUrl; ?>">
					<input type="hidden" name="fa" value="<?php echo $obj->familyId; ?>" />
					<?php echo $backParamInputs; ?>
					<button type="submit" class="btn btn-sm btn-default btn-ligh" style="border: none; height: 30px; font-size: 18px; line-height: 1;" ><i class="fa fa-angle-left"></i></button>
				</form>
			<?php } ?>
			<?php if( !$obj->add) { ?>
				<div style="display: inline-block; font-size: 18px;font-weight: bold; height: 35px; display: flex; align-items: center; justify-content: center;">
					<div>Famille <?php echo $obj->name; ?></div>
				</div>
			<?php } else { ?>
				<div style="display: inline-block; font-size: 18px;font-weight: bold; margin-bottom: 20px; height: 35px; display: flex; align-items: center; justify-content: center;">
					<div>Famille <input type="text" style="display: inline-block; width: auto;" class="form-control form-control-sm" name="faname" value="<?php echo $obj->name; ?>" placeholder="Nom Famille" editable required autofocus/></div>
				</div>
			</td>
			<td style="width:10%;">&nbsp;
			<?php }?>
			</td>
			<td style="width:50%; text-align:center;">
<?php
if( $obj->msg_error <> "") {
	$html = "";
	$html .= "<div class=\"alert alert-danger\" style=\"display: inline; height: 30px; padding: 10px; font-size:90%\"><strong style=\"padding: 10px;\">";
	$html .=$obj->msg_error;
	$html .= "</strong><button type=\"button\" class=\"close\" data-dismiss=\"alert\" style=\"display: contents; color: #6b6b6b;\">&times;</button></div>";
	echo $html;
}
if( $obj->msg_success <> "") {
	$html = "";
	$html .= "<div class=\"alert alert-success\" style=\"display: inline; height: 30px; padding: 10px; font-size:90%\"><strong style=\"padding: 10px;\">";
	$html .=$obj->msg_success;
	$html .= "</strong><button type=\"button\" class=\"close\" data-dismiss=\"alert\" style=\"display: contents; color: #6b6b6b;\">&times;</button></div>";
	echo $html;
}
?>
			</td>
			<td style="width:20%; text-align:right;">
<?php if( !$obj->add && !$obj->edit ) { ?>
				<form method="post" id="editForm">
					<input type="hidden" name="edit"	value="1" >
					<input type="hidden" name="fa" value="<?php echo $obj->familyId; ?>" />
					<input type="hidden" name="general"	value="<?php if( $obj->isGeneral ) echo "1"; else echo "0"; ?>" >
					<input type="hidden" name="finance"	value="<?php if( $obj->isFinance ) echo "1"; else echo "0"; ?>" >
					<input type="hidden" name="access"	value="<?php if( $obj->isAccess ) echo "1"; else echo "0"; ?>" >
					<input type="hidden" name="qfgrid"	value="<?php if( $obj->isQFGrid )  echo "1"; else echo "0"; ?>" >
					<input type="hidden" name="bk"		value="<?php echo $obj->back; ?>" />
					<input type="hidden" name="bkparam"	value="<?php echo $obj->backParam; ?>" />
					<button type="submit" name="btnEdit" class="btn btn-sm btn-primary" ><span class="glyphicon glyphicon-edit" aria-hidden="true"></span> Edit</button>
				</form>
<?php } ?>
			</td>
		</tr>
	</table>

<ul class="nav nav-tabs">
	<li <?php if( $obj->isGeneral  ) echo "class=\"active\""; ?>><a data-toggle="tab" href="#main">Général</a></li>
<?php if( ( !$obj->add ) &&
          ( $user->isAdmin() || $user->isSuper() ) ) { ?>
	<?php /** ?>
	<li <?php if( $obj->isQFGrid  )  echo "class=\"active\""; ?>><a data-toggle="tab" href="#admin">Grille QF</a></li>
	<?php **/ ?>
	<li <?php if( $obj->isAccess  )  echo "class=\"active\""; ?>><a data-toggle="tab" href="#access">Accès Portail</a></li>
<?php	if( $user->isAdmin() || $user->isSuper() ) { ?>
	<li <?php if( $obj->isFinance  ) echo "class=\"active\""; ?>><a data-toggle="tab" href="#finance">Finance</a></li>
<?php	} ?>
<?php } ?>
</ul>
<div class="tab-content">
	&nbsp;<br/>
	
	
	<!- ===================== PANEL GENERAL ========================!>
	<div id="main" class="tab-pane fade <?php if( $obj->isGeneral ) echo "in active"; ?>">
<?php if( !$obj->add ) { ?>
		<form class="form-tab" method="post" >
			<input type="hidden" name="faname" value="<?php echo $obj->name; ?>" >
<?php } ?>
			<?php include '../inc/doEditFamilyForm.php'; ?>
		
			<?php if ($obj->edit || $obj->add) { ?>
			<table style="width: 100%;table-layout: fixed;border: 1px;"><tr>
				<td><div style="width:100%; text-align:right;font-style: italic;">
					<?php if ($obj->edit) { ?>
					<input type="submit" name="btnSubmitUpdate" class="hidden" />
					<?php } elseif ($obj->add) { ?>
					<input type="submit" name="btnSubmitCreate" class="hidden" />
					<?php }?>
					<button type="submit" name="cancel" class="btn btn-light" formnovalidate>Annuler</button>
					<input type="hidden" name="general" value="1" />
					<!--?php echo $backParamInputs; ?-->
					<input type="hidden" name="bk"		value="<?php echo $obj->back; ?>" />
					<input type="hidden" name="bkparam"	value="<?php echo $obj->backParam; ?>" />
					<?php if ($obj->edit) { ?>
					<input type="hidden" name="fa" value="<?php echo $obj->familyId; ?>" />
					<input type="hidden" name="edit"	value="1" >
					<input type="submit" value="Enregistrer" name="btnSubmitUpdate" class="btn btn-success" />
					<?php } elseif ($obj->add) { ?>
					<input type="hidden" name="add" value="1" />
					<button type="submit" name="btnSubmitCreate" class="btn btn-success" ><span class="glyphicon glyphicon-plus" aria-hidden="true"></span> Créer</button>
					<?php }?>
				</div></td>
			</tr></table>
			<?php } ?>
		</form>
	</div>
	
	<!- ===================== PANEL GRILLE QF ========================!>
<?php if( ( !$obj->add ) &&
          ( $user->isAdmin() || $user->isSuper() )
		) { ?>
	<?php /** ?>
	<div id="admin" class="tab-pane fade <?php if( $obj->isQFGrid ) echo "in active"; ?>">
		<form class="form-tab" method="post" enctype="multipart/form-data" novalidate>
			<?php
				// Champs éditable ou non en fonction du statut de la grille de scolarité
				$obj->qfgrid->edit = false;
				if( $obj->edit && ($obj->qfgrid->qfStatus == 0 || $obj->qfgrid->qfStatus == 1) )
					$obj->qfgrid->edit = true;

				$initialEditable = $editable;
				$editable = "readonly disabled";
				if( $obj->qfgrid->edit )
					$editable = "";
				
				include '../inc/doEditGrilleScolariteForm.php';
				$editable = $initialEditable;
			?>
			<?php if( $obj->qfgrid->qfStatus == 0 && $obj->edit ) { ?>
			<div style="padding: 5px; margin: 10px;">
				<input type="checkbox" name="qfAcceptation"> En cochant cette case, j'atteste que les déclarations dans la présente demande sont exactes et complètes.
			</div>
			<?php } ?>
			<input type="submit" name="btnSubmitQFGrid" class="hidden"/>
			<input type="hidden" name="btnSubmitQFGrid"/>
			<input type="hidden" name="qfgrid" value="1"/>
			<?php if ($obj->edit) { ?>
			<table style="width: 100%;table-layout: fixed;border: 1px;"><tr>
				<td><div style="width:100%; text-align:right;font-style: italic;">
					<button type="submit" name="cancel" class="btn btn-light" formnovalidate>Annuler</button>
					<input type="hidden" name="fa" value="<?php echo $obj->familyId; ?>" />
					<input type="hidden" name="qfgrid" value="1" />
					<input type="hidden" name="edit"	value="1" >
					<!--?php echo $backParamInputs; ?-->
					<input type="hidden" name="bk"		value="<?php echo $obj->back; ?>" />
					<input type="hidden" name="bkparam"	value="<?php echo $obj->backParam; ?>" />
					<?php if( $obj->qfgrid->qfStatus == 0 ) { ?>
					<input type="hidden" name="qfFamValid" value="0"/>
					<input type="submit" value="Enregistrer" name="btnSubmitQFGrid" class="btn btn-success" /></div>
					<?php } elseif( $obj->qfgrid->qfStatus == 1 ) { ?>
					<input type="hidden" name="qfValidation" value="0"/>
					<input type="submit" value="Valider" name="btnSubmitQFGrid" class="btn btn-success" /></div>
					<?php } ?>
				</td></tr>
			</table>
			<?php } ?>
		</form>
		<form method="post" id="financialTabChangeYear">
			<input type="hidden" name="y" />
			<input type="hidden" name="fa"		value="<?php echo $obj->familyId; ?>" />
			<input type="hidden" name="qfgrid"	value="1" />
			<input type="hidden" name="edit"	value="<?php echo ($obj->edit) ? '1' : '0'; ?>" />
			<input type="hidden" name="bk"		value="<?php echo $obj->back; ?>" />
			<input type="hidden" name="bkparam"	value="<?php echo $obj->backParam; ?>" />
		</form>
	</div>
	<?php **/ ?>
	
	<!- ===================== PANEL ACCES PORTAIL ========================!>
	<div id="access" class="tab-pane fade <?php if( $obj->isAccess ) echo "in active"; ?>">
		<form class="form-tab" method="post" >
			<input type="hidden" name="fa" value="<?php echo $obj->familyId; ?>" />
			<input type="hidden" name="access" value="1" />
			<input type="hidden" name="bk" value="<?php echo $obj->back; ?>" />
			<?php include '../inc/doEditPasswordForm.php'; ?>
			<?php if ($obj->edit || $obj->add) { ?>
			<table style="width: 100%;table-layout: fixed;border: 1px;"><tr>
				<td><div style="width:100%; text-align:right;font-style: italic;">
				<input type="hidden" name="edit"	value="1" >
				<!--?php echo $backParamInputs; ?-->
				<input type="hidden" name="bk"		value="<?php echo $obj->back; ?>" />
				<input type="hidden" name="bkparam"	value="<?php echo $obj->backParam; ?>" />
				<input type="submit" name="btnSubmitAccess" class="hidden" />
				<button type="submit" name="cancel" class="btn btn-light" formnovalidate>Annuler</button>
				<input type="submit" value="Enregistrer" name="btnSubmitAccess" class="btn btn-success" /></div>
			</td></tr></table>
			<?php } ?>
		</form>
	</div>

	<!- ===================== PANEL FINANCE ========================!>
	<?php

	// Access control
	if( $user->isAdmin() || $user->isSuper() ) {
		// Initialize InvoiceControler
		require_once('../services/InvoiceControler.php');
		$invObj=new InvoiceControler();
		$invObj->initialize();
		$invObj->loadInvoiceList($obj->familyId);
	?>
	<div id="finance" class="tab-pane fade <?php if( $obj->isFinance ) echo "in active"; ?>">
		<form method="post" >
		<div class="container-fluid border rounded-top bg-light" style="padding-top:10px;margin-bottom:10px;">
			<table class="form-tab"><tbody>
				<tr>
					<td class="col-md-1"><label>Accès au Portail</label></td>
					<td class="col-md-3"><input type="checkbox" name="active" <?php if( $obj->active ) echo "checked "; if( !$obj->edit && !$obj->add )	echo "disabled "; ?>/></td>
				</tr>
				<tr>
					<td class="col-md-1"><label>QF</label></td>
					<td class="col-md-4"><input type="text" class="form-control form-control-sm" name="qf" value="<?php echo $obj->qf; ?>" placeholder="QF" <?php echo $editable; ?> required autofocus/></td>
				</tr>
				<tr>
					<td class="col-md-1"><label>Solde Famille</label></td>
					<td class="col-md-3"><?php
						$html = "<strong>" . number_format($obj->balance, 2, '.', ' ') . "€</strong>" .
								"&nbsp;&nbsp;<small><i>(";
						if( $obj->balance < 0 )	$html .= "débit";
						else					$html .= "crédit";
						$html .= ")</i></small>";
						echo $html;
					?></td>
				</tr>
			</tbody></table>
			<?php $invObj->build_ListInvoicesTable(false); ?>
		</div>
		<?php if ($obj->edit || $obj->add) { ?>
		<table style="width: 100%;table-layout: fixed;border: 1px;"><tr>
			<td><div style="width:100%; text-align:right;font-style: italic;">
			<input type="submit" name="btnSubmitFinance" class="hidden" />
			<button type="submit" name="cancel" class="btn btn-light" formnovalidate>Annuler</button>
			<input type="hidden" name="fa" value="<?php echo $obj->familyId; ?>" />
			<input type="hidden" name="finance" value="1" />
			<!--?php echo $backParamInputs; ?-->
			<input type="hidden" name="bk"		value="<?php echo $obj->back; ?>" />
			<input type="hidden" name="bkparam"	value="<?php echo $obj->backParam; ?>" />
			<input type="submit" value="Enregistrer" name="btnSubmitFinance" class="btn btn-success" /></div>
		</td></tr></table>
		<?php } ?>
		</form>
	</div>
	<?php } ?>
<?php } // End if( !$obj->add )  ?>
&nbsp;
</div>
</div>
</div>

<!-- Modal HTML to add & edit period -->
<div style="width: 100%"-->
<div id="myModal" class="modal fade modal-dialog-centered">
<div class="modal-dialog" style="width: 80%;"><div class="modal-content"></div></div>
</div>
</div>

<script src="../dist/js/jquery.min.js" ></script>
<script src="../dist/js/bootstrap.min.js" ></script>
<script src="../dist/js/metisMenu.min.js" ></script>
<script src="../dist/js/sb-admin-2.js" ></script>
<script src="../dist/js/bootstrap-datetimepicker.js" charset="UTF-8"></script>
<script src="../dist/js/bootstrap-datetimepicker.fr.js" charset="UTF-8"></script>
<script src="../dist/js/app.js" ></script>
<script src="../dist/js/efam.js" ></script>

</body>
</html>