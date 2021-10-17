<?php
require_once('../services/Database.php');
require_once('../services/InvoiceControler.php');
require_once('../services/InvoiceManager.php');

class InvoicePeriodControler {
	
	/**************************************************************************
	 * Attributes
	 **************************************************************************/
	private $db;							// Database instance
	private $mysqli;						// Database connection
	
	public  $edit				= false;	// type: boolean
	public  $add				= false;	// type: boolean
	public  $detail				= false;	// type: boolean
	public  $close				= false;	// type: boolean
	public  $currentPeriodId	= 0;		// type: int
	public  $periodId			= 0;		// type: int
	
	public  $msg_error			= "";		// type: String

	/**************************************************************************
	 * Public Functions
	 **************************************************************************/
	
	public function initialize() {
		
		if(isset($_GET['in']) && is_numeric($_GET['in']))			$this->periodId	= intval($_GET['in']);
		if(isset($_POST['in']) && is_numeric($_POST['in']))			$this->periodId	= intval($_POST['in']);
		if(isset($_GET['edit']) && is_numeric($_GET['edit']))		$this->edit	= (intval($_GET['edit']) == 1);
		if(isset($_POST['edit']) && is_numeric($_POST['edit']))		$this->edit	= (intval($_POST['edit']) == 1);
		if(isset($_GET['add']) && is_numeric($_GET['add']))			$this->add	= (intval($_GET['add']) == 1);
		if(isset($_POST['add']) && is_numeric($_POST['add']))		$this->add	= (intval($_POST['add']) == 1);
		if(isset($_GET['detail']) && is_numeric($_GET['detail']))	$this->detail	= (intval($_GET['detail']) == 1);
		if(isset($_POST['detail']) && is_numeric($_POST['detail']))	$this->detail	= (intval($_POST['detail']) == 1);
		if(isset($_GET['close']) && is_numeric($_GET['close']))		$this->close	= (intval($_GET['close']) == 1);
		if(isset($_POST['close']) && is_numeric($_POST['close']))	$this->close	= (intval($_POST['close']) == 1);
		
		// Initialize database connection
		$this->db = Database::getInstance();
		$this->mysqli = $this->db->getConnection();
		
		// Load current invoicing period
		$this->loadCurrentInvoicePeriod();
	}
	
	public function build_period_view() {
		$html  = "";
		$invPeriods 	= $this->getInvoicePeriods();
		$clsLst			= array(0=>"closed", 1=>"current", 2=>"planned");
		$statusLabel	= array(0=>"Clos", 1=>"En cours", 2=>"-");
		$lineNb			= 1;
		
		foreach( $invPeriods as $period ) {
			$html .= "<tr class=\"" . $clsLst[intval($period->status)] . "\">";			
			$html .= "<td>" . $lineNb++ . "</td>\n";
			$html .= "<td><a href='?detail=1&in=" . $period->id . "' title=\"Liste des Factures\">" . $period->title . "</a></td>\n";
			$html .= "<td>" . substr($period->fromNoJour, -2) . "/" . substr($period->fromNoJour, 4, 2) . "/" . substr($period->fromNoJour, 0, 4) . "</td>\n";
			$html .= "<td>" . substr($period->toNoJour, -2) . "/" . substr($period->toNoJour, 4, 2) . "/" . substr($period->toNoJour, 0, 4) . "</td>\n";
			$html .= "<td>" . $period->invoiceNumber . "</td>\n";
			$html .= "<td>" . $period->paidInvoiceNb . "</td>\n";
			$html .= "<td>" . number_format($period->amount, 2, ',', ' ') . "</td>\n";
			$html .= "<td>" . number_format($period->paidAmount, 2, ',', ' ') . "</td>\n";
			$html .= "<td>" . $statusLabel[$period->status] . "</td>\n";
			
			if( $period->status == 1 )
				$html .= "<td><button type=\"button\" class=\"btn btn-sm btn-primary\" onclick=\"$('#confirmationModal').modal('show');\">Clôturer</button></td>\n";
			else
				$html .= "<td></td>\n";
			
			if( $period->status == 1 || $period->status == 2 )
				$html .= "<td><a class=\"ls-modal\" href=\"../inc/doEditInvoicePeriod.php?in=" . $period->id . "&edit=1\"><i class=\"fa fa-edit\" data-toggle=\"tooltip\" title=\"Edit\"></i></a></td>\n";
			else
				$html .= "<td></td>\n";
			
			$html .= "</tr>\n";
		}

		echo $html;
	}
	
	public function build_list_invoice_view($activePer) {
		$obj = new InvoiceControler();
		$obj->initialize();
		$obj->loadAllInvoices($this->periodId, $activePer);
		$this->msg_error = $obj->msg_error;
		
		if( !$activePer )
			return $obj->build_ListInvoicesTable(false);
		else
			return $obj->build_ListInvoicesSimulationTable();
	}

	// Parse request
	public function parse_request() {
		
		// Demande de clotûre de la période
		if( $this->close ) {
			$this->closeInvoicePeriod($this->periodId);
			return;
		}
		
		// Mise à jour de la période de facturation
		if( !isset($_POST['submit']) ) return;
		if( !isset($_POST['in']) || !is_numeric($_POST['in']) ) return;
		if( !isset($_POST['title']) ) return;
		if( !isset($_POST['fromNoJour']) || !is_numeric($_POST['fromNoJour']) ) return;
		if( !isset($_POST['toNoJour']) || !is_numeric($_POST['toNoJour']) ) return;
		
		$title			= $_POST['title'];
		$fromNoJour		= intval($_POST['fromNoJour']);
		$toNoJour		= intval($_POST['toNoJour']);
		
		$from	= DateTime::createFromFormat('Ymd', $fromNoJour);
		if( $from == false ) return;
		
		$to	= DateTime::createFromFormat('Ymd', $toNoJour);
		if( $to == false ) return;
		
		if( $this->edit ) {
			// Contrôle de chevauchement avec autres période
			if( $this->_isIntersectionWithAnotherPeriod($fromNoJour, $this->periodId) ) {
				$this->msg_error = "Date de début en chevauchement avec une autre période. Mise à jour impossible !";
				return;
			}
			if( $this->_isIntersectionWithAnotherPeriod($toNoJour, $this->periodId) ) {
				$this->msg_error = "Date de fin en chevauchement avec une autre période. Mise à jour impossible !";
				return;
			}	
			// Enregistrement de la mise à jour
			$this->updatePeriod($title, $fromNoJour, $toNoJour);
		
		} else if( $this->add ) {
			// Contrôle de chevauchement avec autres période
			if( $this->_isIntersectionWithAnotherPeriod($fromNoJour, -1) ) {
				$this->msg_error = "Date de début en chevauchement avec une autre période. Création impossible !";
				return;
			}
			if( $this->_isIntersectionWithAnotherPeriod($toNoJour, -1) ) {
				$this->msg_error = "Date de fin en chevauchement avec une autre période. Création impossible !";
				return;
			}	
			// Enregistrement de la création
			$this->insertPeriod($title, $fromNoJour, $toNoJour);
		}
		
		$this->edit = false;
		$this->add = false;
	}
	
	private function closeInvoicePeriod($periodId) {
		InvoiceManager::getInstance()->closeInvoicePeriod();
		$this->msg_error = InvoiceManager::getInstance()->msg_error;
		if( $this->msg_error != "")
			$this->msg_error = "!! CLOTURE IMPOSSIBLE !!<br/><br/>" . $this->msg_error;
	}
	
	/**************************************************************************
	 * Private Functions - Database access functions
	 **************************************************************************/
	private function loadCurrentInvoicePeriod() {
		$query = "SELECT `ID` ".
				 "FROM `periode_facturation` " .
				 "WHERE `STATUT`=1 LIMIT 1";
		$stmt= $this->mysqli->query($query);
		if (is_object($stmt)) {
			if($res = $stmt->fetch_array(MYSQLI_NUM))
				$this->currentPeriodId = $res[0];
			$stmt->close();
		}
	}

	private function getInvoicePeriods() {
		$invPeriods = array();
		$query = "SELECT `ID`, `TITRE`, `D_DEB`, `D_FIN`, `STATUT` ".
				 "FROM `periode_facturation` " .
				 "ORDER BY `D_DEB`";
		$stmt= $this->mysqli->query($query);
		if (is_object($stmt)) {
			while($res = $stmt->fetch_array(MYSQLI_NUM)) {
				$invPer = new InvoicePeriodEntity();
				$invPer->id				= intval($res[0]);
				$invPer->title			= $res[1];
				$invPer->fromNoJour		= intval($res[2]);
				$invPer->toNoJour		= intval($res[3]);
				$invPer->status			= intval($res[4]);
				$invPer->invoiceNumber	= $this->getInvoiceNumber($invPer->id);
				$invPer->paidInvoiceNb	= $this->getPaidInvoiceNumber($invPer->id);
				$invPer->amount			= $this->getInvoicePeriodAmount($invPer->id);
				$invPer->paidAmount		= $this->getInvoicePeriodPaidAmount($invPer->id);
				array_push($invPeriods, $invPer);
			}
			$stmt->close();
		}
		return $invPeriods;
	}
	
	public function getInvoicePeriod($periodId) {
		$invPer = null;
		$query = "SELECT `ID`, `TITRE`, `D_DEB`, `D_FIN`, `STATUT` ".
				 "FROM `periode_facturation` " .
				 "WHERE `ID`=" . $periodId ;
		$stmt= $this->mysqli->query($query);
		if (is_object($stmt)) {
			if($res = $stmt->fetch_array(MYSQLI_NUM)) {
				$invPer = new InvoicePeriodEntity();
				$invPer->id				= intval($res[0]);
				$invPer->title			= $res[1];
				$invPer->fromNoJour		= intval($res[2]);
				$invPer->toNoJour		= intval($res[3]);
				$invPer->status			= intval($res[4]);
			}
			$stmt->close();
		}
		return $invPer;
	}
	
	private function getInvoiceNumber($invPeriodId) {
		$ret = 0;
		$query = "SELECT COUNT(*) ".
				 "FROM `facture` " .
				 "WHERE `ID_PERIODE`=" . $invPeriodId;
		$stmt= $this->mysqli->query($query);
		if (is_object($stmt)) {
			if($res = $stmt->fetch_array(MYSQLI_NUM)) $ret = intval($res[0]);
			$stmt->close();
		}
		return $ret;
	}
	
	private function getPaidInvoiceNumber($invPeriodId) {
		$ret = 0;
		$query = "SELECT COUNT(*) ".
				 "FROM `facture` " .
				 "WHERE `MONTANT_REGLE` >= `MONTANT` AND `ID_PERIODE`=" . $invPeriodId ;
		$stmt= $this->mysqli->query($query);
		if (is_object($stmt)) {
			if($res = $stmt->fetch_array(MYSQLI_NUM))
				$ret = intval($res[0]);
			$stmt->close();
		}
		return $ret;
	}
	
	private function getInvoicePeriodAmount($invPeriodId) {
		$ret = 0.0;
		$query = "SELECT SUM(`MONTANT`) ".
				 "FROM `facture` " .
				 "WHERE `ID_PERIODE`=" . $invPeriodId ;
		$stmt= $this->mysqli->query($query);
		if (is_object($stmt)) {
			if($res = $stmt->fetch_array(MYSQLI_NUM))
				$ret = floatval($res[0]);
			$stmt->close();
		}
		return $ret;
	}

	private function getInvoicePeriodPaidAmount($invPeriodId) {
		$ret = 0;
		$query = "SELECT SUM(`MONTANT_REGLE`) ".
				 "FROM `facture` " .
				 "WHERE `ID_PERIODE`=" . $invPeriodId ;
		$stmt= $this->mysqli->query($query);
		if (is_object($stmt)) {
			if($res = $stmt->fetch_array(MYSQLI_NUM))
				$ret = floatval($res[0]);
			$stmt->close();
		}
		return $ret;
	}
	
	public function isActiveInvoicePeriod($invPeriodId) {
		$ret = false;
		$query = "SELECT `STATUT` ".
				 "FROM `periode_facturation` " .
				 "WHERE `ID`=" . $invPeriodId ;
		$stmt= $this->mysqli->query($query);
		if (is_object($stmt)) {
			if($res = $stmt->fetch_array(MYSQLI_NUM))
				$ret = (intval($res[0]) == 1);
			$stmt->close();
		}
		return $ret;
	}
	
	private function _isIntersectionWithAnotherPeriod($dt, $invPeriodId) {
		$ret = false;
		$query = "SELECT * FROM `periode_facturation` "	.
				 "WHERE `ID`<>"			. $invPeriodId	. " AND " .
					   "`D_DEB`<="		. $dt			. " AND " .
					   "`D_FIN`>="		. $dt			. " ";
		$stmt= $this->mysqli->query($query);
		if (is_object($stmt)) {
			if($res = $stmt->fetch_array(MYSQLI_NUM))
				$ret = true;
			$stmt->close();
		}
		return $ret;
	}
	
	private function updatePeriod($title, $fromNoJour, $toNoJour) {
		$query = "UPDATE `periode_facturation` SET "	.
					"`TITRE`='"		. $title			. "', " .
					"`D_DEB`="		. $fromNoJour		. ", " .
					"`D_FIN`="		. $toNoJour			. " " .
				 "WHERE `ID`="		. $this->periodId	. " " .
				 "AND (`STATUT`=1 OR `STATUT`=2)";
		$this->mysqli->query($query);
		LogManager::logSQLRequest(__CLASS__ , __FUNCTION__ , $query);
	}
	
	private function insertPeriod($title, $fromNoJour, $toNoJour) {
		$query = "INSERT INTO `periode_facturation` (`TITRE`, `D_DEB`, `D_FIN`, `STATUT`) VALUES(" .
				 "'" . DBUtils::toString($title) . "', '" . $fromNoJour . "', '" . $toNoJour . "', '2')";
		$this->mysqli->query($query);
		LogManager::logSQLRequest(__CLASS__ , __FUNCTION__ , $query);
	}
}

/**************************************************************************
 * InvoicePeriodEntity
 **************************************************************************/
class InvoicePeriodEntity {
	
	public $id				= 0;
	public $title			= "";
	public $fromNoJour		= 0;
	public $toNoJour		= 0;
	public $status			= 0;
	public $invoiceNumber	= 0;
	public $paidInvoiceNb	= 0;
	public $amount			= 0.0;
	public $paidAmount		= 0.0;
}
?>