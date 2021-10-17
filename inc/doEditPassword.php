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
$obj->load();

$obj->edit = true;
$editable = "";
?>

<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
    <h4 class="modal-title">Mise à jour du mot des identifiants d'accès au Portail</h4>
	<div class="result" style="display: none;">
		<div style="z-index: 99; position: absolute; left: 0;top: 15px;width: 100%;">
			<div style="display: block; text-align: center; font-size: 1rem;">
				<div style="position: relative; display: inline-block;">
					<div class="alert" style="padding: 5px; ">
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<div class="modal-body">

	<form class="form-tab" method="post" action="../scripts/server_processing_password_form.php">
		<?php include('doEditPasswordForm.php'); ?>

		<input type="submit" name="btnSubmitAccess" class="hidden"/>
		<input type="hidden" name="btnSubmitAccess"/>
		<input type="hidden" name="edit" value="1"/>
	</form>

</div>
<div class="modal-footer">
    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
    <button type="button" class="btn btn-primary" id="saveBtn">Save changes</button>
</div>