<?php
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
$obj->qfgrid->load();

// Parse request
$obj->qfgrid->parse_request();

// Reload after update data
if( $obj->qfgrid->toReload ) {
	$s = $obj->qfgrid->submit;
	$obj->qfgrid->submit = false;
	$obj->qfgrid->load();
	$obj->qfgrid->submit = $s;
}

// Champs éditable ou non en fonction du statut de la grille de scolarité
$obj->qfgrid->edit = false;
if( $obj->qfgrid->qfStatus == 0 )
	$obj->qfgrid->edit = true;

$editable = "readonly disabled";
if( $obj->qfgrid->edit )
	$editable = "";
?>

<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
    <h4 class="modal-title">Grille de scolarité</h4>

	<div id="resultQFGrid"><div><div><div>
		<?php
			if( $obj->qfgrid->submit && $obj->qfgrid->msg_error != null ) {
				if( isset($obj->qfgrid->msg_error['qfAcceptation']) ) {
		?>
		<div class="alert alert-danger"><?php echo $obj->qfgrid->msg_error['qfAcceptation']; ?></div>
		<?php 	} else { ?>
		<div class="alert alert-danger">Plusieurs erreurs de saisie. Merci de vous reporter aux commentaires ci-dessous.</div>
		<?php 	} ?>
		<?php } else if( $obj->qfgrid->submit ) { ?>
		<div class="alert alert-success"><?php echo $obj->msg_success; ?></div>
		<?php } ?>
	</div></div></div></div>

</div>
<div class="modal-body">

	<form class="form-tab" method="post" action="../inc/doEditGrilleScolarite.php" enctype="multipart/form-data" novalidate>
		<?php include('doEditGrilleScolariteForm.php'); ?>
		<?php if( $obj->qfgrid->qfStatus == 0 ) { ?>
		<div style="padding: 5px; margin: 10px;">
			<input type="checkbox" name="qfAcceptation"> En cochant cette case, j'atteste que les déclarations dans la présente demande sont exactes et complètes.
		</div>
		<?php } ?>
		<input type="submit" name="btnSubmitQFGrid" class="hidden"/>
		<input type="hidden" name="btnSubmitQFGrid"/>
		<input type="hidden" name="qfgrid" value="1"/>
		<input type="hidden" name="qfFamValid" value="0"/>
		<?php if( $obj->qfgrid->qfStatus == 0 ) { ?>
		<input type="hidden" name="edit" value="1"/>
		<?php } ?>
	</form>

</div>
<div class="modal-footer">
    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
	<?php if( $obj->qfgrid->qfStatus == 0 ) { ?>
    <button type="button" class="btn btn-info" id="btnSaveQFGrid">Save changes</button>&nbsp;&nbsp;
	<button type="button" class="btn btn-primary" id="btnValidQFGrid">Validation et Envoi du formulaire</button>
	<?php } ?>
</div>
<script>
$('select').each(function(){
	$(this).find('option[value="'+$(this).attr("value")+'"]').prop('selected', true);
});
$('#myModal').find('.modal-content').find('input[autofocus]').select();
<?php if( $obj->qfgrid->submit ) { ?>
$('#resultQFGrid').show();
<?php } ?>
<?php
if( $obj->qfgrid->msg_error != null && is_array($obj->qfgrid->msg_error) ) {
	foreach ($obj->qfgrid->msg_error as $key => $val) break;
?>
$('#myModal').find('.modal-content').find('input[name=<?php echo $key; ?>]').focus();
<?php } ?>
</script>