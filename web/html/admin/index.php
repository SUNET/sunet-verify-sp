<?php
$baseDir = dirname($_SERVER['SCRIPT_FILENAME'], 2);
include $baseDir . '/config.php';

include $baseDir . '/include/Html.php';
$html = new HTML();

$errorURL = isset($_SERVER['Meta-errorURL']) ? '<a href="' . $_SERVER['Meta-errorURL'] . '">Mer information</a><br>' : '<br>';
$errorURL = str_replace(array('ERRORURL_TS', 'ERRORURL_RP', 'ERRORURL_TID'), array(time(), 'https://verify.sunet.se/shibboleth', $_SERVER['Shib-Session-ID']), $errorURL);

$errors = '';
if (isset($_SERVER['Meta-Assurance-Certification'])) {
	$AssuranceCertificationFound = false;
	foreach (explode(';',$_SERVER['Meta-Assurance-Certification']) as $AssuranceCertification) {
		if ($AssuranceCertification == 'http://www.swamid.se/policy/assurance/al2')
			$AssuranceCertificationFound = true;
	}
	if (! $AssuranceCertificationFound) {
		$errors .= sprintf('%s has no AssuranceCertification (http://www.swamid.se/policy/assurance/al2)<br>', $_SERVER['Shib-Identity-Provider']);
	}
}

if ($_SERVER['Shib-Identity-Provider'] == 'https://idp.sunet.se/idp') {
	$errors = '';
} else {
	if (isset($_SERVER['eduPersonAssurance'])) {
		$AssuranceFound = false;
		foreach (explode(';',$_SERVER['eduPersonAssurance']) as $Assurance) {
			if ($Assurance == 'http://www.swamid.se/policy/assurance/al2')
				$AssuranceFound = true;
		}
		if (! $AssuranceFound) {
			$errors .= 'Missing http://www.swamid.se/policy/assurance/al2 in eduPersonAssurance ' . str_replace(array('ERRORURL_CODE', 'ERRORURL_CTX'), array('AUTHORIZATION_FAILURE', 'http://www.swamid.se/policy/assurance/al2'), $errorURL);
		}
	} else {
		$errors .= 'Missing eduPersonAssurance in SAML response ' . str_replace(array('ERRORURL_CODE', 'ERRORURL_CTX'), array('AUTHORIZATION_FAILURE', 'http://www.swamid.se/policy/assurance/al2'), $errorURL);
	}
}

if (isset($_SERVER['eduPersonPrincipalName'])) {
	$EPPN = $_SERVER['eduPersonPrincipalName'];
} else {
	$errors .= 'Missing eduPersonPrincipalName in SAML response ' . str_replace(array('ERRORURL_CODE', 'ERRORURL_CTX'), array('IDENTIFICATION_FAILURE', 'eduPersonPrincipalName'), $errorURL);
}

if ( isset($_SERVER['mail'])) {
	$mailArray = explode(';',$_SERVER['mail']);
	$mail = $mailArray[0];
} else {
	$errors .= 'Missing mail in SAML response ' . str_replace(array('ERRORURL_CODE', 'ERRORURL_CTX'), array('IDENTIFICATION_FAILURE', 'mail'), $errorURL);
}

if (isset($_SERVER['displayName'])) {
	$fullName = $_SERVER['displayName'];
} elseif (isset($_SERVER['givenName'])) {
	$fullName = $_SERVER['givenName'];
	if(isset($_SERVER['sn']))
		$fullName .= ' ' .$_SERVER['sn'];
} else
	$fullName = '';

if ($errors != '') {
	$html->showHeaders('Verify - Errors');
	printf('%s    <div class="row alert alert-danger" role="alert">%s      <div class="col">%s        <b>Errors:</b><br>%s        %s%s      </div>%s    </div>%s', "\n", "\n", "\n", "\n", str_ireplace("\n", "<br>", $errors), "\n", "\n","\n");
	$html->showFooter(array());
	exit;
}

$displayName = '<div> Logged in as : <br> ' . $fullName . ' (' . $EPPN .')</div>';
$html->setDisplayName($displayName);

include $baseDir . '/include/Security.php';
include $baseDir . '/include/Users.php';
include $baseDir . '/include/Organizations.php';
$security = new Security($baseDir, $EPPN, $mail, $fullName);

if ($security->getUserAccessLevel() == 0) {
	$html->showHeaders('Verify - Errors');
	printf('%s    <div class="row alert alert-danger" role="alert">%s      <div class="col">%s        <b>Errors:</b><br>%s         You doesn\'t seems to exist in our database.<br>Please contact NOC if you think you should have access.<br>Please also include : <ul><li>ePPN : %s</li><li>e-mail : %s</li><li>Full name : %s</li></ul>%s      </div>%s    </div>%s', "\n", "\n", "\n", "\n", $EPPN, $mail, $fullName, "\n", "\n","\n");
	$html->showFooter(array());
	exit;
}

if (isset($_GET['userLevel']) && $security->setUserLevel($_GET['userLevel'])) {
		$users = new Users($baseDir, $_GET['userLevel']);
		$organizations = new Organizations($baseDir, $_GET['userLevel']);
} else {
	$users = new Users($baseDir);
	$organizations = new Organizations($baseDir);
}

if (isset($_GET['action'])) {
	$action = $_GET['action'];
} else {
	$action = "ssh";
}
switch($action) {
	case 'addUser' :
		if ($security->getUserAccessLevel() < 4) { 
			showNoAccess();
		}
	case 'user' :
	case 'ssh' :
		if ($action == 'ssh') {
			$menuActive = 'ssh';
		} else {
			$menuActive = 'user';
		}
		if (isset($_POST['verify']) && isset($_POST['userId'])) {
			extendExpireDate();
			showUser($_POST['userId']);
		} elseif (isset($_POST['update']) && isset($_POST['userId'])) {
			showUser($_POST['userId'], true);
		} elseif (isset($_POST['saveKey']) && isset($_POST['userId'])) {
			saveSshKey();
			showUser($_POST['userId']);
		} elseif (isset($_POST['editUser']) && isset($_POST['userId'])) {
			if ($security->getUserAccessLevel() < 4) { 
				showNoAccess();
			}
			showEditUser($_POST['userId']);
		} elseif (isset($_POST['save']) && isset($_POST['userId'])) {
			if ($userId = $users->saveUser($_POST['userId'])) {
				if ($action == 'addUser') {
					$security->logChanges($_POST['userId'], 'User', 'add', 'Added user');
				} else {
					$security->logChanges($_POST['userId'], 'User', 'update', 'Updated user');
				}
			} else {
				$userId = $_POST['userId'];
			}
			showUser($userId);
		} elseif (isset ($_GET['userId'])) {
			showUser($_GET['userId']);
		} else {
			if ($action == 'addUser') {
				showEditUser();
			} elseif ($action == 'ssh') {
				showUser($security->getUserId());
			} else {
				showUserList();
			}
		}
		break;
	case 'removeUser' :
		$menuActive = 'user';
		if ($security->getUserAccessLevel() < 4) { 
			showNoAccess();
		} elseif (isset($_GET['userId'])) {
			removeUser($_GET['userId']);
		}
		break;
	
	# ORG
	case 'org' :
		$menuActive = 'org';
		if ($security->getUserAccessLevel() < 2) { 
			showNoAccess();
		} else {
			if (isset($_GET['organizationId'])) {
				showOrganization($_GET['organizationId']);
			} else {
				showOrgList();
			}
		}
		break;
	case 'addOrg' :
		$menuActive = 'org';
		if ($security->getUserAccessLevel() < 4) { 
			showNoAccess();
		} else {
			if (isset($_POST['save']) && isset($_POST['orgId'])) {
				if ($orgId = $organizations->saveOrganization($_POST['orgId'])) {
					$security->logChanges($orgId, 'Org', 'add', 'Added organization');
					showOrganization($orgId);
				}
			} else {
				showEditOrganization();
			}
		}
		break;
	case 'editOrg' :
		$menuActive = 'org';
		if ($security->getUserAccessLevel() < 4) { 
			showNoAccess();
		} else {
			if (isset($_POST['save']) && isset($_POST['orgId'])) {
				if ($organizations->saveOrganization($_POST['orgId'])) {
					$security->logChanges($_POST['orgId'], 'Org', 'update', 'Updated organization');
				}
				showOrganization($_POST['orgId']);
			} elseif (isset($_GET['orgId'])) {
				showEditOrganization($_GET['orgId']);
			} else {
				showOrgList();
			}
		}
		break;
	case 'removeOrg' :
		$menuActive = 'org';
		if ($security->getUserAccessLevel() < 4) { 
			showNoAccess();
		} elseif (isset($_GET['orgId'])) {
			removeOrganization($_GET['orgId']);
		}
		break;
	case 'admin' :
		$menuActive = 'admin';
		if ($security->getUserAccessLevel() < 2)
		{ 
			showNoAccess();
		} else {
			showAdmin();
		}
		break;
	case 'exportUsers' :
		$menuActive = 'admin';
		if ($security->getUserAccessLevel() < 2)
		{ 
			showNoAccess();
		} else {
			$users->exportUsers();
			exit;
		}
		break;
	case 'spoofAccess' : 
		$menuActive = 'spoofAccess';
		showSpoofAccess();
		break;
	default :
		showNoAccess();
}

$html->showFooter(array(),true);

####
# Extend ExpireDate for account
####
function extendExpireDate() {
	global $security, $users;
	if ($_POST['userId'] == $security->getUserId() || $security->getUserAccessLevel() > 4 ) {
		if ($users->extendExpireDate($_POST['userId'])) {
			$security->logChanges($_POST['userId'], 'User', 'expire', 'ExpireDate extended');
		}
	}
}

####
# Shows SSH-key and verfification of the same
####
function showUser($id = 0,$updateKey = false) {
	global $html, $security, $users;
	$html->showHeaders('Verify - User');
	showMenu();
	$edit = $security->getUserAccessLevel() > 4 ? true : ($id == $security->getUserId());
	if ( $id == 0 ) {
		$users->showUser($security->getUserAccessLevel(),$security->getUserId(), true, $updateKey);
	} else {
		$users->showUser($security->getUserAccessLevel(),$id, $edit, $updateKey);
	}
}

####
# Save SSH-key
####
function saveSshKey() {
	global $security, $users;
	if ( $_POST['userId'] > 0  && ( $_POST['userId'] == $security->getUserId() || $security->getUserAccessLevel() > 4) ) {
		if ($users->saveSshKeyUser($_POST['userId'], $_POST['ssh-key'])) {
			$security->logChanges($_POST['userId'], 'User', 'sshkey', 'SSH-key updated');
		}
	}
}

####
# Shows a list of users dependig of access-right
####
function showUserList() {
	global $html, $security, $users;
	$html->showHeaders('Verify - Users');
	showMenu();
	$users->listUsers($security->getUserAccessLevel(), $security->getOrganizationsId());
}

###
# Shows form to add/edit User
###
function showEditUser($userId = 0) {
	global $html, $users;
	$html->showHeaders('Verify - User');
	showMenu();
	$users->showEditUser($userId);
}

function removeUser($userId) {
	global $html, $security, $users;
	if (isset($_POST['confirm'])) {
		if ($userName = $users->removeUser($userId)) {
			$security->logChanges($userId, 'User', 'remove', 'Removed - ' . $userName);
		}
		showUserList();
	} else {
		$html->showHeaders('Verify - User - Remove');
		showMenu();
		$users->showUser($security->getUserAccessLevel(), $userId, false, false, true);
 	}
}

####
# Shows a list of organizations dependig of access-right
####
function showOrgList() {
	global $html, $security, $organizations;
	$html->showHeaders('Verify - Organizations');
	showMenu();
	$organizations->listOrganizations($security->getUserAccessLevel(), $security->getOrganizationsId(),$security->spoof());
}

###
# Shows info about one Organization
###
function showOrganization($orgId = 0) {
	global $html, $security, $organizations;
	$html->showHeaders('Verify - Organization');
	showMenu();
	$organizations->showOrganization($security->getUserAccessLevel(), $orgId);
}

###
# Shows form to add/edit Organization
###
function showEditOrganization($orgId = 0) {
	global $html, $organizations;
	$html->showHeaders('Verify - Organization');
	showMenu();
	$organizations->showEditOrganization($orgId);
}

function removeOrganization($orgId) {
	global $html, $security, $organizations;
	if (isset($_POST['confirm'])) {
		if ($shortName = $organizations->removeOrganization($_GET['orgId'])) {
			$security->logChanges($_GET['orgId'], 'Org', 'remove', 'Removed - ' . $shortName);
		}
		showOrgList();
	} else {
		$html->showHeaders('Verify - Organization - Remove');
		showMenu();
		$organizations->showOrganization($security->getUserAccessLevel(), $orgId, true);
	}
}
####
function showAdmin() {
	global $html, $users;
	$html->showHeaders('Verify - Admin');
	showMenu();
	$users->showAdmin();
}

####
function showSpoofAccess() {
	global $html, $security;
	$html->showHeaders('Verify - Spoof');
	showMenu();
	printf('    Switch to :<br>%s', "\n");	
	switch ($security->getRealUserAccessLevel()) {
		case 7 :
			if ($security->getUserAccessLevel() == 7) {
				printf('    <button type="button" class="btn btn-outline-primary">NOC - Admin</button><br>%s', "\n");	
			} else {
				printf('    <a href=".?action=spoofAccess&userLevel=7"><button type="button" class="btn btn-primary">NOC - Admin</button></a><br>%s', "\n");	
			}
		case 3 :
			if ($security->getUserAccessLevel() == 3) {
				printf('    <button type="button" class="btn btn-outline-primary">NOC</button><br>%s', "\n");	
			} else {
				printf('    <a href=".?action=spoofAccess&userLevel=3"><button type="button" class="btn btn-primary">NOC</button></a><br>%s', "\n");	
			}
		case 1 :
			if ($security->getUserAccessLevel() == 1) {
				printf('    <button type="button" class="btn btn-outline-primary">User</button><br>%s', "\n");	
			} else {
				printf('    <a href=".?action=spoofAccess&userLevel=1"><button type="button" class="btn btn-primary">User</button></a><br>%s', "\n");	
			}
			break;
		default :
			printf('%s    <div class="row alert alert-danger" role="alert">%s      <div class="col">%s        <b>Errors:</b><br>%s        Missing level :%s      </div>%s    </div>%s', "\n", "\n", "\n", "\n", $security->getRealUserAccessLevel(), "\n", "\n","\n");
	}
}


function showNoAccess() {
	global $html;
	$html->showHeaders('Verify - NoAccess');
	showMenu();
	printf('%s    <div class="row alert alert-danger" role="alert">%s      <div class="col">%s        <b>Errors:</b><br>%s        Du har inte access till denna del%s      </div>%s    </div>%s', "\n", "\n", "\n", "\n", "\n", "\n","\n");
	$html->showFooter(array(),true);
	exit;
}

####
# Shows menu row
####
function showMenu() {
	global $menuActive, $security;
	$spoofedUserLevel = $security->spoof() ? '&userLevel=' . $security->getUserAccessLevel() : '';
	print "\n";
	printf('    <a href=".?action=ssh%s"><button type="button" class="btn btn%s-primary">My access</button></a>%s', $spoofedUserLevel, $menuActive == 'ssh' ? '' : '-outline', "\n");
	printf('    <a href=".?action=user%s"><button type="button" class="btn btn%s-primary">Users</button></a>%s', $spoofedUserLevel, $menuActive == 'user' ? '' : '-outline', "\n");
	if ( $security->getUserAccessLevel() > 1 ) {
		printf('    <a href=".?action=org%s"><button type="button" class="btn btn%s-primary">Organizations</button></a>%s', $spoofedUserLevel, $menuActive == 'org' ? '' : '-outline', "\n");
		printf('    <a href=".?action=admin%s"><button type="button" class="btn btn%s-primary">Admin</button></a>%s', $spoofedUserLevel, $menuActive == 'admin' ? '' : '-outline', "\n");
	}
	if ( $security->getRealUserAccessLevel() > 1 ) {
		if ($security->spoof()) {
			printf('    <a href=".?action=spoofAccess%s"><button type="button" class="btn btn%s-danger">SpoofedAccess</button></a>%s', $spoofedUserLevel, $menuActive == 'spoofAccess' ? '' : '-outline', "\n");
		} else {
			printf('    <a href=".?action=spoofAccess%s"><button type="button" class="btn btn%s-primary">SpoofAccess</button></a>%s', $spoofedUserLevel, $menuActive == 'spoofAccess' ? '' : '-outline', "\n");	
		}
	}
	print "\n    <br>\n    <br>\n";
}