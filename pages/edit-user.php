<?php
/**************************************************
 * Code transaction : q/eusr
 **************************************************/  
 
require_once('../services/AccessManager.php');
session_start ();
if( isset($_SESSION ['user']) ) $user = $_SESSION ['user'];
if( !isset($user) ) header('location:../q/logi');

// Access Family Item
require_once('../services/UserFormControler.php');
$obj=new UserFormControler();
$obj->initialize( $user->getUserId(), $user->isAdmin() || $user->isSuper() );

// Access control
if( !$user->isAdmin() && !$user->isSuper() )
	$obj->add = false;
	
// Load data of this page
$obj->load();

// Parse request
$obj->parse_request();

// Load data of this page
if( $obj->msg_error == "")
	$obj->load();

$editable = "readonly";
if( $obj->edit || $obj->add ) $editable = "";
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
<link href="../dist/css/app.css"	rel="stylesheet" type="text/css">
<link href="../dist/css/eusr.css"	rel="stylesheet" type="text/css">
<title>Edition Utilisateur</title>
</head>

<body>

<div id="wrapper">

<!-- Navigation -->
<?php include('../inc/doLeftbar.php'); ?>

<div id="page-wrapper">
	<table style="white-space: nowrap; width: 100%;margin:0;">
		<tr>
			<td style="text-align: left; height: 35px;">
<?php if( $obj->back != "" ) { ?>
				<button class="btn btn-sm btn-default btn-ligh" style="border: none; height: 30px; font-size: 18px; line-height: 1;" onclick="location.href='<?php echo $obj->backUrl; ?>'"><i class="fa fa-angle-left"></i></button>
<?php }?>
<?php if( $obj->add ) { ?>
				<div style="display: inline-block; font-size: 18px;font-weight: bold;">Création d'un nouvel utilisateur</div>
<?php } else { ?>
				<div style="display: inline-block; font-size: 18px;font-weight: bold;">Utilisateur <?php echo $obj->userId; ?></div>
<?php } ?>
			</td>
			<td style="width:10%;">&nbsp;</td>
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
<?php if( !$obj->add && !$obj->edit && !$obj->del) { ?>
				<form method="post" id="editForm">
					<input type="hidden" name="edit"	value="1" >
					<input type="hidden" name="id" value="<?php echo $obj->userId; ?>" />
					<input type="hidden" name="bk" value="<?php echo $obj->back; ?>" />
					<button type="submit" name="btnEdit" class="btn btn-sm btn-primary" ><span class="glyphicon glyphicon-edit" aria-hidden="true"></span> Edit</button>
				</form>
<?php } ?>
			</td>
		</tr>
	</table>
<?php if( !$obj->del) { ?>
	<br/>
	<form method="post" id="formfield">
	<input type="hidden" name="id" value="<?php echo $obj->userId; ?>" />
	<input type="hidden" name="bk" value="<?php echo $obj->back; ?>" />
	<div class="container-fluid border rounded-top bg-light" style="padding-top:10px;margin-bottom:10px;">
		<table class="form-tab"><tbody>
			<tr>
				<td class="col-md-2" style="vertical-align: top;"><label>Pseudo</label></td>
	<?php if( !$obj->add ) { ?>
				<td class="col-md-3"><?php echo $obj->userId; ?></td>
	<?php } else { ?>
				<td class="col-md-3"><input type="text" class="form-control form-control-sm" name="pseudo" value="<?php echo $obj->userId; ?>" placeholder="Nom" <?php echo $editable; ?> required autofocus/></td>
	<?php } ?>
				<td class="col-md-4"></td>
			</tr>
			<tr><td colspan=3></td></tr>
			<tr>
				<td class="col-md-2" style="vertical-align: top;"><label>Nom</label></td>
				<td class="col-md-3"><input type="text" class="form-control form-control-sm" name="name" value="<?php echo $obj->name; ?>" placeholder="Nom" <?php echo $editable; ?> required autofocus/></td>
				<td class="col-md-4"></td>
			</tr>
			<tr><td colspan=3></td></tr>
			<tr>
				<td class="col-md-2" style="vertical-align: top;"><label>Prénom</label></td>
				<td class="col-md-3"><input type="text" class="form-control form-control-sm" name="firstname" value="<?php echo $obj->firstname; ?>" placeholder="Nom" <?php echo $editable; ?> required/></td>
				<td class="col-md-4"></td>
			</tr>
			<tr><td colspan=3></td></tr>
			<tr>
				<td class="col-md-2" style="vertical-align: top;"><label>Mail d'accès</label></td>
				<td class="col-md-3"><input type="text" class="form-control form-control-sm" name="email" value="<?php echo $obj->email; ?>" placeholder="Nom" <?php echo $editable; ?> required/></td>
				<td class="col-md-4">
			<?php if( !$obj->add ) { ?>
				<?php if( $obj->validmail ) { ?>
					<span style="color: forestgreen;"><i class="fa fa-check-square-o" aria-hidden="true"></i>&nbsp;validée</span>
				<?php } else { ?>
					<span style="color: red;"><i class="fa fa-square-o" aria-hidden="true"></i>&nbsp;non validée</span>
				<?php } ?>
			<?php } ?>
				</td>
			</tr>
	<?php if( $user->isAdmin() || $user->isSuper() ) { ?>
			<tr><td colspan=3></td></tr>
			<tr>
				<td class="col-md-2" style="vertical-align: top;"><label>Droit Animateur</label></td>
				<td class="col-md-3"><input type="checkbox" name="anim" <?php if( $obj->anim ) echo "checked "; if( !$obj->edit && !$obj->add )	echo "disabled "; ?>/></td>
				<td class="col-md-4"></td>
			</tr>
			<tr><td colspan=3></td></tr>
			<tr>
				<td class="col-md-2" style="vertical-align: top;"><label>Droit Admin</label></td>
				<td class="col-md-3"><input type="checkbox" name="admin" <?php if( $obj->admin ) echo "checked "; if( !$obj->edit && !$obj->add )	echo "disabled "; ?>/></td>
				<td class="col-md-4"></td>
			</tr>
	<?php } ?>
	<?php if( $obj->isOwner && $obj->edit ) { ?>
			<tr><td colspan=3></td></tr>
			<tr>
				<td rowspan=3 class="col-md-2" style="vertical-align: top;"><label>Mot de passe</label></td>
				<td class="col-md-3"><input id="password-field1" type="password" class="form-control form-control-sm" name="pwd0" placeholder="Mot de passe actuel" <?php echo $editable; ?> /><span toggle="#password-field1" class="fa fa-fw fa-eye field-icon toggle-password"></span></td>
				<td rowspan=3 class="col-md-4"></td>
			</tr>
			<tr><td class="col-md-3"><input id="password-field2" type="password" class="form-control form-control-sm" name="pwd1" placeholder="Nouveau Mot de passe" <?php echo $editable; ?> /><span toggle="#password-field2" class="fa fa-fw fa-eye field-icon toggle-password"></span></td></tr>
			<tr><td class="col-md-3"><input id="password-field3" type="password" class="form-control form-control-sm" name="pwd2" placeholder="Confirmation Mot de passe" <?php echo $editable; ?> /><span toggle="#password-field3" class="fa fa-fw fa-eye field-icon toggle-password"></span></td></tr>
	<?php } ?>
	<?php if( $obj->isOwner && !$obj->edit ) { ?>
			<tr><td colspan=3></td></tr>
			<tr>
				<td class="col-md-2" style="vertical-align: top;"><label>Mot de passe</label></td>
				<td class="col-md-3"><tt>***********</tt></td>
				<td rowspan=3 class="col-md-4"></td>
			</tr>
	<?php } ?>
		</tbody></table>
	</div>
	<?php if ($obj->edit || $obj->add) { ?>
	<table style="width: 100%;table-layout: fixed;border: 1px;"><tr>
		<td><div style="width:100%; text-align:right;font-style: italic;">
		<input type="hidden" name="bk" value="<?php echo $obj->back; ?>" />
		<?php if ($obj->edit ) { ?>
		<input type="hidden" name="edit" value="1" >
		<input type="hidden" name="delete" value="0" >
		<?php } elseif ($obj->add ) { ?>
		<input type="hidden" name="add"	value="1" >
		<?php } ?>
		<input type="submit" name="btnSubmit" class="hidden" />
		<button type="submit" name="cancel" class="btn btn-light" formnovalidate>Annuler</button>
		<input type="submit" value="Enregistrer" name="btnSubmit" class="btn btn-success" />
		<?php if( $user->isAdmin() || $user->isSuper() ) { ?>
		<button type="button" name="btnDelete" class="btn btn-info" onclick="$('#confirmationModal').modal('show');"><span class="glyphicon glyphicon-erase" aria-hidden="true"></span> Supprimer</button>
		<?php } ?>
		</div>
	</td></tr></table>
	<?php } ?>
	</form>
<?php } // Endif( !$obj->del) ?>
</div>
</div>

<?php if( $user->isAdmin() || $user->isSuper() ) { ?>
<!-- Modal -->
<div class="modal fade" id="confirmationModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLongTitle">Confirmation de la suppression d'un utilisateur</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        Confirmez-vous la suppression de l'utilisateur ?
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
        <button type="button" class="btn btn-primary"  onclick="$('#formfield input[name=delete]').val(1); $('#formfield').submit();">Valider</button>
      </div>
    </div>
  </div>
</div>
<?php } ?>

<script src="../dist/js/jquery.min.js" ></script>
<script src="../dist/js/bootstrap.min.js" ></script>
<script src="../dist/js/metisMenu.min.js" ></script>
<script src="../dist/js/sb-admin-2.js" ></script>
<script src="../dist/js/app.js"></script>
<script src="../dist/js/eusr.js" ></script>
</body>
</html>