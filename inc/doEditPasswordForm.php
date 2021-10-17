	<div class="divC">
		<div class="divC" style="width: 100%; margin: 5px; background-color: #f0f8ff; border-style: solid; border-color: #e3e3e3; border-width: thin;">
			<div style="padding: 7px; ">
				<table style="width: 100%;"><tbody>
					<tr>
						<td class="col-md-2" style="vertical-align: top;"><label>Pseudo</label></td>
		<?php if( $user->isFamily() ) { ?>
						<td class="col-md-3"><?php echo $obj->pseudo; ?></td>
		<?php } else { ?>
						<td class="col-md-3"><input type="text" class="form-control form-control-sm" name="pseudo" value="<?php echo $obj->pseudo; ?>" placeholder="Nom" <?php echo $editable; ?> required autofocus/></td>
		<?php } ?>
		<?php if( $obj->activated ) { ?>
						<td class="col-md-2"><span style="color: forestgreen;"><i class="fa fa-check-square-o" aria-hidden="true"></i>&nbsp;activé</span></td>
			<?php if( $obj->edit && ($user->isAdmin() || $user->isSuper()) ) { ?>
						<td class="col-md-3">
							<input type="submit" name="btnSubmitAccess" class="hidden" />
							<button type="submit" name="btnReactivate" class="btn btn-sm btn-primary" style="padding: 1px 5px; text-align: left;"><span class="fa fa-envelope" aria-hidden="true"></span>&nbsp;&nbsp;Renvoyer une demande d'activation</button>
						</td>
			<?php } else { ?>
						<td class="col-md-3"></td>
			<?php } ?>

		<?php } else { ?>
						<td class="col-md-2"><span style="color: red;"><i class="fa fa-square-o" aria-hidden="true"></i>&nbsp;non activé</span></td>
			<?php if( $user->isAdmin() || $user->isSuper() ) { ?>
						<td class="col-md-3">
							<input type="submit" name="btnSubmitAccess" class="hidden" />
							<button type="submit" name="btnReactivate" class="btn btn-sm btn-primary" style="padding: 1px 5px; width:100%; text-align: left;"><span class="fa fa-envelope" aria-hidden="true"></span>&nbsp;&nbsp;Renvoyer une demande d'activation</button>
						</td>
			<?php } else { ?>
						<td class="col-md-3"></td>
			<?php } ?>
		<?php } ?>
					</tr>
					<tr><td colspan=4></td></tr>
					<tr>
						<td rowspan=2 class="col-md-2" style="vertical-align: top;"><label>Mails d'accès</label></td>
						<td class="col-md-3"><?php echo $obj->email1; ?></td>
						<td class="col-md-2">
						<?php if( $obj->validmail1 ) { ?>
							<span style="color: forestgreen;"><i class="fa fa-check-square-o" aria-hidden="true"></i>&nbsp;validée</span>
						<?php } else { ?>
							<span style="color: red;"><i class="fa fa-square-o" aria-hidden="true"></i>&nbsp;non validée</span>
						<?php } ?>
						</td>
						<td class="col-md-3"></td>
					</tr>
					<tr>
						<td class="col-md-3"><?php echo $obj->email2; ?></td>
						<td class="col-md-2">
						<?php if( $obj->validmail2 ) { ?>
							<span style="color: forestgreen;"><i class="fa fa-check-square-o" aria-hidden="true"></i>&nbsp;validée</span>
						<?php } else { ?>
							<span style="color: red;"><i class="fa fa-square-o" aria-hidden="true"></i>&nbsp;non validée</span>
						<?php } ?>
						</td>
						<td class="col-md-3"></td>
					</tr>
		<?php if( $user->isFamily() && $obj->edit ) { ?>
					<tr><td colspan=4></td></tr>
					<tr>
						<td rowspan=3 class="col-md-2" style="vertical-align: top;"><label>Mot de passe</label></td>
						<td class="col-md-3"><input id="password-field1" type="password" class="form-control form-control-sm" name="pwd0" placeholder="Mot de passe actuel" <?php echo $editable; ?>  autofocus /><span toggle="#password-field1" class="fa fa-fw fa-eye field-icon toggle-password"></span></td>
						<td rowspan=3 class="col-md-2"></td>
						<td rowspan=3 class="col-md-3"></td>
					</tr>
					<tr><td class="col-md-3"><input id="password-field2" type="password" class="form-control form-control-sm" name="pwd1" placeholder="Nouveau Mot de passe" <?php echo $editable; ?> /><span toggle="#password-field2" class="fa fa-fw fa-eye field-icon toggle-password"></span></td></tr>
					<tr><td class="col-md-3"><input id="password-field3" type="password" class="form-control form-control-sm" name="pwd2" placeholder="Confirmation Mot de passe" <?php echo $editable; ?> /><span toggle="#password-field3" class="fa fa-fw fa-eye field-icon toggle-password"></span></td></tr>
		<?php } ?>
		<?php if( $user->isFamily() && !$obj->edit ) { ?>
					<tr><td colspan=4></td></tr>
					<tr>
						<td class="col-md-2" style="vertical-align: top;"><label>Mot de passe</label></td>
						<td class="col-md-3"><tt>***********</tt></td>
						<td rowspan=3 class="col-md-2"></td>
						<td rowspan=3 class="col-md-2"></td>
					</tr>
		<?php } ?>
				</tbody></table>
			</div>
		</div>
	</div>