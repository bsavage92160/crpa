<?php
require_once('../services/CalendarControler.php');

// Check modal windows or submit treatment
// Case #1 : Display of Modal windows
if( !isset($_POST['cust_wd']) ) {
	
	// Parse request
	$wd = 0;
	if( isset($_GET['wd']) && is_numeric($_GET['wd']) )		$wd	= intval($_GET['wd']);
	$d = DateTime::createFromFormat('Ymd', $wd);
	if($d == false)
		return;
	$wDay= intval($d->getTimestamp());

	$jourSem 	= intval(date('N', $wDay));
	$jj		 	= date('d', $wDay);
	$mm		 	= date('m', $wDay);
	$days		= array("Lundi", "Mardi", "Mercredi", "Jeudi", "Vendredi", "Samedi", "Dimanche");

	$cus = "1000100";
	if( $jourSem == 3 ) $cus = "1111000";
	if( isset($_GET['cus']) && is_numeric($_GET['cus']) )		$cus	= $_GET['cus'];
	$cus .= "00000000";
	$alb = "";
	if( isset($_GET['alb']) )									$alb	= $_GET['alb'];

?>
<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
    <h4 class="modal-title">Personnalisation d'une journée</h4>
</div>
<div class="modal-body">

	<form id="moreDayModal" method="post" action="../inc/doEditCalendarMore.php">
		<input type="hidden" name="cust_wd" value="<?php echo $wd ?>">
		<div class="divC">
			<div><h4 class="modal-title" style="padding-bottom: 10px;"><strong><?php echo $days[$jourSem-1] . $jj . "/" . $mm; ?></strong></div>
			<div class="divR">
				<span>Schéma journée : </span>
				<label><input type="checkbox" name="cust_mat" <?php echo (substr($cus, 0, 1) == '1') ? 'checked' : '' ?>><span>Matin</span></label>
				<label><input type="checkbox" name="merc_lib" <?php echo (($jourSem == 3) && (substr($cus, 6, 1) == '1')) ? 'checked' : '' ?> <?php echo ($jourSem != 3) ? 'disabled' : '' ?>><span <?php echo ($jourSem != 3) ? 'class="disabled"' : '' ?>>Mercredi libéré</span></label>
				<label><input type="checkbox" name="cust_mid" <?php echo (($jourSem == 3) && (substr($cus, 1, 1) == '1')) ? 'checked' : '' ?> <?php echo ($jourSem != 3) ? 'disabled' : '' ?>><span <?php echo ($jourSem != 3) ? 'class="disabled"' : '' ?>>Midi</span></label>
				<label><input type="checkbox" name="cust_rep" <?php echo (($jourSem == 3) && (substr($cus, 2, 1) == '1')) ? 'checked' : '' ?> <?php echo ($jourSem != 3) ? 'disabled' : '' ?>><span <?php echo ($jourSem != 3) ? 'class="disabled"' : '' ?>>Repas</span></label>
				<label><input type="checkbox" name="cust_apm" <?php echo (($jourSem == 3) && (substr($cus, 3, 1) == '1')) ? 'checked' : '' ?> <?php echo ($jourSem != 3) ? 'disabled' : '' ?>><span <?php echo ($jourSem != 3) ? 'class="disabled"' : '' ?>>Après-midi</span></label>
				<label><input type="checkbox" name="cust_soi" <?php echo (($jourSem != 3) && (substr($cus, 4, 1) == '1')) ? 'checked' : '' ?> <?php echo ($jourSem == 3) ? 'disabled' : '' ?>><span <?php echo ($jourSem == 3) ? 'class="disabled"' : '' ?>>Soir</span></label>
			</div>
			<div class="divR">
				<span>Activité nécessitant une réservation : </span>
				<span><input type="checkbox" name="cust_act"  <?php echo (($jourSem == 3) && (substr($cus, 5, 1) == '1')) ? 'checked' : '' ?> <?php echo ($jourSem != 3) ? 'disabled' : '' ?>></span>
				<label><input type="text" name="act_lbl" value="<?php echo $alb ?>" class="form-control form-control-sm" maxlength="20" <?php echo (($jourSem != 3) || (substr($cus, 5, 1) != '1')) ? 'disabled' : '' ?>></label>
			</div>
		</div>
	</form>

</div>
<div class="modal-footer">
    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
    <button type="button" class="btn btn-primary" id="btnSave">Save changes</button>
</div>

<?php

// Case #2 : Submit treatment
} else {
	
	// Attributes
	$wd			= 0;
	$cust_mat	= false;
	$cust_mid	= false;
	$cust_rep	= false;
	$cust_apm	= false;
	$cust_soi	= false;
	$cust_act	= false;
	$merc_lib	= false;
	$act_lbl	= "";
	
	// Parse request POST
	if( isset($_POST['cust_wd']) && is_numeric($_POST['cust_wd']) )		$wd			= intval($_POST['cust_wd']);
	if( isset($_POST['cust_mat']) )										$cust_mat	= true;
	if( isset($_POST['cust_mid']) )										$cust_mid	= true;
	if( isset($_POST['cust_rep']) )										$cust_rep	= true;
	if( isset($_POST['cust_apm']) )										$cust_apm	= true;
	if( isset($_POST['cust_soi']) )										$cust_soi	= true;
	if( isset($_POST['cust_act']) )										$cust_act	= true;
	if( isset($_POST['act_lbl']) )										$act_lbl	= trim($_POST['act_lbl']);
	if( isset($_POST['merc_lib']) )										$merc_lib	= true;
	
	// Treat working date
	$d = DateTime::createFromFormat('Ymd', $wd);
	if($d == false)
		return;
	$wDay= intval($d->getTimestamp());
	$jourSem 	= intval(date('N', $wDay));
	
	// Treat custom codification
	$custCode = "";
	if( $cust_mat )		$custCode .= "1";
	else				$custCode .= "0";
	if( $cust_mid )		$custCode .= "1";
	else				$custCode .= "0";
	if( $cust_rep )		$custCode .= "1";
	else				$custCode .= "0";
	if( $cust_apm )		$custCode .= "1";
	else				$custCode .= "0";
	if( $cust_soi )		$custCode .= "1";
	else				$custCode .= "0";
	if( $cust_act )		$custCode .= "1";
	else				$custCode .= "0";
	if( $merc_lib )		$custCode .= "1";
	else				$custCode .= "0";
	
	echo CalendarControler::buildCalendarCaseDetails($wd, $jourSem, $custCode, $act_lbl);

} // End -- If (Check modal windows or submit treatment)
?>