<?php
require_once('../services/Database.php');
require_once('../services/ParameterManager.php');
require_once('../services/InvoiceManager.php');

class InvoiceControler {
	
	/**************************************************************************
	 * Attributes
	 **************************************************************************/
	private $db;						// Database instance
	private $mysqli;					// Database connection
	
	public  $familyId		= 0;		// type: int
	public  $invoiceId		= 0;		// type: int
	public  $periodId		= 0;		// type: int
	
	private $invoices		= null;		// type: Array (InvoiceEntity)
	private $invoice		= null;		// type: InvoiceEntity
	
	private $backUrl		= "";		// type: String
	public $msg_error		= "";		// type: String

	
	/**************************************************************************
	 * Public Functions
	 **************************************************************************/
	
	/**
	 * Initialisation du Controler
	 * <ul>
	 *   <li>Initialisation des attributs <tt>invoiceId</tt> et <tt>backUrl</tt> à partir de la requête GET
	 *   <li>Initialisation de l'accès la database
	 * </ul>
	 *
	 * @public
	 */
	public function initialize($familyId = 0) {
		$this->familyId = $familyId;
		if(isset($_GET['in']) && is_numeric($_GET['in']))	$this->invoiceId	= intval($_GET['in']);
		if(isset($_GET['fa']) && is_numeric($_GET['fa']))	$this->familyId		= intval($_GET['fa']);
		if(isset($_GET['back']))							$this->backUrl		= $_GET['back'];
		
		// Initialize database connection
		$this->db = Database::getInstance();
		$this->mysqli = $this->db->getConnection();
	}
	
	/**
	 * Chargement des factures associées à une période
	 * 
	 * @param $periodId (int) Identifiant de la période de facturation
	 * @param $simu 	(boolean)	<tt>true</tt> pour une simulation de la facturation sur la période
	 * 								<tt>false</tt> pour une consultation des données de facturation en base sur la période
	 * @public
	 */
	public function loadAllInvoices($periodId, $simu) {
		$this->periodId = $periodId;
		if( $this->periodId == 0 ) return;
		if( $simu ) {
			$famList = $this->listFamilies();
			unset($this->invoices);
			$this->invoices = array();
			foreach( $famList as $famId ) {
				$inv = InvoiceManager::getInstance()->generateInvoice($famId, true);
				if( $inv == null ) {
					if ( InvoiceManager::getInstance()->msg_error != "" )
						$this->msg_error .= InvoiceManager::getInstance()->msg_error . '<br/>';
				} else {
					array_push($this->invoices, $inv);
				}
			}
		} else {
			$this->loadAllInvoicesWithoutItems($this->periodId);
		}
	}
	
	/**
	 * Chargement des factures associées à une famille
	 * 
	 * @param $familyId (int) Identifiant de la famille
	 * @public
	 */
	public function loadInvoiceList($familyId) {
		$this->familyId = $familyId;
		$this->loadInvoicesWithoutItems();
	}
	
	/**
	 * Chargement des éléments de facturation pour afficher le détail de la facture (via invoice.php)
	 * Données chargées à partir de l'identifiant de la factue si fourni, sinon à partir de la simulation de facturation
	 * 
	 * @public	(call by invoice.php and invoice-pdf.php)
	 */
	public function loadInvoiceDetails() {
		if( $this->invoiceId == 0 ) {
			if( $this->familyId > 0 ) {
				$inv = InvoiceManager::getInstance()->generateInvoice($this->familyId, true);
				if( $inv == null ) {
					if ( InvoiceManager::getInstance()->msg_error != "") {
						$this->msg_error = InvoiceManager::getInstance()->msg_error;
						return;
					}
				} else {
					$this->invoice = $inv;
				}
			}
		} else {
			$this->loadInvoice();
		}
	}
	
	/**
	 * Permet de vérifier (pour les accès famille) si une famille peut accéder à une facture
	 * 
	 * @public	(call by invoice.php and invoice-pdf.php)
	 */
	public function checkAccesstoInvoice() {
		if( $this->invoiceId != 0 )
			return $this->checkFamilyAccess($this->familyId, $this->invoiceId);
		return true;
	}
		
	/**
	 * Génération du code HTML permettant de visualiser la liste des factures (que ce soit pour une famille ou pour l'administration).
	 * 
	 * @param $familyView (boolean) <tt>true</tt> pour la vue 'Famille' qui présentre qqs différences avec la vue 'Administration'
	 * @public	(call by list-invoice.php, edit-family.php and InvoicePeriodControler.php (edit-invoice-period.php)
	 */
	public function build_ListInvoicesTable($familyView = true) {
		$html = "";
		$html .= "<table id=\"invoiceTable\" class=\"table table-striped table-hover\">\n<thead>";
		$html .= "<tr><th>N° Facture</th>";
		if( !$familyView )	$html .= "<th>Famille</th>";
		$html .= "<th>Intitulé</th>"			.
				 "<th>Période</th>"				.
				 "<th>Montant</th>"				.
				 "<th>Montant réglé</th>"		.
				 "<th>Reste à payer</th>"		.
				 "<th>Statut</th>"				.
				 "<th>Date de paiement</th>"	.
				 "<th>Actions</th></tr></thead>\n";
		$html .= "<tbody>\n";
		
		// Présente la simulation sur la période en cours
		if( $familyView ) {
			$invoice = InvoiceManager::getInstance()->generateInvoice($this->familyId, true);
			if( $invoice == null ) {
				$this->msg_error = InvoiceManager::getInstance()->msg_error;
			} else {
				$html .= "<tr>\n";
				$html .= "<td><a class=\"ls-modal\" href=\"../inc/doEditInvoice.php?in=0\"><i>(en cours)</i></a></td>\n";
				if( !$familyView )	$html .= "<td>" . $invoice->familyName . "</td>\n";
				$html .= "<td>" . $invoice->title . "</td>\n";
				
				$html .= "<td>" . substr($invoice->fromDate, -2) . "/" . substr($invoice->fromDate, 4, 2) . "/" . substr($invoice->fromDate, 0, 4) . " au " .
								  substr($invoice->toDate, -2) . "/" 	. substr($invoice->toDate, 4, 2) . "/" 	. substr($invoice->toDate, 0, 4) . "</td>\n";
				$html .= "<td>" . number_format($invoice->amount, 2) . " €<br/><small><i>(estimation)</i></small></td>\n";
				$html .= "<td></td>\n";
				$html .= "<td>" . number_format($invoice->amount, 2) . " €<br/><small><i>(estimation)</i></small></td>\n";
				$html .= "<td>En cours</td><td></td><td></td></tr>\n";
			}
		}
		
		// Liste l'ensemble des factures de la famille
		foreach( $this->invoices as $invoice ) {
			$html .= "<tr class=\"";
			if( $invoice->status == 0 )
				$html .= "inv-topay";
			elseif( $invoice->status == 1 )
				$html .= "inv-paid";
			elseif( $invoice->status == 2 )
				$html .= "inv-report";
			$html .= "\">\n";
			$html .= "<td><a class=\"ls-modal\" href=\"../inc/doEditInvoice.php?in=" . $invoice->invoiceId . "\">" .
					  substr("00000000" . $invoice->invoiceId, -8) . "</a></td>\n";
			if( !$familyView )
				$html .= "<td><a href=\"../q/efam?fa=" . $invoice->familyId . "\">" . $invoice->familyName . "</a></td>\n";
			$html .= "<td>" . $invoice->title . "</td>\n";
			$html .= "<td>" . substr($invoice->fromDate, -2) . "/" . substr($invoice->fromDate, 4, 2) . "/" . substr($invoice->fromDate, 0, 4) . " au " .
							  substr($invoice->toDate, -2) . "/" 	. substr($invoice->toDate, 4, 2) . "/" 	. substr($invoice->toDate, 0, 4) . "</td>\n";
			$html .= "<td>" . number_format($invoice->toPayAmount, 2) . " €</td>\n";
			$html .= "<td>" . number_format($invoice->paidAmount, 2) . " €</td>\n";
			$html .= "<td>" . number_format(max(0.0, $invoice->toPayAmount - $invoice->paidAmount), 2) . " €</td>\n";
			if( $invoice->status == 0) {
				$html .= "<td>A payer</td>\n";
			} elseif( $invoice->status == 1) {
				$html .= "<td>Payé</td>\n";
			} elseif( $invoice->status == 2) {
				$html .= "<td>Report</td>\n";
			}
			if( $invoice->paymentDate > 0 )
				$html .= "<td>" . substr($invoice->paymentDate, -2) . "/" . substr($invoice->paymentDate, 4, 2) . "/" . substr($invoice->paymentDate, 0, 4) . "</td>\n";
			else
				$html .= "<td></td>\n";
			$html .= "<td>";
			if( $invoice->status == 0 && $familyView ) {
				$html .= "<button class=\"btn btn-sm btn-primary\" onclick=\"payInvoice(" . $invoice->invoiceId . ");\"><span class=\"fa fa-credit-card\" aria-hidden=\"true\"></span> Payer</button>&nbsp;\n";
			}
			$html .= "<a href=\"../q/ipdf?in=" . $invoice->invoiceId . "\"><i class=\"fa fa-file-pdf-o\" data-toggle=\"tooltip\" title=\"Facture\"></i></a>\n";
			if( !$familyView )
				$html .= "&nbsp;<a class=\"ls-modal\" href=\"../inc/doListEntries.php?in=" . $invoice->invoiceId . "\"><i class=\"fa fa-ellipsis-h\" data-toggle=\"tooltip\" title=\"Ecritures\"></i></a>\n";
			$html .= "</td>\n";
			$html .= "</tr>\n";
		}
		$html .= "</tbody>\n</table>\n";
		echo $html;
	}
	
	/**
	 * Génération du code HTML de l'ensemble d'une facture, que ce soit pour une consultation
	 * en ligne ou pour la génération du fichier PDF (via l'outil tcpdf).
	 * 
	 * @param $pdf (boolean) <tt>true</tt> pour le PDF (pas de bouton d'entête ...)
	 * @public	(call by invoice.php and invoice-pdf.php)
	 */
	public function build_DetailInvoiceTable($pdf = false) {
		$html  = "";
		if( !isset($this->invoice) ) return $html;
		
		// En-tête avec les boutons de commande
		if( !$pdf ) {
			$html .= "<table style=\"white-space: nowrap; width: 100%\"><tr>";
			$html .= "<td style=\"text-align: left\"><div style=\"font-size: 18px;font-weight: bold;\">Facture</div></td>";
			$html .= "<td style=\"width:50%; text-align:center;\">";
			if( $this->msg_error <> "") {
				$html .= "<div class=\"alert alert-danger\"><strong>" . $this->msg_error;
				$html .= "</strong><button type=\"button\" class=\"close\" data-dismiss=\"alert\">&times;</button></div>";
			}
			$html .= "</td>";
			$html .= "<td style=\"text-align:right;\">";
			if( $this->backUrl != "") {
				$html .= "<button type=\"button\" class=\"btn btn-default\" onclick=\"location.href='" . urldecode($this->backUrl) . "'\">";
				$html .= "<i class=\"fa fa-undo\" aria-hidden=\"true\"></i></button>&nbsp;&nbsp;";
			}
			$html .= "<button type=\"button\" class=\"btn btn-primary\" onclick=\"location.href='../q/ipdf?in=" . $this->invoiceId ."'\" title=\"Facture PDF\">";
			$html .= "<i class=\"fa fa-file-pdf-o\" aria-hidden=\"true\"></i></button>";
			$html .= "</td></tr></table>\n";
			$html .= "&nbsp;\n";
		}
		
		$html .= "<table style=\"width:100%;\"><tbody><tr>\n";
		
		// En-tête GAUCHE de la facture
		$html .= "<td style=\"vertical-align:top;width:60%;padding:5px;\">\n";
		$html .= "<h3 class=\"titleInv\" style=\"margin-top: 0px;\"><strong>Ecole Nouvelle Antony</strong></h3>";
		$html .= "<p>" . ParameterManager::getInstance()->addr	. "<br/>";
		$html .= 		 ParameterManager::getInstance()->tel	. "<br/>";
		$html .= 		 ParameterManager::getInstance()->email	. "</p>";
		$html .= "<p>";
		$html .= "<strong>" . $this->invoice->title . "</strong><br/>";
		$html .= "Période du " . substr($this->invoice->fromDate, -2) . "/" . substr($this->invoice->fromDate, 4, 2) . "/"  . substr($this->invoice->fromDate, 0, 4) . " au " .
								 substr($this->invoice->toDate, -2) . "/" 	. substr($this->invoice->toDate, 4, 2) . "/" 	. substr($this->invoice->toDate, 0, 4) . "<br/>\n";
		$html .= "Facture du <strong>" . substr($this->invoice->createdDate, -2) . "/" . substr($this->invoice->createdDate, 4, 2) . "/"  . substr($this->invoice->createdDate, 0, 4) . "</strong><br/>";
		
		$html .= "A régler avant le <strong>" . substr($this->invoice->toPayDate, -2) . "/" . substr($this->invoice->toPayDate, 4, 2) . "/"  . substr($this->invoice->toPayDate, 0, 4) . "</strong>";
		$html .= "</p>";
		
		// Infos Famille (Code Famille, QF et Prix unité)
		$html .= "<table id=\"infosFamTab\" cellpadding=\"10\"><tbody><tr>";
		$html .= "<td class=\"infoFam\">Code Famille: " . substr("00000" . $this->invoice->familyId, -5) . "</td>";
		$html .= "<td class=\"infoFam\">Quotient Familial: " . $this->invoice->qf . "</td>";
		$html .= "<td class=\"infoFam\">Prix unité: " . number_format($this->invoice->unitPrice, 2) . " €</td>";
		$html .= "</tr></tbody></table>";
		
		$html .= "</td>";
		
		// En-tête DROITE de la facture (Nom, adresse, ... de la Famille)
		$html .= "<td style=\"text-align:right; vertical-align:top;width:40%;padding:5px;\">";
		$html .= "<h3 class=\"titleInv\" style=\"margin-top: 0px;\"><strong>Facture N°" . substr("00000000" . $this->invoiceId, -8) . "</strong></h3>";
		$html .= "<div style=\"text-align:left; border:1px solid #cecece;padding:10px;border-radius: 25px;\">";
		$html .= "<p><strong>" . $this->invoice->name1 . "<br/>" . $this->invoice->name2 . "</strong><br/>" .
						 $this->invoice->adress . "<br/>" . $this->invoice->cp . " " .$this->invoice->city . "</p>" .
						 $this->invoice->mail1 . "<br/>" . $this->invoice->mail2;
		$html .= "</div>";
		$html .= "</td>";
		$html .= "</tr></tbody></table>";
		
		// Table des éléments de facturation
		$html .= "<table id=\"itemTable\" cellpadding=\"2\">\n";
		$html .= "<thead><tr>" .
				 "<th class=\"itemth1\">Code</th>" .
				 "<th class=\"itemth2\">Libellé</th>" .
				 "<th class=\"itemth3\">Quantité</th>" .
				 "<th class=\"itemth4\">Prix unitaire (€)</th>" .
				 "<th class=\"itemth5\">Prix total (€)</th></tr></thead>\n";
		$html .= "<tbody>\n";
		foreach( $this->invoice->items as $item ) {
			$html .= "<tr>";
			$html .= "<td class=\"itemtd1\">" . $item->cod . "</td>\n";
			$html .= "<td class=\"itemtd2\">" . InvoiceManager::CODE_ACT_LBL[$item->cod] . "</td>\n";
			$html .= "<td class=\"itemtd3\">" . $item->qty . "</td>\n";
			$html .= "<td class=\"itemtd4\">" . number_format($item->unitPrice, 2)	. " €</td>\n";
			$html .= "<td class=\"itemtd5\">" . number_format($item->amount, 2)		. " €</td>\n";
			$html .= "</tr>";
		}
		$html .= "</tbody>\n";
		$html .= "<tfoot><tr>" .
				 "<th class=\"itemtf1\"></th>" .
				 "<th class=\"itemtf2\"></th>" .
				 "<th class=\"itemtf3\"></th>" .
				 "<th class=\"itemtf4\">Sous-total</th>" .
				 "<th class=\"itemtf5\">" . number_format($this->invoice->amount, 2) . " €</th></tr></tfoot>\n";
		$html .= "</table>\n";
		
		if( $pdf ) $html .= "&nbsp;<br/>\n&nbsp;<br/>\n";
		$html .= "<table id=\"amountTab\" cellpadding=\"5\"><tbody><tr>\n";
		if( !$pdf ) $html .= "<td></td>";
		$html .= "<td class=\"amountLb1\"><label>Facturé :</label></td>\n";
		$html .= "<td><div class=\"amount\">" . number_format($this->invoice->amount, 2) . " €</div></td>\n";
		$html .= "<td class=\"amountLb2\"><label>Solde Famille :</label></td>\n";
		$html .= "<td><div class=\"amount\">" . number_format($this->invoice->accountBalance, 2) . " €</div></td>\n";
		$html .= "<td class=\"amountLb3\"><label>A payer :</label></td>\n";
		$html .= "<td><div class=\"amount\">" . number_format($this->invoice->toPayAmount, 2) . " €</div></td>\n";
		$html .= "</tr></tbody></table>\n";
	
		return $html;
	}
	
	/**
	 * Génération du code HTML pour le bon de retour figurant en base de la facture (version PDF)
	 * 
	 * @public	(call only by invoice-pdf.php)
	 */
	public function printReturnInvoiceFooter() {
		$html  = "";
		if( !isset($this->invoice) ) return $html;
		
		$html .= "<table cellpadding=\"2\"><tbody>\n";
		$html .= "<tr><td colspan=\"3\" style=\"border-top: 1px dotted #000;\"><strong>Talon à joindre à votre réglement à adresser à :</strong></td></tr>\n";
		$html .= "<tr>";
		$html .= "<td style=\"width: 45%;\">" . ParameterManager::getInstance()->addr . "<br/><br/><strong>Chèque à l'ordre du CRPA</strong></td>\n";
		$html .= "<td style=\"width: 40%;\">\n";
		$html .= "Facture N°<strong>" . substr("00000000" . $this->invoiceId, -8) . "</strong><br/>\n";
		$html .= "Période du <strong>" . substr($this->invoice->fromDate, -2) . "/" . substr($this->invoice->fromDate, 4, 2) . "/"  . substr($this->invoice->fromDate, 0, 4) . "</strong> au " .
							"<strong>" . substr($this->invoice->toDate, -2) . "/" 	. substr($this->invoice->toDate, 4, 2) . "/" 	. substr($this->invoice->toDate, 0, 4) . "</strong><br/>\n";
		$html .= $this->invoice->name1 . "<br/>" . $this->invoice->name2;
		$html .= "</td>\n";
		$html .= "<td style=\"width: 15%;\">A payer :<br/><strong>" . number_format($this->invoice->toPayAmount, 2) . "</strong></td>\n";
		$html .= "</tr></tbody></table>\n";
		return $html;
	}
	
	/**
	 * Génération du code HTML permettant de visualiser la liste des simulation de facturation sur la période en cours.
	 * 
	 * @public	call only by InvoicePeriodControler.php (edit-invoice-period.php)
	 */
	public function build_ListInvoicesSimulationTable() {
		$totalAmount = 0;
		$html = "";
		$html .= "<table id=\"invoiceTable\" class=\"table table-striped table-hover\">\n<thead>";
		$html .= "<th>Famille</th>"		.
		         "<th>Intitulé</th>"	.
				 "<th>Période</th>"		.
				 "<th>Montant</th>"		.
				 "<th>Statut</th>"		.
				 "</tr></thead>\n";
		$html .= "<tbody>\n";
		
		// Liste l'ensemble des factures de la famille
		foreach( $this->invoices as $invoice ) {
			$totalAmount += $invoice->amount;
			$html .= "<tr>\n";
			$html .= "<td><a class=\"ls-modal\" href=\"../inc/doEditInvoice.php?fa=" . $invoice->familyId . "\">" . 
					  $invoice->familyName . "</a></td>\n";
			$html .= "<td>" . $invoice->title . "</td>\n";
			$html .= "<td>" . substr($invoice->fromDate, -2) . "/" . substr($invoice->fromDate, 4, 2) . "/" . substr($invoice->fromDate, 0, 4) . " au " .
							  substr($invoice->toDate, -2) . "/" 	. substr($invoice->toDate, 4, 2) . "/" 	. substr($invoice->toDate, 0, 4) . "</td>\n";
			$html .= "<td>" . number_format($invoice->amount, 2, ".", " ") . " €</td>\n";
			$html .= "<td>En cours</td>\n";
			$html .= "</tr>\n";
		}
		$html .= "</tbody>\n</table>\n";
		echo $html;
		
		return $totalAmount;
	}
	
	/**************************************************************************
	 * Private Functions - Database access functions
	 **************************************************************************/
	private function loadAllInvoicesWithoutItems($periodId) {
		unset($this->invoices);
		$this->invoices = array();
		$query = "SELECT `ID`, `TITRE`, `ID_FAMILLE`, `NOM_FAMILLE`, `QF_FAMILLE`, `PRIX_UNITE`, `DATE_CREATION`, `DATE_PAIEMENT`, `DATE_LIMITE`, " .
				 "`ID_PERIODE`, `DATE_DEB`, `DATE_FIN`, `REMISE`, `MONTANT`, `SOLDE_FAMILLE`, `MONTANT_A_PAYER`, `MONTANT_REGLE`, `STATUT` " .
				 "FROM `facture` " .
				 "WHERE `ID_PERIODE`=" . $periodId;
		$stmt = $this->mysqli->query($query);
		if( is_object($stmt) ) {
			while($res = $stmt->fetch_array(MYSQLI_NUM)) {
				$invoice = new InvoiceEntity();
				$invoice->invoiceId			= $res[0];
				$invoice->title				= $res[1];
				
				$invoice->familyId			= $res[2];
				$invoice->familyName		= $res[3];
				$invoice->qf				= $res[4];
				$invoice->unitPrice			= $res[5];
	
				$invoice->createdDate		= $res[6];
				$invoice->paymentDate		= $res[7];
				$invoice->toPayDate			= $res[8];
		
				$invoice->periodId			= $res[9];
				$invoice->fromDate			= $res[10];
				$invoice->toDate			= $res[11];
	
				$invoice->rebate			= $res[12];
				$invoice->amount			= $res[13];
				$invoice->accountBalance	= $res[14];
				$invoice->toPayAmount		= $res[15];
				$invoice->paidAmount		= $res[16];
				
				$invoice->status			= $res[17];
				
				array_push($this->invoices, $invoice);
			}
			$stmt->close();
		}
	}
	
	private function loadInvoicesWithoutItems() {
		unset($this->invoices);
		$this->invoices = array();
		$query = "SELECT `ID`, `TITRE`, `NOM_FAMILLE`, `NOM1`, `NOM2`, `ADRESSE`, `CP`, `VILLE`, `EMAIL1`, `EMAIL2`, `QF_FAMILLE`, `PRIX_UNITE`, " .
				 "`DATE_CREATION`, `DATE_PAIEMENT`, `DATE_LIMITE`, `ID_PERIODE`, `DATE_DEB`, `DATE_FIN`, `REMISE`, `MONTANT`, `SOLDE_FAMILLE`, `MONTANT_A_PAYER`, `MONTANT_REGLE`, `STATUT` " .
				 "FROM `facture` " .
				 "WHERE `ID_FAMILLE`=" . $this->familyId;
		$stmt = $this->mysqli->query($query);
		if( is_object($stmt) ) {
			while($res = $stmt->fetch_array(MYSQLI_NUM)) {
				$invoice = new InvoiceEntity();
				$invoice->invoiceId			= $res[0];
				$invoice->title				= $res[1];
				
				$invoice->familyId			= $this->familyId;
				$invoice->familyName		= $res[2];
				
				$invoice->name1				= $res[3];
				$invoice->name2				= $res[4];
				$invoice->adress			= $res[5];
				$invoice->cp				= $res[6];
				$invoice->city				= $res[7];
				$invoice->mail1				= $res[8];
				$invoice->mail2				= $res[9];
				
				$invoice->qf				= $res[10];
				$invoice->unitPrice			= $res[11];
	
				$invoice->createdDate		= $res[12];
				$invoice->paymentDate		= $res[13];
				$invoice->toPayDate			= $res[14];
		
				$invoice->periodId			= $res[15];
				$invoice->fromDate			= $res[16];
				$invoice->toDate			= $res[17];
	
				$invoice->rebate			= $res[18];
				$invoice->amount			= $res[19];
				$invoice->accountBalance	= $res[20];
				$invoice->toPayAmount		= $res[21];
				$invoice->paidAmount		= $res[22];
				
				$invoice->status			= $res[23];
				
				array_push($this->invoices, $invoice);
			}
		}
	}
	
	private function loadInvoice() {
		$query = "SELECT `ID`, `TITRE`, `ID_FAMILLE`, `NOM_FAMILLE`, `NOM1`, `NOM2`, `ADRESSE`, `CP`, `VILLE`, `EMAIL1`, `EMAIL2`, `QF_FAMILLE`, `PRIX_UNITE`, " .
				 "`DATE_CREATION`, `DATE_PAIEMENT`, `DATE_LIMITE`, `ID_PERIODE`, `DATE_DEB`, `DATE_FIN`, `REMISE`, `MONTANT`, `SOLDE_FAMILLE`, `MONTANT_A_PAYER`, `MONTANT_REGLE`, `STATUT` " .
				 "FROM `facture` " .
				 "WHERE `ID`=" . $this->invoiceId;
		$stmt = $this->mysqli->query($query);
		if( is_object($stmt) ) {
			if($res = $stmt->fetch_array(MYSQLI_NUM)) {
				$this->invoice 				= new InvoiceEntity();
				$this->invoice->invoiceId		= $res[0];
				$this->invoice->title			= $res[1];
				
				$this->invoice->familyId		= $res[2];
				$this->invoice->familyName		= $res[3];
				
				$this->invoice->name1			= $res[4];
				$this->invoice->name2			= $res[5];
				$this->invoice->adress			= $res[6];
				$this->invoice->cp				= $res[7];
				$this->invoice->city			= $res[8];
				$this->invoice->mail1			= $res[9];
				$this->invoice->mail2			= $res[10];
				
				$this->invoice->qf				= $res[11];
				$this->invoice->unitPrice		= $res[12];
	
				$this->invoice->createdDate		= $res[13];
				$this->invoice->paymentDate		= $res[14];
				$this->invoice->toPayDate		= $res[15];
		
				$this->invoice->periodId		= $res[16];
				$this->invoice->fromDate		= $res[17];
				$this->invoice->toDate			= $res[18];

				$this->invoice->rebate			= $res[19];
				$this->invoice->amount			= $res[20];
				$this->invoice->accountBalance	= $res[21];
				$this->invoice->toPayAmount		= $res[22];
				$this->invoice->paidAmount		= $res[23];
				
				$this->invoice->status			= $res[24];
				
				$this->loadInvoiceItems();
			}
		}
	}
	
	private function loadInvoiceItems() {
		unset($this->invoice->items);
		$this->invoice->items = array();
		$query = "SELECT `CODE`, `QTE`, `QTE_UNITE_PAR_ACT`, `PRIX_UNITAIRE`, `MONTANT` ".
				 "FROM `ligne_facture` " .
				 "WHERE `ID_FACTURE`=" . $this->invoiceId;
		$stmt = $this->mysqli->query($query);
		if( is_object($stmt) ) {
			while($res = $stmt->fetch_array(MYSQLI_NUM)) {
				$item = new InvoiceItemEntity();
				$item->cod			= $res[0];
				$item->qty			= $res[1];
				$item->unitQty		= $res[2];
				$item->unitPrice	= $res[3];
				$item->amount		= $res[4];
				
				array_push($this->invoice->items, $item);
			}
		}
	}
	
	private function listFamilies() {
		$famList = array();
		$query = "SELECT `ID` FROM `famille`";
		$stmt = $this->mysqli->query($query);
		if( is_object($stmt) ) {
			while($res = $stmt->fetch_array(MYSQLI_NUM)) array_push($famList, intval($res[0]));
		}
		return $famList;
	}
	
	private function checkFamilyAccess($familyId, $invoiceId) {
		$query = "SELECT `ID` FROM `facture` "		.
				 "WHERE `ID`=" 	. $invoiceId	. " AND " .
					   "`ID_FAMILLE`="	. $familyId		. " LIMIT 1";
//		echo "query=$query<br/>";
		$stmt = $this->mysqli->query($query);
		if( is_object($stmt) )
			if($res = $stmt->fetch_array(MYSQLI_NUM)) return true;
		return false;
	}
}
?>