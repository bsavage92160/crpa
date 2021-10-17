<?php
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
?>

<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>

<?php if ($obj->edit ){ ?>
    <h4 class="modal-title">Mise à jour d'une période de facturation</h4>
<?php } elseif ($obj->add ){ ?>
    <h4 class="modal-title">Ajout d'une nouvelle période de facturation</h4>
<?php } ?>

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

<?php
if ($obj->edit ){
	$period = $obj->getInvoicePeriod($obj->periodId);
?>
	<form id="myForm" class="form-tab" method="post">
		<input type="hidden" name="in" value="<?php echo $obj->periodId; ?>"/>
		<input type="hidden" name="edit" value="1"/>
		<table class="table-condensed"><tbody>
			<tr>
				<td>Intitulé :</td>
				<td><input type="text" name="title" class="form-control myinput myinput-m" value="<?php echo $period->title; ?>" autofocus></td>
			</tr>
			<tr>
				<td>Date de début :</td>
				<td>
					<div class="input-group date form_date col-md-5" data-date="" data-date-format="dd/mm/yyyy" data-link-field="fromNoJour" data-link-format="yyyymmdd">
						<input class="form-control myinput myinput-xs" style="width: 160px; text-align: left;" type="text" value="<?php echo substr($period->fromNoJour, -2) . '/' . substr($period->fromNoJour, 4, 2) . '/' . substr($period->fromNoJour, 0, 4); ?>" readonly>
						<span class="input-group-addon" style="padding: 0px 12px 0px 12px;"><span class="glyphicon glyphicon-calendar"></span></span>
					</div>
					<input type="hidden" id="fromNoJour" name="fromNoJour" value="<?php echo $period->fromNoJour; ?>" />
				</td>
			</tr>
			<tr>
				<td>Date de fin :</td>
				<td>
					<div class="input-group date form_date col-md-5" data-date="" data-date-format="dd/mm/yyyy" data-link-field="toNoJour" data-link-format="yyyymmdd">
						<input class="form-control myinput myinput-xs" style="width: 160px; text-align: left;" type="text" value="<?php echo substr($period->toNoJour, -2) . '/' . substr($period->toNoJour, 4, 2) . '/' . substr($period->toNoJour, 0, 4); ?>" readonly>
						<span class="input-group-addon" style="padding: 0px 12px 0px 12px;"><span class="glyphicon glyphicon-calendar"></span></span>
					</div>
					<input type="hidden" id="toNoJour" name="toNoJour" value="<?php echo $period->toNoJour; ?>" />
				</td>
			</tr>
		</tbody></table>
		<input type="submit" name="submit" class="hidden"/>
	</form>
	
<?php } elseif ($obj->add ){ ?>

	<form id="myForm" class="form-tab" method="post">
		<input type="hidden" name="in" value="-1"/>
		<input type="hidden" name="add" value="1"/>
		<table class="table-condensed"><tbody>
			<tr>
				<td>Intitulé :</td>
				<td><input type="text" name="title" class="form-control myinput myinput-m" autofocus></td>
			</tr>
			<tr>
				<td>Date de début :</td>
				<td>
					<div class="input-group date form_date col-md-5" data-date="" data-date-format="dd/mm/yyyy" data-link-field="fromNoJour" data-link-format="yyyymmdd">
						<input class="form-control myinput myinput-xs" style="width: 160px; text-align: left;" type="text" value="" readonly>
						<span class="input-group-addon" style="padding: 0px 12px 0px 12px;"><span class="glyphicon glyphicon-calendar"></span></span>
					</div>
					<input type="hidden" id="fromNoJour" name="fromNoJour" value="" />
				</td>
			</tr>
			<tr>
				<td>Date de fin :</td>
				<td>
					<div class="input-group date form_date col-md-5" data-date="" data-date-format="dd/mm/yyyy" data-link-field="toNoJour" data-link-format="yyyymmdd">
						<input class="form-control myinput myinput-xs" style="width: 160px; text-align: left;" type="text" value="" readonly>
						<span class="input-group-addon" style="padding: 0px 12px 0px 12px;"><span class="glyphicon glyphicon-calendar"></span></span>
					</div>
					<input type="hidden" id="toNoJour" name="toNoJour" value="" />
				</td>
			</tr>
		</tbody></table>
		<input type="submit" name="submit" class="hidden"/>
	</form>
	
<?php } ?>

</div>
<div class="modal-footer">
    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
    <button type="button" class="btn btn-primary" id="saveBtn">Save changes</button>
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