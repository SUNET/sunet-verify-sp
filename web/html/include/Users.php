<?php
Class Users {
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
				$this->db = new PDO("mysql:host=$dbServername;dbname=$dbName;charset=utf8", $dbUsername, $dbPassword);
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

	private function __construct1() {	
	}

	private function __construct2($userLevel) {
		$this->spoofedUserLevel = '&userLevel=' . $userLevel;	
	}

	public function listUsers($userLevel, $orgID) {
		if ($userLevel > 1) {
			$userHandler = $this->db->prepare('SELECT Users.`id`, `userName`, Users.`fullName`, `EPPN`, `email`, `sshEnabled`, `accessLevel`, `shortName`, `expireDate` FROM Users, Organizations WHERE Organizations_id = Organizations.id ORDER BY accessLevel, `userName`');
		} else {
			$userHandler = $this->db->prepare('SELECT Users.`id`, `userName`, Users.`fullName`, `EPPN`, `email`, `sshEnabled`, `accessLevel`, `shortName`, `expireDate` FROM Users, Organizations WHERE Organizations_id = Organizations.id AND `Organizations_id` = :OrganizationsId ORDER BY `userName`');
			$userHandler->bindParam(':OrganizationsId', $orgID);
		} 
		$userHandler->execute();

		printf('    <table class="table table-striped table-bordered">%s      <tr><th>UserName</th><th>FullName</th><th>ePPN</th><th>E-mail</th><th>SSH-enabled</th><th>Organisation</th><th>Expiredate</th><tr>%s', "\n", "\n");

		while ($user = $userHandler->fetch(PDO::FETCH_ASSOC)) {
			printf('      <tr><td><a href="?action=user&userId=%d%s">%s</a></td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>%s', $user['id'], $this->spoofedUserLevel, $user['userName'], utf8_decode($user['fullName']), $user['EPPN'], $user['email'], $user['sshEnabled'] ? 'Yes' : 'No', $user['shortName'], $user['expireDate'], "\n");
		}
		print "    </table>\n" ;
		if ($userLevel > 4)
			printf('    <a href=".?action=addUser%s"><button type="button" class="btn btn-info">Add new User</button></a>%s', $this->spoofedUserLevel, "\n");

	}

	public function showUser($userLevel, $userId, $edit = false, $updateKey = false, $remove = false) {
		$userHandler = $this->db->prepare('SELECT `userName`, Users.`fullName`, `EPPN`, `email`, `sshKey`, `sshEnabled`, `accessLevel`, `shortName`, `lastChanged`, `expireDate` FROM Users, Organizations WHERE Organizations_id = Organizations.id AND Users.`id` = :UserId');
		$writeAccessHandler = $this->db->prepare('SELECT `group` FROM UserWrite, Groups WHERE `Users_id` = :UserId AND Groups.`id` = `Groups_id` ORDER BY `group`');
		$readAccessHandler = $this->db->prepare('SELECT `group` FROM UserRead, Groups WHERE `Users_id` = :UserId AND Groups.`id` = `Groups_id` ORDER BY `group`');
		$firewallAccessHandler = $this->db->prepare('SELECT `group` FROM UserFirewall, Groups WHERE `Users_id` = :UserId AND Groups.`id` = `Groups_id` ORDER BY `group`');
		$logHandler = $this->db->prepare('SELECT `message`, `updater`, `changed` FROM HistoryLog WHERE `id` = :UserId AND `type` = :Type ORDER BY changed DESC');
		$userHandler->bindParam(':UserId', $userId);
		$writeAccessHandler->bindParam(':UserId', $userId);
		$readAccessHandler->bindParam(':UserId', $userId);
		$firewallAccessHandler->bindParam(':UserId', $userId);
		$logHandler->bindParam(':UserId', $userId);
		$logHandler->bindValue(':Type', 'User');
		$userHandler->execute();

	
		if ($user = $userHandler->fetch(PDO::FETCH_ASSOC)) { ?>
    <form method="POST">
      <table class="table table-striped table-bordered">
        <tr><th>UserName</th><td><?=$user['userName']?></td></tr>
        <tr><th>FullName</th><td><?=utf8_decode($user['fullName'])?></td></tr>
        <tr><th>ePPN</th><td><?=$user['EPPN']?></td></tr>
        <tr><th>E-mail</th><td><?=$user['email']?></td></tr>
        <tr><th>Organisation</th><td><?=$user['shortName']?></td></tr>
        <tr><th>SSH-key</th><td><?=$updateKey ? printf ('<textarea id="ssh-key" name="ssh-key" rows="6" cols="120">%s</textarea>', $user['sshKey']) : $this->printSshKey($user['sshKey']) ?></td></tr>
        <tr><th>SSH-enabled</th><td><?=$user['sshEnabled'] ? 'Yes' : 'No'?></td></tr>
        <tr><th>Expiredate</th><td><?=$user['expireDate']?></td></tr>
        <tr><th>Last changed</th><td><?=$user['lastChanged']?> UTC</td></tr><?php
			print "\n";
			if ($userLevel > 2) {
				$writeAccessHandler->execute();
				if ($group = $writeAccessHandler->fetch(PDO::FETCH_ASSOC)) {
					printf('        <tr><th>Write access</th>%s          <td><ul>%s            <li>%s</li>%s', "\n", "\n", $group['group'], "\n");
					while ($group = $writeAccessHandler->fetch(PDO::FETCH_ASSOC)) {
						printf('            <li>%s</li>%s', $group['group'], "\n");
					}
					print "          </ul></td>\n        </tr>\n";
				}
				$readAccessHandler->execute();
				if ($group = $readAccessHandler->fetch(PDO::FETCH_ASSOC)) {
					printf('        <tr><th>Read access</th>%s          <td><ul>%s            <li>%s</li>%s', "\n", "\n", $group['group'], "\n");
					while ($group = $readAccessHandler->fetch(PDO::FETCH_ASSOC)) {
						printf('            <li>%s</li>%s', $group['group'], "\n");
					}
					print "          </ul></td>\n        </tr>\n";
				}
				$firewallAccessHandler->execute();
				if ($group = $firewallAccessHandler->fetch(PDO::FETCH_ASSOC)) {
					printf('        <tr><th>Firewall access</th>%s          <td><ul>%s            <li>%s</li>%s', "\n", "\n", $group['group'], "\n");
					while ($group = $firewallAccessHandler->fetch(PDO::FETCH_ASSOC)) {
						printf('            <li>%s</li>%s', $group['group'], "\n");
					}
					print "          </ul></td>\n        </tr>\n";
				}
			}
			printf ('      </table>%s', "\n");
			if ($edit) {
				printf ('      <input type="hidden" name="userId" value="%d">%s', $userId, "\n");
				if ($updateKey) {
					printf ('      <input class="btn btn-success" type="submit" name="saveKey" value="Save ssh-key">%s      <input class="btn btn-warning" type="submit" name="cancel" value="Cancel">%s', "\n", "\n");
				} else {
					printf ('      <input class="btn btn-info" type="submit" name="verify" value="Extend Expiredate">%s      <input class="btn btn-info" type="submit" name="update" value="Update ssh-key">%s', "\n", "\n");	
					if ($userLevel > 4) {
						printf ('      <input class="btn btn-info" type="submit" name="editUser" value="Edit User">%s', "\n");
						printf ('      <a href=".?action=removeUser&userId=%d%s"><button type="button" class="btn btn-danger">Remove User</button></a>%s', $userId, $this->spoofedUserLevel, "\n");
					}
				}
	
			} elseif ($remove) {
				printf ('      <input class="btn btn-danger" type="submit" name="confirm" value="Yes Remove!"></form>%s', "\n");
			}
			printf ('    </form>%s', "\n", "\n");
			if ($userLevel > 4) {

			}
			$logHandler->execute();
			printf ('    <h3></h3>%s    <table class="table table-striped table-bordered">%s      <tr><th>Date</th><th>Action</th><th>Done by</th></tr>%s', "\n", "\n", "\n");
			while($userLog = $logHandler->fetch(PDO::FETCH_ASSOC)) {
				printf('      <tr><td>%s UTC</td><td>%s</td><td>%s</td></tr>%s', $userLog['changed'], utf8_decode($userLog['message']), utf8_decode($userLog['updater']), "\n");
			}
			printf ('    </table>%s', "\n");
		}
	}

	public function showEditUser($userId) {
		$userName = '';
		$fullName = '';
		$EPPN = '';
		$email = '';
		$sshEnabled = '';
		$accessLevel = 0;
		$orgId = 0;

		if (isset($_POST['userName']) && isset($_POST['fullName'])) {
			$userName = $_POST['userName'];
			$fullName = $_POST['fullName'];
			$EPPN = $_POST['EPPN'];
			$email = $_POST['email'];
			$sshEnabled = isset($_POST['sshEnabled']);
			$accessLevel = $_POST['accessLevel'];
			$orgId = $_POST['orgId'];
		} else {
			$userHandler = $this->db->prepare('SELECT `userName`, `fullName`, `EPPN`, `email`, `sshEnabled`, `accessLevel`, `Organizations_id` FROM Users WHERE Users.`id` = :UserId');
			$userHandler->bindParam(':UserId', $userId);
			$userHandler->execute();
			if ($user = $userHandler->fetch(PDO::FETCH_ASSOC)) {
				$userName = $user['userName'];
				$fullName = $user['fullName'];
				$EPPN = $user['EPPN'];
				$email = $user['email'];
				$sshEnabled = $user['sshEnabled'];
				$accessLevel = $user['accessLevel'];
				$orgId = $user['Organizations_id'];
			}
		} ?>
    <form method="POST">
      <table class="table table-striped table-bordered">
        <tr><th>UserName</th><td><input type="text" name="userName" size="50" value="<?=$userName?>"></td></tr>
        <tr><th>FullName</th><td><input type="text" name="fullName" size="50" value="<?=$fullName?>"></td></tr>
        <tr><th>ePPN</th><td><input type="text" name="EPPN" size="50" value="<?=$EPPN?>"></td></tr>
        <tr><th>E-mail</th><td><input type="text" name="email" size="50" value="<?=$email?>"></td></tr>
        <tr><th>Organisation</th>
          <td><select name="orgId"><?=$this->showOrgList($orgId)?> 
          </select></td>
        </tr>
        <tr><th>Access level</th>
          <td><select name="accessLevel">
            <option value="1"<?=$accessLevel == 1 ? ' selected' : ''?>>User</option>
            <option value="3"<?=$accessLevel == 3 ? ' selected' : ''?>>NOC</option>
            <option value="7"<?=$accessLevel == 7 ? ' selected' : ''?>>NOC - Admin</option>
          </select></td>
        </tr>
        <tr><th>SSH-enabled</th><td><input type="checkbox" name="sshEnabled" value="enabled"<?=$sshEnabled ? ' checked' : ''?>></td></tr>
        <tr><th>Write access</th>
          <td><?=$this->showGroupList($userId, 'write')?> 
          </td>
        </tr>
        <tr><th>Read access</th>
          <td><?=$this->showGroupList($userId, 'read')?> 
          </td>
        </tr>
        <tr><th>Firewall access</th>
          <td><?=$this->showGroupList($userId, 'firewall')?> 
          </td>
        </tr>
      </table>
      <input type="hidden" name="userId" value="<?=$userId?>">
      <input class="btn btn-success" type="submit" name="save" value="Save">	
    </form> <?php
		print "\n";
	}

	public function saveUser($userId) {
		$userName = $_POST['userName'];
		$fullName = utf8_encode($_POST['fullName']);
		$EPPN = $_POST['EPPN'];
		$email = $_POST['email'];
		$sshEnabled = isset($_POST['sshEnabled']) ? 1 : 0;
		$accessLevel = $_POST['accessLevel'];
		$orgId = $_POST['orgId'];
		$readGroup = isset($_POST['readGroup']) ? $_POST['readGroup'] : array();
		$firewallGroup = isset($_POST['firewallGroup']) ? $_POST['firewallGroup'] : array();
		$writeGroup = isset($_POST['writeGroup']) ? $_POST['writeGroup'] : array();
		if ($userId == 0) {
			$addHandler = $this->db->prepare('INSERT INTO Users (`Organizations_id`, `userName`, `fullName`, `EPPN`, `email`, `sshEnabled`, `accessLevel`) VALUES (:OrgId, :UserName, :FullName, :EPPN, :Email, :SshEnabled, :AccessLevel)');
			$addHandler->bindValue(':OrgId', $orgId);
			$addHandler->bindValue(':UserName', $userName);
			$addHandler->bindValue(':FullName', $fullName);
			$addHandler->bindValue(':EPPN', $EPPN);
			$addHandler->bindValue(':Email', $email);
			$addHandler->bindValue(':SshEnabled', $sshEnabled);
			$addHandler->bindValue(':AccessLevel', $accessLevel);
			if ($addHandler->execute()) {
				$userId =  $this->db->lastInsertId();
				$this->saveGroups($userId, $readGroup, 'read');
				$this->saveGroups($userId, $writeGroup, 'write');
				$this->saveGroups($userId, $firewallGroup, 'firewall');
				return $userId;
			}
		} else {
			$updateHandler = $this->db->prepare('UPDATE Users SET `Organizations_id` = :OrgId, `userName` = :UserName, `fullName` = :FullName, `EPPN` = :EPPN, `email` = :Email, `sshEnabled` = :SshEnabled, `accessLevel` =  :AccessLevel	WHERE id = :UserId');
			$updateHandler->bindValue(':OrgId', $orgId);
			$updateHandler->bindValue(':UserName', $userName);
			$updateHandler->bindValue(':FullName', $fullName);
			$updateHandler->bindValue(':EPPN', $EPPN);
			$updateHandler->bindValue(':Email', $email);
			$updateHandler->bindValue(':SshEnabled', $sshEnabled);
			$updateHandler->bindValue(':AccessLevel', $accessLevel);
			$updateHandler->bindValue(':UserId', $userId);
			$updateHandler->execute();
			if ($updateHandler->rowCount() > 0 || $this->saveGroups($userId, $readGroup, 'read') || $this->saveGroups($userId, $writeGroup, 'write') || $this->saveGroups($userId, $writeGroup, 'write')) {
				return $userId;
			} else {
				return false;
			}
		}
	}

	public function removeUser($userId){
		$userHandler = $this->db->prepare('SELECT `userName` FROM Users WHERE `id` = :UserId');
		$userHandler->bindParam(':UserId', $userId);
		$userHandler->execute();
		if ($user = $userHandler->fetch(PDO::FETCH_ASSOC)) {
			$removeHandler = $this->db->prepare('DELETE FROM Users WHERE id = :UserId');
			$removeFirewallHandler = $this->db->prepare('DELETE FROM UserFirewall WHERE `Users_id` = :UserId');
			$removeReadHandler = $this->db->prepare('DELETE FROM UserRead WHERE `Users_id` = :UserId');
			$removeWriteHandler = $this->db->prepare('DELETE FROM UserWrite WHERE `Users_id` = :UserId');
			$removeHandler->bindValue(':UserId', $userId);
			$removeFirewallHandler->bindValue(':UserId', $userId);
			$removeReadHandler->bindValue(':UserId', $userId);
			$removeWriteHandler->bindValue(':UserId', $userId);
			if ($removeHandler->execute()) {
				$removeFirewallHandler->execute();
				$removeReadHandler->execute();
				$removeWriteHandler->execute();
				return $user['userName'];
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	private function showOrgList($orgId) {
		$organizationHandler = $this->db->prepare('SELECT `id`, `shortName`, `fullName` FROM Organizations');
		$organizationHandler->execute();
		while ($organization = $organizationHandler->fetch(PDO::FETCH_ASSOC)) {
			printf('%s            <option value="%d"%s>%s</option>', "\n", $organization['id'], $organization['id'] == $orgId ? ' selected' : '', $organization['shortName']);
		}
	}

	private function showAccessLevelList($accessLevel) {
		printf('%s            <option value="1"%s>%User</option>', "\n", $accessLevel == 1 ? ' selected' : '');
		printf('%s            <option value="3"%s>%NOC</option>', "\n", $accessLevel == 3 ? ' selected' : '');
		printf('%s            <option value="7"%s>%NOC - Admin</option>', "\n", $accessLevel == 7 ? ' selected' : '');
	}

	private function showGroupList($userId, $type) {
		switch ($type) {
			case 'firewall' :
				$accessHandler = $this->db->prepare('SELECT `Groups_id` FROM UserFirewall WHERE `Users_id` = :UserId');
				break;
			case 'read' :
				$accessHandler = $this->db->prepare('SELECT `Groups_id` FROM UserRead WHERE `Users_id` = :UserId');
				break;
			case 'write' :
				$accessHandler = $this->db->prepare('SELECT `Groups_id` FROM UserWrite WHERE `Users_id` = :UserId');
				break;
			default:
				return;
		}
		$accessArray = array();
		$accessHandler->bindParam(':UserId', $userId);
		$accessHandler->execute();
		while ($access = $accessHandler->fetch(PDO::FETCH_ASSOC)) {
			$accessArray[$access['Groups_id']] = true;
		}

		$groupHandler = $this->db->prepare('SELECT `id`, `group` FROM Groups ORDER BY `group`');
		$groupHandler->execute();
		while ($group = $groupHandler->fetch(PDO::FETCH_ASSOC)) {
			printf ('%s            <input type="checkbox" name="%sGroup[]" value="%d"%s> %s<br>', "\n", $type, $group['id'], isset($accessArray[$group['id']]) ? ' checked' : '', $group['group']);
		}
	}

	private function saveGroups($userId, $groups, $type) {
		$changed = false;
		switch ($type) {
			case 'firewall' :
				$accessHandler = $this->db->prepare('SELECT `Groups_id` FROM UserFirewall WHERE `Users_id` = :UserId');
				$removeHandler = $this->db->prepare('DELETE FROM UserFirewall WHERE `Users_id` = :UserId AND `Groups_id` = :GroupId');
				$addHandler = $this->db->prepare('INSERT INTO UserFirewall (`Users_id`, `Groups_id`) VALUES (:UserId, :GroupId)');
				break;
			case 'read' :
				$accessHandler = $this->db->prepare('SELECT `Groups_id` FROM UserRead WHERE `Users_id` = :UserId');
				$removeHandler = $this->db->prepare('DELETE FROM UserRead WHERE `Users_id` = :UserId AND `Groups_id` = :GroupId');
				$addHandler = $this->db->prepare('INSERT INTO UserRead (`Users_id`, `Groups_id`) VALUES (:UserId, :GroupId)');
				break;
			case 'write' :
				$accessHandler = $this->db->prepare('SELECT `Groups_id` FROM UserWrite WHERE `Users_id` = :UserId');
				$removeHandler = $this->db->prepare('DELETE FROM UserWrite WHERE `Users_id` = :UserId AND `Groups_id` = :GroupId');
				$addHandler = $this->db->prepare('INSERT INTO UserWrite (`Users_id`, `Groups_id`) VALUES (:UserId, :GroupId)');
				break;
			default:
				return;
		}
		$accessArray = array();
		$accessHandler->bindParam(':UserId', $userId);
		$removeHandler->bindParam(':UserId', $userId);
		$removeHandler->bindParam(':GroupId', $groupId);
		$addHandler->bindParam(':UserId', $userId);
		$addHandler->bindParam(':GroupId', $groupId);
		$removeHandler->execute();
		$accessHandler->execute();
		while ($access = $accessHandler->fetch(PDO::FETCH_ASSOC)) {
			$accessArray[$access['Groups_id']] = $access['Groups_id'];
		}
		foreach ($groups as $groupId) {
			if (isset($accessArray[$groupId])){
				unset($accessArray[$groupId]);
			} else {
				$addHandler->execute();
				$changed = true;
			}
		}
		foreach ($accessArray as $groupId) {
			$removeHandler->execute();
			$changed = true;
		}
		return $changed;
	}

	private function printSshKey($key) {
		$ret_string = '';
		foreach (explode(' ', $key) as $part) {
			$ret_string .= implode('<br>', str_split($part, 90)) . ' ';
		}
		return $ret_string;
	}

	public function saveSshKeyUser($userId, $sshKey) {
		$userUpdateHandler = $this->db->prepare('UPDATE Users SET sshKey = :Key WHERE `id` = :UserId');
		$userUpdateHandler->bindParam(':UserId', $userId);
		$userUpdateHandler->bindParam(':Key', $sshKey);
		$userUpdateHandler->execute();
		return ($userUpdateHandler->rowCount() > 0);
	}

	public function extendExpireDate($userId) {
		$paramsHandler = $this->db->prepare('SELECT `value` FROM Params WHERE `name` = :ParamName');
		$paramsHandler->bindValue('ParamName', 'expireDate');
		$paramsHandler->execute();
		if ($param = $paramsHandler->fetch(PDO::FETCH_ASSOC)) {
			$userUpdateHandler = $this->db->prepare('UPDATE Users SET `expireDate` = :ExpireDate WHERE `id` = :UserId');
			$userUpdateHandler->bindParam(':ExpireDate', $param['value']);
			$userUpdateHandler->bindParam(':UserId', $userId);
			$userUpdateHandler->execute();
			return ($userUpdateHandler->rowCount() > 0);
		}
		return false;
	}

	public function exportUsers() {
		header('Content-Type: text/plain; charset=utf-8');
		header('Content-Disposition: attachment; filename=NCS.txt');
		$userHandler = $this->db->prepare('SELECT Users.`id`, `userName`, Users.`fullName`, `EPPN`, `email`, `sshKey`, `sshEnabled`, `accessLevel`, `shortName`, `expireDate` FROM Users, Organizations WHERE Organizations_id = Organizations.id');
		$userHandler->execute();
		$writeHandler = $this->db->prepare('SELECT `group` FROM UserWrite, Groups WHERE `Users_id` = :UserId AND `Groups_id` = Groups.`id` ORDER BY `group`');
		$writeHandler->bindParam(':UserId', $userId);
		$readHandler = $this->db->prepare('SELECT `group` FROM UserRead, Groups WHERE `Users_id` = :UserId AND `Groups_id` = Groups.`id` ORDER BY `group`');
		$readHandler->bindParam(':UserId', $userId);
		$firewallHandler = $this->db->prepare('SELECT `group` FROM UserFirewall, Groups WHERE `Users_id` = :UserId AND `Groups_id` = Groups.`id` ORDER BY `group`');
		$firewallHandler->bindParam(':UserId', $userId);
		
		while ($user = $userHandler->fetch(PDO::FETCH_ASSOC)) {
			if ($user['sshEnabled']) {
				printf ('customer-users %s {%s    description        "%s, %s";%s    ssh-keys           [ "%s" ];%s', $user['userName'], "\n", utf8_decode($user['fullName']), $user['email'], "\n",  $user['sshKey'], "\n");
				$userId = $user['id'];
				$writeHandler->execute();
				if ($group = $writeHandler->fetch(PDO::FETCH_ASSOC)) {
					printf('    write-device-group [ %s', $group['group']);
					while ($group = $writeHandler->fetch(PDO::FETCH_ASSOC)) {
						printf(' %s', $group['group']);
					}
					print " ];\n";
				}
				$readHandler->execute();
				if ($group = $readHandler->fetch(PDO::FETCH_ASSOC)) {
					printf('    read-device-group  [ %s', $group['group']);
					while ($group = $readHandler->fetch(PDO::FETCH_ASSOC)) {
						printf(' %s', $group['group']);
					}
					print " ];\n";
				}
				$firewallHandler->execute();
				if ($group = $firewallHandler->fetch(PDO::FETCH_ASSOC)) {
					printf('    firewall-device-group [ %s', $group['group']);
					while ($group = $firewallHandler->fetch(PDO::FETCH_ASSOC)) {
						printf(' %s', $group['group']);
					}
					print " ];\n";
				}
				//firewall-device-group
				printf('    expire-date        %s;%s}%s', $user['expireDate'], "\n", "\n");
			}
		}
	}

	public function showAdmin() {
		$expireDate = '';
		$updateHandler = $this->db->prepare('UPDATE Params SET `value` = :Value WHERE `name` = :ParamName');
		$updateHandler->bindParam(':ParamName', $POSTKey);
		$updateHandler->bindParam(':Value', $POSTValue);
		if (isset($_POST) ){
			foreach ($_POST as $POSTKey => $POSTValue) {
				switch($POSTKey) {
					case 'expireDate' :
						$updateHandler->execute();
						break;
					case 'save' :
						break;
					default:
						printf('Unkown Pair %s -> %s', $POSTKey, $POSTValue);
				}
			}
		}

		$paramsHandler = $this->db->prepare('SELECT `name`, `value` FROM Params');
		$paramsHandler->execute();
		while ($param = $paramsHandler->fetch(PDO::FETCH_ASSOC)) {
			switch ($param['name']) {
				case 'expireDate' :
					$expireDate = $param['value'];
					break;
				default:
					printf('Unknown param : %s<br>%s', $param['name'], "\n");
			}
		} ?>
    <form method="POST">
      <a href=".?action=exportUsers"><button type="button" class="btn btn-info">Export User for NCS</button></a>
      <hr>
      <h4>Update values</h4>
      <table class="table table-striped table-bordered">
        <tr><td>Next Expiredate : <input type="text" name="expireDate" size="10" value="<?=$expireDate?>"><br>
          This will be the date any user will get as <b>Expiredate</b> next time someone presses <b>Extend Expiredate</b> for that user.</td></tr>
      </table>
     <input class="btn btn-success" type="submit" name="save" value="Save">	
    </form>
<?php
	}
}