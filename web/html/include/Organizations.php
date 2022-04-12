<?php
Class Organizations {
	# Setup
	private $basedDir = '';
	private $spoofedUserLevel = '';
		
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

	private function __construct2($userLevel) {
		$this->spoofedUserLevel = '&userLevel=' . $userLevel;
	}

	public function listOrganizations($userLevel, $orgId) {
		$userCountHandler = $this->db->prepare('SELECT COUNT(id) AS `count` FROM Users WHERE `Organizations_id` = :OrganizationsId');
		$userCountHandler->bindParam(':OrganizationsId', $userOrg);
		if ($userLevel > 1) {
			$organizationHandler = $this->db->prepare('SELECT `id`, `shortName`, `fullName` FROM Organizations');
		} else {
			$organizationHandler = $this->db->prepare('SELECT `id`, `shortName`, `fullName` FROM Organizations WHERE `id` = :OrganizationsId');
			$organizationHandler->bindParam(':OrganizationsId', $orgId);
		}
		$organizationHandler->execute();

		printf('    <table class="table table-striped table-bordered">%s      <tr><th>Organisation</th><th>FullName</th><td>Users in org</td><tr>%s', "\n", "\n");

		while ($organization = $organizationHandler->fetch(PDO::FETCH_ASSOC)) {
			$userOrg = $organization['id'];
			$userCountHandler->execute();
			$count = ($user = $userCountHandler->fetch(PDO::FETCH_ASSOC)) ? $user['count'] : 0;
			printf('      <tr><td><a href="?action=org&organizationId=%d%s">%s</a></td><td>%s</td><td>%d</td></tr>%s', $organization['id'], $this->spoofedUserLevel, $organization['shortName'], utf8_decode($organization['fullName']), $count, "\n");
		}
		printf('    </table>%s', "\n");
		if ($userLevel > 4)
			printf('    <a href=".?action=addOrg%s"><button type="button" class="btn btn-info">Add new Organisation</button></a>%s', $this->spoofedUserLevel, "\n");
	}

	public function showOrganization($userLevel, $orgId, $remove = false) {
		$organizationHandler = $this->db->prepare('SELECT `id`, `shortName`, `fullName` FROM Organizations WHERE `id` = :OrgId');
		$userHandler = $this->db->prepare('SELECT `id`, `userName`, `fullName`, `EPPN`, `email`, `sshEnabled`, `accessLevel`, `lastChanged`, `expireDate` FROM Users WHERE `Organizations_id` = :OrgId');
		$logHandler = $this->db->prepare('SELECT `message`, `updater`, `changed` FROM HistoryLog WHERE `id` = :OrgId AND `type` = :Type ORDER BY changed DESC');
		$organizationHandler->bindParam(':OrgId', $orgId);
		$userHandler->bindParam(':OrgId', $orgId);
		$logHandler->bindParam(':OrgId', $orgId);
		$logHandler->bindValue(':Type', 'Org');


		$organizationHandler->execute();

		printf('    <table class="table table-striped table-bordered">%s', "\n");
		if ($organization = $organizationHandler->fetch(PDO::FETCH_ASSOC)) {
			printf ('        <tr><th>ShortName</th><td>%s</td></tr>%s', $organization['shortName'], "\n");
			printf ('        <tr><th>FullName</th><td>%s</td></tr>%s', utf8_decode($organization['fullName']), "\n");
		}
		
		$usersFound = false;
		$userHandler->execute();
		while ($user = $userHandler->fetch(PDO::FETCH_ASSOC)) {
			if (! $usersFound ) {
				$usersFound = true;
				printf('    </table>%s    <h4>Users</h4>%s    <table class="table table-striped table-bordered">%s      <tr><th>UserName</th><th>FullName</th><th>ePPN</th><th>E-mail</th><th>SSH-enabled</th><th>Last updated</th><th>Expire date</th><tr>%s', "\n", "\n", "\n", "\n");
			}
			$sshAccess = ($user['sshEnabled']) ? 'Yes' : 'No';
			printf('      <tr><td><a href="?action=user&userId=%d%s">%s</a></td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>%s', $user['id'], $this->spoofedUserLevel, $user['userName'], utf8_decode($user['fullName']), $user['EPPN'], $user['email'], $sshAccess, $user['lastChanged'], $user['expireDate'], "\n");
		}
		if ($userLevel > 4) {
			if ($remove) {
				printf('    </table>%s    <form method="POST"><input class="btn btn-danger" type="submit" name="confirm" value="Yes Remove!"></form>%s', "\n", "\n");
			} else {
				printf('    </table>%s    <a href=".?action=editOrg&orgId=%d%s"><button class="btn btn-info" type="button" class="btn btn-primary">Edit Organisation</button></a>%s', "\n", $orgId, $this->spoofedUserLevel, "\n");
				if (! $usersFound) {
					printf('    <a href=".?action=removeOrg&orgId=%d%s"><button type="button" class="btn btn-danger">Remove Organisation</button></a>%s', $orgId, $this->spoofedUserLevel, "\n");
				}
			}
		}
		$logHandler->execute();
		printf ('    <h3></h3>%s    <table class="table table-striped table-bordered">%s      <tr><th>Date</th><th>Action</th><th>Done by</th></tr>%s', "\n", "\n", "\n");
		while($userLog = $logHandler->fetch(PDO::FETCH_ASSOC)) {
			printf('      <tr><td>%s UTC</td><td>%s</td><td>%s</td></tr>%s', $userLog['changed'], utf8_decode($userLog['message']), utf8_decode($userLog['updater']), "\n");
		}
		printf ('    </table>%s', "\n");
	}

	public function showEditOrganization($orgId) {
		$orgName = '';
		$fullName = '';
		if (isset($_POST['orgName']) && isset($_POST['fullName'])) {
			$orgName = $_POST['orgName'];
			$fullName = $_POST['fullName'];
		} else {
			$organizationHandler = $this->db->prepare('SELECT `id`, `shortName`, `fullName` FROM Organizations WHERE `id` = :OrganizationsId');
			$organizationHandler->bindParam(':OrganizationsId', $orgId);
			$organizationHandler->execute();
			if ($organization = $organizationHandler->fetch(PDO::FETCH_ASSOC)) {
				$orgName = $organization['shortName'];
				$fullName = utf8_decode($organization['fullName']);
			}
		} ?>
    <form method="POST">
      <table class="table table-striped table-bordered">
        <tr><th>Organisation</th><td><input type="text" name="orgName" size="50" value="<?=$orgName?>"></td></tr>
        <tr><th>FullName</th><td><input type="text" name="fullName" size="256" value="<?=$fullName?>"></td></tr>
      </table>
	  <input type="hidden" name="orgId" value="<?=$orgId?>">
      <input type="submit" name="save" value="Save">	
    </form> <?php
		print "\n";
	}

	public function saveOrganization($orgId) {
		$orgName = isset($_POST['orgName']) ? $_POST['orgName'] : '';
		$orgFullName = isset($_POST['fullName']) ? utf8_encode($_POST['fullName']) : '';
		if ($orgId == 0) {
			$addHandler = $this->db->prepare('INSERT INTO Organizations (`shortName`, `fullName`) VALUES ( :ShortName, :FullName)');
			$addHandler->bindValue(':ShortName', $orgName);
			$addHandler->bindValue(':FullName', $orgFullName);
			if ($addHandler->execute()) {
				return $this->db->lastInsertId();
			}
		} else {
			$updateHandler = $this->db->prepare('UPDATE Organizations SET `shortName` = :ShortName, `fullName` = :FullName WHERE id = :OrgId');
			$updateHandler->bindValue(':ShortName', $orgName);
			$updateHandler->bindValue(':FullName', $orgFullName);
			$updateHandler->bindValue(':OrgId', $orgId);
			$updateHandler->execute();
			return ($updateHandler->rowCount() > 0);
		}
	}

	public function removeOrganization($orgId){
		$organizationHandler = $this->db->prepare('SELECT `shortName` FROM Organizations WHERE `id` = :OrgId');
		$organizationHandler->bindParam(':OrgId', $orgId);
		$organizationHandler->execute();
		if ($organization = $organizationHandler->fetch(PDO::FETCH_ASSOC)) {
			$removeHandler = $this->db->prepare('DELETE FROM Organizations WHERE id = :OrgId');
			$removeHandler->bindValue(':OrgId', $orgId);
			if ($removeHandler->execute()) {
				return $organization['shortName'];
			} else {
				return false;
			}
		} else {
			return false;
		}
	}
}