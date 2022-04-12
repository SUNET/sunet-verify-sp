<?php
Class Security {
	# Setup
	private $basedDir = '';
	private $userId = 0;
	private $userAccessLevel = 0;
	private $realUserAccessLevel = 0;
	private $organizationsId = 0;
	private $updater = '';
	private $spoofed = false;
	
	function __construct() {
		$a = func_get_args();
		$i = func_num_args();
		if (isset($a[0])) {
			$this->baseDir = array_shift($a);
			include $this->baseDir . '/config.php';
			try {
				$this->db = new PDO("mysql:host=$dbServername;dbname=$dbName", $dbUsername, $dbPassword);
				// set the PDO error mode to exception
				$this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			} catch(PDOException $e) {
				echo "Error: " . $e->getMessage();
			}
		}
		if (method_exists($this,$f='__construct'.$i)) {
			call_user_func_array(array($this,$f),$a);
		}
	}

	private function __construct4($ePPN, $mail, $fullName) {
		$userEPPNHandler = $this->db->prepare('SELECT `id`, `Organizations_id`, `userName`, `accessLevel`, `email` , `fullName` FROM Users WHERE `EPPN` = :EPPN');
		$userEPPNHandler->bindValue(':EPPN', $ePPN);
		$userEPPNHandler->execute();
		$userFound = false;
		if ($user = $userEPPNHandler->fetch(PDO::FETCH_ASSOC)) {
			$userFound = true;
		} else {
			$useremailHandler = $this->db->prepare("SELECT `id`, `Organizations_id`, `userName`, `accessLevel`, `fullName` FROM Users WHERE `email` = :Email AND `EPPN` = ''");
			$useremailHandler->bindValue(':Email', $mail);
			$useremailHandler->execute();
			if ($user = $useremailHandler->fetch(PDO::FETCH_ASSOC)) {
				$userFound = true;
				$userUpdateHandler = $this->db->prepare("UPDATE Users SET `EPPN` = :EPPN WHERE `email` = :Email AND `EPPN` = ''");
				$userUpdateHandler->bindValue(':Email', $mail);
				$userUpdateHandler->bindValue(':EPPN', $ePPN);
				$userUpdateHandler->execute();
			}
		}
		if ($userFound) {
			$this->userId = $user['id'];
			$this->userAccessLevel = $user['accessLevel'];
			$this->realUserAccessLevel = $user['accessLevel'];
			$this->organizationsId = $user['Organizations_id'];
			$this->updater = sprintf('%s (%s)', $fullName, $ePPN);

			$fullNameNoUml = $this->Unaccent($fullName);

			if ($fullNameNoUml != $user['fullName']) {
				$userUpdateHandler = $this->db->prepare('UPDATE Users SET `fullName` = :FullName WHERE id = :UserId');
				$userUpdateHandler->bindValue(':FullName', $fullNameNoUml);
				$userUpdateHandler->bindValue(':UserId', $user['id']);
				$userUpdateHandler->execute();
				if ($userUpdateHandler->rowCount() > 0) {
					$this->logChanges($user['id'], 'User', 'fullName', 'Name updated based on IdP info. From ' . $user['fullName'] . ' to ' . $fullNameNoUml);
				}

			}
		}
	}

	public function getUserId () {
		return $this->userId;
	}

	public function getUserAccessLevel () {
		return $this->userAccessLevel;
	}

	public function setUserLevel ($newLevel = 0) {
		if ($this->realUserAccessLevel > $newLevel) {
			$this->userAccessLevel = $newLevel;
			$this->spoofed = true;
			return true;
		} else {
			return false;
		}
	}

	public function getRealUserAccessLevel () {
		return $this->realUserAccessLevel;
	}

	public function getOrganizationsId () {
		return $this->organizationsId;
	}

	public function spoof() {
		return $this->spoofed;
	}

	public function logChanges($id, $type, $action, $message) {
		$userLogHandler = $this->db->prepare('INSERT INTO HistoryLog (`id`, `type`, `action`, `message`, `updater`) VALUES (:Id, :Type, :Action, :Message, :Updater)');
		$userLogHandler->bindValue(':Id', $id);
		$userLogHandler->bindValue(':Type', $type);
		$userLogHandler->bindValue(':Action', $action);
		$userLogHandler->bindValue(':Message', utf8_encode($message));
		$userLogHandler->bindValue(':Updater', utf8_encode($this->updater));
		$userLogHandler->execute();
	}

	private function Unaccent($string) {
		if (strpos($string = htmlentities($string, ENT_QUOTES, 'UTF-8'), '&') !== false) {
			$string = html_entity_decode(preg_replace('~&([a-z]{1,2})(?:acute|cedil|circ|grave|lig|orn|ring|slash|tilde|uml);~i', '$1', $string), ENT_QUOTES, 'UTF-8');
		}
		return $string;
	}
}