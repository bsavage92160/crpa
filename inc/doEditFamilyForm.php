<?php
$famFormAutofocus = 0;

function fam_write_text_input($name, $placeholder, $autofocus = false, $required = true, $numeric = false, $phoneNumber = false) {
	_fam_write_input("text", $name, $placeholder, $autofocus, $required, $numeric, $phoneNumber);
}

function fam_write_email_input($name, $placeholder, $autofocus = false, $required = true) {
	_fam_write_input("email", $name, $placeholder, $autofocus, $required, false, false);
}

function _fam_write_input($type, $name, $placeholder, $autofocus = false, $required = true, $numeric = false, $phoneNumber = false) {
	global $obj, $editable, $famFormAutofocus;
	$html = "";
	$html .= "<input type=\"" . $type . "\" name=\"" . $name . "\" value=\"" ;
	if( $numeric && is_numeric($obj->$name) )	$html .= number_format($obj->$name, 0, ",", " ");
	else 										$html .= $obj->$name;
	$html .= "\" placeholder=\"" . $placeholder . "\" " . $editable . " required ";
	if($autofocus && $famFormAutofocus == 0) $html .= " autofocus";
	$html .= "/>";
	if( isset($obj->msg_error[$name]) ) {
		if( $obj->msg_error[$name] <> "" )
			$html .= "<small style=\"margin-top: 0px; color: #dc2a26;\">" . $obj->msg_error[$name] . "</small>";
	}
	if( $autofocus )
		$famFormAutofocus++;
	echo $html;
}

?>
	<div class="divC">
		<div class="divR">
			<div class="divC" style="width: 50%; margin: 5px; background-color: #f0f8ff; border-style: solid; border-color: #e3e3e3; border-width: thin;">
				<div style="padding: 7px; ">
					<div><label>Représentant légal 1</label></div>
					<div class="divR">
						<div style="width: 15%;"><select name="gender1" class="form-control form-control-sm" <?php echo $editable; ?> required >
							<option value="M"   <?php if( $obj->gender1 == "M" )   echo "selected"; ?>>M.</option>
							<option value="MME" <?php if( $obj->gender1 == "MME" ) echo "selected"; ?>>Mme</option>
						</select></div>
						<div style="width: 40%;"><?php fam_write_text_input("name1", "Nom", true, true); ?></div>
						<div style="width: 45%;"><?php fam_write_text_input("firstname1", "Prénom", false, true); ?></div>
					</div>
					<div style="width: 100%;"><?php fam_write_email_input("email1", "Mail", false, true); ?></div>
					<div class="divR">
						<div style="width: 15%;"><select name="link1" class="form-control form-control-sm" <?php echo $editable; ?> <?php if( !$obj->add ) echo "required"; ?> >
							<option value="P" <?php if( $obj->link1 == "P" ) echo "selected"; ?>>Père</option>
							<option value="M" <?php if( $obj->link1 == "M" ) echo "selected"; ?>>Mère</option>
						</select></div>
						<div style="width: 45%;"><?php fam_write_text_input("prof1", "Profession", false, (!$obj->add)); ?></div>
						<div style="width: 20%;"><?php fam_write_text_input("phone11", "Téléphone personnel", false, (!$obj->add), false, true); ?></div>
						<div style="width: 20%;"><?php fam_write_text_input("phone12", "Téléphone professionel", false, (!$obj->add), false, true); ?></div>
					</div>
					<div class="divR">
						<div style="width: 60%;"><?php fam_write_text_input("address1", "Adresse", false, (!$obj->add)); ?></div>
						<div style="width: 10%;"><?php fam_write_text_input("cp1", "CP", true, (!$obj->add)); ?></div>
						<div style="width: 30%;"><?php fam_write_text_input("city1", "Ville", false, (!$obj->add)); ?></div>
					</div>
				</div>
			</div>
			<div class="divC" style="width: 50%; margin: 5px; background-color: #f0f8ff; border-style: solid; border-color: #e3e3e3; border-width: thin;">
				<div style="padding: 7px; ">
					<div class="divR">
						<div><label>Représentant légal 2</label></div>
						<div style="flex: 1; text-align: right;"><label style="font-weight: normal; font-style: italic;"><small><input type="checkbox" name="only1LegalRepresentative" id="only1LegalRepresentative" <?php if($obj->only1LegalRp) echo "checked"; ?>> cocher si 1 seul représentant légal</small></label></div>
					</div>
					<?php
						$editablebkp = $editable;
						if($obj->only1LegalRp) $editable = "readonly";
					?>
					<div class="divR">
						<div style="width: 15%;"><select name="gender2" class="form-control form-control-sm" <?php echo $obj->only1LegalRp ? "disabled" : $editable; ?> required >
							<option value="M"   <?php if( $obj->gender2 == "M" )   echo "selected"; ?>>M.</option>
							<option value="MME" <?php if( $obj->gender2 == "MME" ) echo "selected"; ?>>Mme</option>
						</select></div>
						<div style="width: 40%;"><?php fam_write_text_input("name2", "Nom", true, true); ?></div>
						<div style="width: 45%;"><?php fam_write_text_input("firstname2", "Prénom", false, true); ?></div>
					</div>
					<div style="width: 100%;"><?php fam_write_email_input("email2", "Mail", false, true); ?></div>
					<div class="divR">
						<div style="width: 15%;"><select name="link2" class="form-control form-control-sm" <?php echo $obj->only1LegalRp ? "disabled" : $editable; ?> <?php if( !$obj->add ) echo "required"; ?> >
							<option value="P" <?php if( $obj->link2 == "P" ) echo "selected"; ?>>Père</option>
							<option value="M" <?php if( $obj->link2 == "M" ) echo "selected"; ?>>Mère</option>
						</select></div>
						<div style="width: 45%;"><?php fam_write_text_input("prof2", "Profession", false, (!$obj->add)); ?></div>
						<div style="width: 20%;"><?php fam_write_text_input("phone21", "Téléphone personnel", false, (!$obj->add), false, true); ?></div>
						<div style="width: 20%;"><?php fam_write_text_input("phone22", "Téléphone professionel", false, (!$obj->add), false, true); ?></div>
					</div>
					<div class="divR">
						<div style="width: 60%;"><?php fam_write_text_input("address2", "Adresse", false, (!$obj->add)); ?></div>
						<div style="width: 10%;"><?php fam_write_text_input("cp2", "CP", true, (!$obj->add)); ?></div>
						<div style="width: 30%;"><?php fam_write_text_input("city2", "Ville", false, (!$obj->add)); ?></div>
					</div>
					<?php
						$editable = $editablebkp;
					?>
				</div>
			</div>
		</div>
		
		<div class="divC" style="margin: 5px; background-color: #f0f8ff; border-style: solid; border-color: #e3e3e3; border-width: thin;">
			<div style="padding: 7px; ">
				<div><label>Enfants</label></div>
				<div>
					<table class="child-tab"><tbody>
						<tr>
							<td class="col-md-1" style="width: 5%;"></td>
							<?php if( !$user->isFamily() ) { ?><td class="col-md-2"><small>Nom</small></td><?php } ?>
							<td class="col-md-2"><small>Prénom</small></td>
							<td class="col-md-2"><small>Garçon / Fille</small></td>
							<td class="col-md-2"><small>Date Naissance</small></td>
							<td class="col-md-1"><small>Niveau</small></td>
							<td class="col-md-2"><small>Classe</small></td>
							<?php if( !$user->isFamily() ) { ?><td class="col-md-1"><small>Présent</small></td><?php } ?>
						</tr>
<?php
				$colName		= array("name", "firstname", "gender", "bth", "lev", "cl");
				$colVals		= array();
				$today			= time();
				$cur			= intval(date('d/m/Y', $today));
				$placeholderLst	= array("Nom", "Prénom", "Garçon / Fille", $cur, "PS/MS/GS/CP/...", "PS-MS/MS-GS...");
				$html			= "";
				$i				= 0;
				foreach( $obj->children as $child ) {
					$i++;
					$existing = isset($child['id']);
					$colVals[0] = strtoupper($child['name']);
					$colVals[1] = ucfirst(strtolower($child['firstname']));
					$colVals[2] = $child['gender'];
					$d	= mktime(0, 0, 0, intval($child['birthM']), intval($child['birthD']), intval($child['birthY']));
					$colVals[3] = date('Ymd', $d);;
					$colVals[4] = strtoupper($child['level']);
					$colVals[5] = strtoupper($child['class']);
					
					if( $existing )
						$html .= "<input type=\"hidden\" name=\"child[0][" . $i . "][id]\" value=\"" . $child['id'] . "\" />\n";
					$html .= "<tr>";
					$html .= "<td><label>" . $i . "</label></td>";
					for ($j=0; $j<6; $j++) {
						if( $j != 0 || !$user->isFamily() ) {
							
							$name = "child[";
							if( $existing )	$name .= "0";
							else 			$name .= "1";
							$name .= "][" . $i . "][" . $colName[$j] . "]";
							
							$html .= "<td>";
							
							if( $j == 2 ) { 									// Gender
								$gender = $colVals[$j];
								$html .= "<select class=\"form-control form-control-sm\" ";
								$html .= "name=\"" . $name . "\" ";
								if( $user->isFamily() )
									$html .= "disabled ";
								$html .= $editable;
								$html .= ">\n";
								$html .= "<option value=\"G\" ";
								if( $gender == "G" )
									$html .= "selected";
								$html .= ">Garçon</option>\n";
								$html .= "<option value=\"F\" ";
								if( $gender == "F" )
									$html .= "selected";
								$html .= ">Fille</option>\n";
								$html .= "</select>\n";

							} elseif( $j == 3 ) { 									// Birth
							
								if ($obj->edit || $obj->add) {
									$html .= "<div class=\"input-group date form_date col-md-5\" data-date=\"\" data-date-format=\"dd/mm/yyyy\" " .
										     "data-link-field=\"inDT_" . $i . "\" data-link-format=\"yyyymmdd\">\n";
								}
								$html .= "<input class=\"form-control\" style=\"width: 120px; text-align: left; margin: 0;\" type=\"text\" " .
										 "value=\"" . substr($colVals[3], -2) . "/" . substr($colVals[3], 4, 2) . "/" . substr($colVals[3], 0, 4) . "\" " . $editable . ">\n";
								
								if ($obj->edit || $obj->add) {
									$html .= "<span class=\"input-group-addon\" style=\"padding: 0px 12px 0px 12px;\"><span class=\"glyphicon glyphicon-calendar\"></span></span>\n";
									$html .= "</div>\n";
									$html .= "<input type=\"hidden\" id=\"inDT_" . $i . "\" name=\"" . $name . "\" value=\"" . $colVals[3] . "\" />\n";
								}

							} else {
								$html .= "<input type=\"text\" class=\"form-control form-control-sm\" ";
								$html .= "name=\"" . $name . "\" ";
								$html .= "value=\"" . $colVals[$j] . "\" placeholder=\"" . $placeholderLst[$j] . "\" ";
								if( $j == 1 && $user->isFamily() )
									$html .= "Readonly";
								else
									$html .= $editable;
								$html .= " />";
							}
							$html .= "</td>";
						}
						if( ($j == 0 || $j == 1 || $j == 2) && $user->isFamily() ) {
							$html .= "<input type=\"hidden\" ";
							$html .= "name=\"child[";
							if( $existing )	$html .= "0";
							else 			$html .= "1";
							$html .= "][" . $i . "][" . $colName[$j] . "]\" value=\"" . $colVals[$j] . "\" placeholder=\"" . $placeholderLst[$j] . "\" />";
						}
					}
					if( !$user->isFamily() ) {
						$html .= "<td><input type=\"checkbox\" name=\"child[0][" . $i . "][active]\" ";
						if( $child['active'] )			$html .= "checked ";
						if( !$obj->edit && !$obj->add )	$html .= "disabled ";
						$html .= "/></td>";
					}
					$html .= "</tr>\n";
				}
				if( !$user->isFamily() && ($user->isAdmin() || $user->isSuper()) ) {
					if ($i < 5 && ($obj->edit || $obj->add)) {
						for ($j=$i+1; $j<6; $j++) {
							$html .= "<tr>";
							$html .= "<td><label>" . $j . "</label></td>";
							for ($k=0; $k<6; $k++) {
								$name = "child[1][" . $j . "][" . $colName[$k] . "]";
								$html .= "<td>";
								if( $k == 2 ) { 									// Gender
									$gender = $colVals[$j];
									$html .= "<select class=\"form-control form-control-sm\" ";
									$html .= "name=\"" . $name . "\" >\n";
									$html .= "<option value=\"G\">Garçon</option>\n";
									$html .= "<option value=\"F\">Fille</option>\n";
									$html .= "</select>\n";
									
								} elseif( $k == 3 ) { 									// Birth
								
									$html .= "<div class=\"input-group date form_date col-md-5\" data-date=\"\" data-date-format=\"dd/mm/yyyy\" " .
											 "data-link-field=\"inDT_" . $i . "\" data-link-format=\"yyyymmdd\">\n";
									$html .= "<input class=\"form-control myinput myinput-xs\" style=\"width: 120px; text-align: left; margin: 0;\" type=\"text\" " .
											 "value=\"\" " . $editable . ">\n";
									$html .= "<span class=\"input-group-addon\" style=\"padding: 0px 12px 0px 12px;\"><span class=\"glyphicon glyphicon-calendar\"></span></span>\n";
									$html .= "</div>\n";
									$html .= "<input type=\"hidden\" id=\"inDT_" . $i . "\" name=\"" . $name . "\" value=\"\" />\n";

								} else {
									$html .= "<input type=\"text\" class=\"form-control form-control-sm\" ";
									$html .= "name=\"" . $name . "\" placeholder=\"" . $placeholderLst[$k] . "\" />";
								}
								$html .= "</td>";
							}
							$html .= "<td><input type=\"checkbox\" name=\"child[1][" . $j . "][active]\" ";
							if( !$obj->edit && !$obj->add )	$html .= "disabled ";
							$html .= "/></td></tr>\n";
						}
					}
				}
				echo $html;
?>
					</tbody></table>
				</div>
			</div>
		</div>
	</div>