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

$editable = "";
$obj->edit = true;
?>

<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
    <h4 class="modal-title">Mise à jour Profil</h4>
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

	<form class="form-tab" method="post" action="../scripts/server_processing_family_form.php">
		<?php include('doEditFamilyForm.php'); ?>
		<input type="submit" name="btnSubmitEditFam" class="hidden"/>
		<input type="hidden" name="btnSubmitUpdate"/>
		<input type="hidden" name="edit" value="1"/>
	</form>

</div>
<div class="modal-footer">
    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
    <button type="button" class="btn btn-primary" id="btnSaveEditFam">Save changes</button>
</div>

<script>
$('.form_date').datetimepicker({
	language:  'fr',
	weekStart: 1,
	todayBtn:  1,
	autoclose: 1,
	todayHighlight: 1,
	startView: 2,
	minView: 2,
	forceParse: 0
});
</script>