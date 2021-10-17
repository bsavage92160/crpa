<?php
	if( !isset($_GET['wd']) || !is_numeric($_GET['wd']) )
		return;
	$wd = intval($_GET['wd']);
?>
<form method="post">
<div id="confirmForm">
	<div class="modal-header">
		<h5 class="modal-title">Confirmation du chargement des réservations</h5>
	</div>
	<div class="modal-body">
		<strong>Les données de pointage sur cette semaine seront écrasées par les réservations.</strong>
		<br>
		Jours chargées : &nbsp;&nbsp;
		<label><input type="checkbox" name="d[0]" checked>&nbsp;Lundi</label>&nbsp;&nbsp;	
		<label><input type="checkbox" name="d[1]" checked>&nbsp;Mardi</label>&nbsp;&nbsp;
		<label><input type="checkbox" name="d[2]" checked>&nbsp;Mercredi</label>&nbsp;&nbsp;
		<label><input type="checkbox" name="d[3]" checked>&nbsp;Jeudi</label>&nbsp;&nbsp;
		<label><input type="checkbox" name="d[4]" checked>&nbsp;Vendredi</label>&nbsp;&nbsp;
		<br><br>
		Confirmez-vous cette mise à jour ?
	</div>
	<div class="modal-footer">
		<button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
		<input type="hidden" name="loadres" value="1" />
		<input type="hidden" name="wd" value="<?php echo $wd; ?>" />
		<button type="submit" class="btn btn-primary">Valider</button>
	</div>
</div>
</form>