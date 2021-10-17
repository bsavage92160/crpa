<?php

function fin_write_text_input($name, $placeholder, $autofocus = false, $numeric = true) {
	global $obj, $editable;
	$html = "";
	$html .= "<input type=\"text\" name=\"" . $name . "\" value=\"" ;
	if( $numeric && is_numeric($obj->qfgrid->$name) )	$html .= number_format($obj->qfgrid->$name, 0, ",", " ");
	else 										$html .= $obj->qfgrid->$name;
	$html .= "\" placeholder=\"" . $placeholder . "\" " . $editable . " required ";
	if($autofocus) $html .= " autofocus";
	$html .= "/>";
	if( isset($obj->qfgrid->msg_error[$name]) ) {
		if( $obj->qfgrid->msg_error[$name] <> "" )
			$html .= "<small class=\"error\">" . $obj->qfgrid->msg_error[$name] . "</small>";
	}
	echo $html;
}

function fin_write_file_input($name) {
	global $obj, $editable;
	$html = "<td class=\"col-md-3\">";
	$html .= "<input type=\"hidden\" name=\"" . $name . "_\" value=\"" . $obj->qfgrid->$name . "\" />";
	$html .= "<input type=\"file\" name=\"" . $name . "\" class=\"form-control-file\" ";
	if( !$obj->edit )	$html .= " disabled";
	$html .= "/>";
	if( isset($obj->qfgrid->msg_error[$name]) ) {
		if( $obj->qfgrid->msg_error[$name] <> "" )
			$html .= "<small class=\"error\">" . $obj->qfgrid->msg_error[$name] . "</small>";
	}
	$html .= "</td>";
	$html .= "<td class=\"col-md-3\">";
	if( $obj->qfgrid->$name <> "" ) {
		$html .= "<a href=\"../upload/" . $obj->qfgrid->$name . "\"><i class=\"fa ";
		if( strstr($obj->qfgrid->$name, 'pdf') ) 	$html .= "fa-file-pdf-o";
		else								$html .= "fa-file-image-o";
		$html .= "\" aria-hidden=\"true\"></i>&nbsp;" . $obj->qfgrid->$name . "</a>";
	}
	$html .= "</td>";
	echo $html;
}
?>
<div style="padding: 0px 5px 0px 5px; margin: 0px 10px 0px 10px;">
	<table id="financialTabYearSelector" style="width:100%"><tbody>
		<tr>
			<td style="white-space: nowrap; width: 10px;"><label>ANNEE :</label></td>
			<td style="min-width: 100px">
				<select name="y" value="<?php echo $obj->qfgrid->qfYear; ?>">
<?php
foreach ($obj->qfgrid->qfStatusLst as $year => $status){
	$statusW = "";
	if( $status == 0 )
		$statusW = "Ouvert à la saisie";
	elseif( $status == 1 )
		$statusW = "Attente de validation";
	else
		$statusW = "Validé";
?>
					<option value="<?php echo $year ?>"><?php echo $year . ' - ' . $statusW?></option>
<?php } ?>
				</select>
			</td>
		</tr>
	</table>
</div>
<div style="padding: 5px; margin: 10px; background-color: #f0f8ff; border-style: solid; border-color: #e3e3e3; border-width: thin;">
	<table id="financialTab"><tbody>
		<tr>
			<td>
				<table style="width:100%"><tbody>
					<tr>
						<td class="col-md-4" style="white-space: nowrap;"><label>1- Nombre d'enfants (NE) :</label><br/><small>(y compris ceux nés jusqu'au 15 septembre <?php echo ParameterManager::getInstance()->year1; ?>)</small></td>
						<td class="col-md-3" style="min-width: 100px"><?php fin_write_text_input("ne", "Nb Enfants", true); ?></td>
					</tr>
					<tr>
						<td class="col-md-4" style="white-space: nowrap;"><label>2- Nombre d'adultes (NA) :</label></td>
						<td class="col-md-3" style="min-width: 100px"><?php fin_write_text_input("na", "Nb Adultes"); ?></td>
					</tr>
				</tbody></table>
			</td>
			<td colspan="3"></td>
		</tr>
		<tr>
			<td class="col-md-4"></td>
			<td class="col-md-2"><label>1er Responsable</label></td>
			<td class="col-md-2"><label>2ème Responsable</label></td>
			<td class="col-md-2"><label>Autre Pers.</label></td>
		</tr>
		<tr>
			<td style="text-align:right;"><label>NOM (Rappel) :</label></td>
			<td><?php echo $obj->qfgrid->name1; ?></td>
			<td><?php echo $obj->qfgrid->name2; ?></td>
			<td><?php fin_write_text_input("name3", "", false, false); ?></td>
		</tr>
		<tr>
			<td style="text-align:right;"><label>Lien de Parenté :</label></td>
			<td><?php echo $obj->qfgrid->link1; ?></td>
			<td><?php echo $obj->qfgrid->link2; ?></td>
			<td><?php fin_write_text_input("link3", "", false, false); ?></td>
		</tr>
		<tr>
			<td style="text-align:right;"><label>Profession Exercée :</label></td>
			<td><?php echo $obj->qfgrid->prof1; ?></td>
			<td><?php echo $obj->qfgrid->prof2; ?></td>
			<td><?php fin_write_text_input("prof3", "", false, false); ?></td>
		</tr>
		<tr>
			<td style="text-align:right;"><label>Temps de travail :</label></td>
			<td><select name="tpsw1" <?php echo 'value="' . $obj->qfgrid->tpsw1 . '" ' . $editable; ?>><option value="0">---</option><option value="100">Temps Plein (TP)</option><option value="75">3/4 Temps</option><option value="50">Mi-Temps</option><option value="25">1/4 Temps</option></select></td>
			<td><select name="tpsw2" <?php echo 'value="' . $obj->qfgrid->tpsw2 . '" ' . $editable; ?>><option value="0">---</option><option value="100">Temps Plein (TP)</option><option value="75">3/4 Temps</option><option value="50">Mi-Temps</option><option value="25">1/4 Temps</option></select></td>
			<td><select name="tpsw3" <?php echo 'value="' . $obj->qfgrid->tpsw3 . '" ' . $editable; ?>><option value="0">---</option><option value="100">Temps Plein (TP)</option><option value="75">3/4 Temps</option><option value="50">Mi-Temps</option><option value="25">1/4 Temps</option></select></td>
		</tr>
		<tr>
			<td><label>A. RESSOURCES PERCUES EN <?php echo (ParameterManager::getInstance()->year1 - 1); ?></label></td>
			<td></td><td></td><td></td>
		</tr>
		<tr>
			<td style="text-align:right;"><label>1. SALAIRES :</label><br/><small>Tels que mentionnés sur l’avis d’imposition de <?php echo ParameterManager::getInstance()->year1; ?></small></td>
			<td><?php fin_write_text_input("fi011", ""); ?></td>
			<td><?php fin_write_text_input("fi012", ""); ?></td>
			<td><?php fin_write_text_input("fi013", ""); ?></td>
		</tr>
		<tr>
			<td style="text-align:right;"><label>2. RESSOURCES NETTES :</label><br/><small>Des professions non-salariées, BIC, BNC</small></td>
			<td><?php fin_write_text_input("fi021", ""); ?></td>
			<td><?php fin_write_text_input("fi022", ""); ?></td>
			<td><?php fin_write_text_input("fi023", ""); ?></td>
		</tr>
		<tr>
			<td style="text-align:right;"><label>3. ALLOCATIONS FAMILIALES :</label><br/><small>Le montant des allocations familiales, lorsque celles-ci sont intégrées dans le bulletin de salaire, n'est pas inclus dans le revenu imposable qui est mentionné sur le récapitulatif de fin d'année que fournit l'employeur. Pensez à le rajouter.</small></td>
			<td><?php fin_write_text_input("fi031", ""); ?></td>
			<td><?php fin_write_text_input("fi032", ""); ?></td>
			<td><?php fin_write_text_input("fi033", ""); ?></td>
		</tr>
		<tr>
			<td style="text-align:right;"><label>4. SUPPLÉMENT FAMILIAL :</label><br/><small>A faire ressortir à part obligatoirement des allocations familiales.</small></td>
			<td><?php fin_write_text_input("fi041", ""); ?></td>
			<td><?php fin_write_text_input("fi042", ""); ?></td>
			<td><?php fin_write_text_input("fi043", ""); ?></td>
		</tr>
		<tr>
			<td style="text-align:right;"><label>5. ALLOCATIONS ET PENSIONS :</label><br/><small>Diverses reçues. Précisez SVP : Pensions alimentaires, …</small></td>
			<td><?php fin_write_text_input("fi051", ""); ?></td>
			<td><?php fin_write_text_input("fi052", ""); ?></td>
			<td><?php fin_write_text_input("fi053", ""); ?></td>
		</tr>
		<tr>
			<td style="text-align:right;"><label>6. TOUTES AUTRES RESSOURCES :</label><br/><small>(non liées à une activité professionnelle : Revenus des capitaux mobiliers, aides familiales, bourses, paiement des scolarités par un tiers)</small></td>
			<td><?php fin_write_text_input("fi061", ""); ?></td>
			<td><?php fin_write_text_input("fi062", ""); ?></td>
			<td><?php fin_write_text_input("fi063", ""); ?></td>
		</tr>
		<tr>
			<td style="text-align:right;"><label>7. AVANTAGES EN NATURE :</label><br/><small>(Logement, nourriture, voiture)</small></td>
			<td><?php fin_write_text_input("fi071", ""); ?></td>
			<td><?php fin_write_text_input("fi072", ""); ?></td>
			<td><?php fin_write_text_input("fi073", ""); ?></td>
		</tr>
		<tr>
			<td style="text-align:right;"><label>TOTAL A :</label></td>
			<td><strong><?php echo number_format($obj->qfgrid->totalA1, 0, ',', ' '); ?> €</strong></td>
			<td><strong><?php echo number_format($obj->qfgrid->totalA2, 0, ',', ' '); ?> €</strong></td>
			<td><strong><?php echo number_format($obj->qfgrid->totalA3, 0, ',', ' '); ?> €</strong></td>
		</tr>
		<tr>
			<td><label>B. CHARGES PAYÉES EN <?php echo (ParameterManager::getInstance()->year1 - 1); ?></label><br/><small>(Pour toutes les personnes mentionnées)</small></td>
			<td></td><td></td><td></td>
		</tr>
		<tr>
			<td style="text-align:right;"><label>8. IMPÔT SUR LES REVENUS :</label><br/><small>Payé en <?php echo (ParameterManager::getInstance()->year1 - 1); ?> au titre de l'année <?php echo (ParameterManager::getInstance()->year1 - 2); ?> (non compris les impôts locaux)</small></td>
			<td><?php fin_write_text_input("fi081", ""); ?></td>
			<td><?php fin_write_text_input("fi082", ""); ?></td>
			<td><?php fin_write_text_input("fi083", ""); ?></td>
		</tr>
		<tr>
			<td style="text-align:right;"><label>9. FRAIS PROFESSIONNELS :</label><br/><small>sur les revenus de <?php echo (ParameterManager::getInstance()->year1 - 1); ?> tels qu’ils apparaissent sur l’avis d’imposition <?php echo ParameterManager::getInstance()->year1; ?> : 10 % ou frais réels pour les professions non salariées.</small></td>
			<td><?php fin_write_text_input("fi091", ""); ?></td>
			<td><?php fin_write_text_input("fi092", ""); ?></td>
			<td><?php fin_write_text_input("fi093", ""); ?></td>
		</tr>
		<tr>
			<td style="text-align:right;"><label>10. FRAIS DE GARDE :</label><br/><small>liés à l'activité́ professionnelle des parents, payés pour les enfants non scolarisés en <?php echo (ParameterManager::getInstance()->year1 - 1); ?> (sauf personnel domestique) : Montant retenu sur l’avis d’imposition <?php echo ParameterManager::getInstance()->year1; ?>.</small></td>
			<td><?php fin_write_text_input("fi101", ""); ?></td>
			<td><?php fin_write_text_input("fi102", ""); ?></td>
			<td><?php fin_write_text_input("fi103", ""); ?></td>
		</tr>
		<tr>
			<td style="text-align:right;"><label>11. FRAIS DE SCOLARITÉ :</label><br/><small>payés à l'École Nouvelle au cours de l'année scolaire <?php echo (ParameterManager::getInstance()->year1 - 1); ?> / <?php echo ParameterManager::getInstance()->year1; ?> (non compris les frais de cantine, garderie, classes extérieures).</small></td>
			<td><?php echo number_format($obj->qfgrid->fi111, 0, ',', ' '); ?> €</td>
			<td><?php echo number_format($obj->qfgrid->fi112, 0, ',', ' '); ?> €</td>
			<td><?php fin_write_text_input("fi113", ""); ?></td>
		</tr>
		<tr>
			<td style="text-align:right;"><label>12. PENSIONS ALIMENTAIRES :</label><br/><small>que vous versez en <?php echo (ParameterManager::getInstance()->year1 - 1); ?>.</small></td>
			<td><?php fin_write_text_input("fi121", ""); ?></td>
			<td><?php fin_write_text_input("fi122", ""); ?></td>
			<td><?php fin_write_text_input("fi123", ""); ?></td>
		</tr>
		<tr>
			<td style="text-align:right;"><label>TOTAL B :</label></td>
			<td><strong><?php echo number_format($obj->qfgrid->totalB1, 0, ',', ' '); ?> €</strong></td>
			<td><strong><?php echo number_format($obj->qfgrid->totalB2, 0, ',', ' '); ?> €</strong></td>
			<td><strong><?php echo number_format($obj->qfgrid->totalB3, 0, ',', ' '); ?> €</strong></td>
		</tr>
	</tbody></table>
</div>
<div style="padding: 5px; margin: 10px; background-color: #f0f8ff; border-style: solid; border-color: #e3e3e3; border-width: thin;">
	<table><tbody>
		<tr>
			<td style="width: 100%;"><label>QUOTIENT FAMILIAL (QF) :</label></td>
			<td rowspan=2 style="vertical-align: middle; min-width: 200px; text-align: center;">QF = <strong><?php if( ($obj->qfgrid->qf <> "") && (is_numeric($obj->qfgrid->qf_val)) ) echo number_format($obj->qfgrid->qf_val, 0, ",", " ") . " €"; else echo "?"; ?></strong></td>
			<td rowspan=2 style="min-width: 100px;"><span class="btn btn-info"><strong><?php if( $obj->qfgrid->qf <> "" ) echo $obj->qfgrid->qf; ?></strong></span></td>
		</tr>
		<tr>
			<td><small>Quotient Familial (QF) = (Total A – Total B) / (Nombre d'enfants (NE) + Nombre d'adultes vivant au foyer (NA))</small></td>
		</tr>
	</tbody></table>
</div>
<div style="padding: 5px; margin: 10px; background-color: #f0f8ff; border-style: solid; border-color: #e3e3e3; border-width: thin;">
	<table style="width: 100%;margin-top: 5px;margin-bottom: 5px;"><tbody>
		<tr><td colspan=3><label>JUSTIFICATIFS :</label></td></tr>
		<tr>
			<td class="col-md-4" style="text-align:right;"><label>Avis d'imposition <?php echo (ParameterManager::getInstance()->year1 - 1); ?></label></td>
			<?php fin_write_file_input("favis1"); ?>
		</tr>
		<tr>
			<td class="col-md-4" style="text-align:right;"><label>Avis d'imposition <?php echo ParameterManager::getInstance()->year1; ?></label></td>
			<?php fin_write_file_input("favis2"); ?>
		</tr>
		<tr>
			<td class="col-md-4" style="text-align:right;"><label>RIB (si prélévement)</label></td>
			<?php fin_write_file_input("frib"); ?>
		</tr>
	</tbody></table>
</div>