<?php
require_once('../services/Database.php');
require_once('../services/InvoiceManager.php');

class PaymentControler {
	
	/**************************************************************************
	 * Attributes
	 **************************************************************************/
	private $db;						// Database instance
	private $mysqli;					// Database connection
	
	public  $userId			= "";		// type: String
	
	public  $invoiceNoStr	= "";		// type: String
	public  $amountStr		= "";		// type: String
	public  $stateStr		= "";		// type: String
	public  $comment		= "";		// type: String
	private $invoiceId		= 0;		// type: int
	private $amount			= 0.0;		// type: float
	public  $state			= 0;		// type: int	(0: formulaire vide, 1: vérification facture, 2: validation finale)
	
	private $invoice		= null;		// type: InvoiceEntity
	private $isValidation	= false;	// type: boolean
	public  $msg_error1		= "";		// type: String
	public  $msg_error2		= "";		// type: String
	public  $msg_error3		= "";		// type: String
	public  $msg_success	= "";		// type: String

	
	/**************************************************************************
	 * Public Functions
	 **************************************************************************/
	
	public function initialize($userId) {
		// Initialize database connection
		$this->db = Database::getInstance();
		$this->mysqli = $this->db->getConnection();
		
		$this->userId	= $userId;
	}
	
	public function parse_request() {
		if( !isset($_POST['submit']) )	return;
		if( isset($_POST['invoiceNo']) ) 	$this->invoiceNoStr	= $_POST['invoiceNo'];
		if( isset($_POST['amount']) ) 		$this->amountStr	= $_POST['amount'];
		if( isset($_POST['state']) ) 		$this->stateStr		= $_POST['state'];
		if( isset($_POST['comment']) ) 		$this->comment		= $_POST['comment'];
		
		// Contrôle et chargement du n° de facture
		if( !is_numeric($this->invoiceNoStr) ) {
			$this->msg_error1 = "N° de facture non valide";
			return;
		}
		$this->invoiceId	= intval($this->invoiceNoStr);
		$this->loadInvoice();
		if( $this->invoice == null ) {
			$this->msg_error1 = "N° de facture non trouvé";
			return;
		}
		
		// Contrôle du montant saisi
		$this->amountStr = trim(str_replace(' ', '', $this->amountStr));
		$this->amountStr = str_replace(',', '.', $this->amountStr);
		if( !is_numeric($this->amountStr) ) {
			$this->msg_error2 = "Montant renseigné non valide";
			return;
		}
		$this->amount		= floatval($this->amountStr);
		
		
		// Contrôle du commentaire saisi
		if( strlen(trim($this->comment)) == 0.0 ) {
			$this->msg_error3 = "Commentaire obligatoire pour une écriture en comptabilité";
			return;
		}
		
		// Contrôle du champ d'état
		if( is_numeric($this->stateStr) ) $this->state = intval($this->stateStr);
		$this->state++;
		if( $this->state == 2 ) {
			InvoiceManager::getInstance()->insertPaymentAccountingRecord($this->invoiceId,
																		 $this->invoice->familyId,
																		 $this->amount,
																		 "Manual entry > " . $this->comment,
																		 $this->userId);
			InvoiceManager::getInstance()->updatePaidAmount($this->invoiceId, $this->amount);
			$this->msg_success = "Demande enregistrée";
			$this->loadInvoice();
			$this->state = 0;
		}
	}
	
	public function generate_html_invoice() {
		if( $this->invoice == null ) return;
		$html = "";
		$html .= "<table id=\"invoiceTable\" class=\"table table-striped table-hover\">\n<thead>";
		$html .= "<tr><th>N° Facture</th>"		. 
				 "<th>Famille</th>"				.
				 "<th>Intitulé</th>"			.
//				 "<th>Période</th>"				.
				 "<th>Montant</th>"				.
				 "<th>Montant à payer</th>"		.
				 "<th>Montant réglé</th>"		.
				 "<th>Reste à payer</th>"		.
				 "<th>Statut</th>"				.
				 "<th>Date de paiement</th>"	.
				 "</tr></thead>\n";
		$html .= "<tbody>\n";
		$html .= "<tr>\n";
		$html .= "<td><a class=\"ls-modal\" href=\"../inc/doEditInvoice.php?in=" . $this->invoice->invoiceId . "\">" .
				  substr("00000000" . $this->invoice->invoiceId, -8) . "</a></td>\n";
		$html .= "<td><a href=\"../q/efam?fa=" . $this->invoice->familyId . "\">" . $this->invoice->familyName . "</a></td>\n";
		$html .= "<td>" . $this->invoice->title . "</td>\n";
//		$html .= "<td>" . substr($this->invoice->fromDate, -2) . "/" . substr($this->invoice->fromDate, 4, 2) . "/" . substr($this->invoice->fromDate, 0, 4) . " au " .
//						  substr($this->invoice->toDate, -2) . "/" 	. substr($this->invoice->toDate, 4, 2) . "/" 	. substr($this->invoice->toDate, 0, 4) . "</td>\n";
		$html .= "<td>" . number_format($this->invoice->amount, 2, ',', ' ') . " €</td>\n";
		$html .= "<td>" . number_format($this->invoice->toPayAmount, 2, ',', ' ') . " €</td>\n";
		$html .= "<td>" . number_format($this->invoice->paidAmount, 2, ',', ' ') . " €</td>\n";
		$html .= "<td>" . number_format(max(0.0, $this->invoice->toPayAmount - $this->invoice->paidAmount), 2, ',', ' ') . " €</td>\n";
		if( $this->invoice->status == 0 ) {
			$html .= "<td>A payer</td>\n";
		} elseif( $this->invoice->status == 1 ) {
			$html .= "<td>Payé</td>\n";
		} elseif( $this->invoice->status == 2 ) {
			$html .= "<td>Report sur facture suivante</td>\n";
		}
		if( $this->invoice->paymentDate > 0 )
			$html .= "<td>" . substr($this->invoice->paymentDate, -2) . "/" . substr($this->invoice->paymentDate, 4, 2) . "/" . substr($this->invoice->paymentDate, 0, 4) . "</td>\n";
		else
			$html .= "<td></td>\n";
		$html .= "</tr>\n";
		$html .= "</tbody>\n</table>\n";
		
		$balance = InvoiceManager::getInstance()->getFamilyBalance($this->invoice->familyId);
		$html .= "Solde Famille " . $this->invoice->familyName . " : " .
				 "<strong>" . number_format($balance, 2, '.', ' ') . "€</strong>" .
				 "&nbsp;&nbsp;<small><i>(";
		if( $balance < 0 )	$html .= "débit";
		else				$html .= "crédit";
		$html .= ")</i></small>\n";
		
		// Print accounting entries ...
		$query = "SELECT `ID`, `DATE`, `ID_FAMILLE`, `ID_FACTURE`, `CREDIT`, `DEBIT`, `COMMENTAIRE`, `LOGINID` " .
				 "FROM `ecriture` " .
				 "WHERE `ID_FACTURE`='" . $this->invoice->invoiceId . "' " .
				 "ORDER BY `ID`";
		$stmt = $this->mysqli->query($query);
		
		$html .= "&nbsp;<br><br><strong>Détail des écritures:</strong>";
		$html .= "<table  class=\"table table-striped table-hover\">\n";
		$html .= "<tr><th>ID</th><th>DATE</th><th>ID_FAMILLE</th><th>ID_FACTURE</th><th>CREDIT</th><th>DEBIT</th><th>COMMENTAIRE</th><th>LOGINID</th></tr>\n";
		if( is_object($stmt) ) {
			while($res = $stmt->fetch_array(MYSQLI_NUM)) {
				$html .= "<tr>";
				$html .= "<td>" . $res[0] . "</td>";
				$d = intval($res[1]);
				$html .= "<td>" . substr($d, -2) . "/" . substr($d, 4, 2) . "/" . substr($d, 0, 4) . "</td>";
				$html .= "<td>" . $res[2] . "</td>";
				$html .= "<td>" . $res[3] . "</td>";
				$html .= "<td>" . $res[4] . "</td>";
				$html .= "<td>" . $res[5] . "</td>";
				$html .= "<td>" . $res[6] . "</td>";
				$html .= "<td>" . $res[7] . "</td>";
				$html .= "</tr>";
			}
			$stmt->close();
		}
		$html .= "</table>\n";
		
		echo $html;
	}
	
	
	/**************************************************************************
	 * Private Functions - Database access functions
	 **************************************************************************/
	private function loadInvoice() {
		$query = "SELECT `TITRE`, `ID_FAMILLE`, `NOM_FAMILLE`, `QF_FAMILLE`, `PRIX_UNITE`, `DATE_CREATION`, `DATE_PAIEMENT`, `ID_PERIODE`, " .
				 "`DATE_DEB`, `DATE_FIN`, `REMISE`, `MONTANT`, `SOLDE_FAMILLE`, `MONTANT_A_PAYER`, `MONTANT_REGLE`, `STATUT` " .
				 "FROM `facture` " .
				 "WHERE `ID`=" . $this->invoiceId;
		$stmt = $this->mysqli->query($query);
		if( is_object($stmt) ) {
			if($res = $stmt->fetch_array(MYSQLI_NUM)) {
				$this->invoice 					= new InvoiceEntity();
				$this->invoice->invoiceId		= $this->invoiceId;
				$this->invoice->title			= $res[0];
				
				$this->invoice->familyId		= $res[1];
				$this->invoice->familyName		= $res[2];
				$this->invoice->qf				= $res[3];
				$this->invoice->unitPrice		= $res[4];
	
				$this->invoice->createdDate		= $res[5];
				$this->invoice->paymentDate		= $res[6];
		
				$this->invoice->periodId		= $res[7];
				$this->invoice->fromDate		= $res[8];
				$this->invoice->toDate			= $res[9];
	
				$this->invoice->rebate			= $res[10];
				$this->invoice->amount			= $res[11];
				$this->invoice->accountBalance	= $res[12];
				$this->invoice->toPayAmount		= $res[13];
				$this->invoice->paidAmount		= $res[14];
				
				$this->invoice->status			= $res[15];
			}
			$stmt->close();
		}
	}
}
?>