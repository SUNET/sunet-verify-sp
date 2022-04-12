<?php
// Import PHPMailer classes into the global namespace
// These must be at the top of your script, not inside a function
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$baseDir = dirname($_SERVER['SCRIPT_FILENAME'], 2);
//Load composer's autoloader
require $baseDir . '/html/vendor/autoload.php';
include $baseDir . '/html/config.php';

try {
	$db = new PDO("mysql:host=$dbServername;dbname=$dbName", $dbUsername, $dbPassword);
	// set the PDO error mode to exception
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
	echo "Error: " . $e->getMessage();
}

// Report 2 days back.
$fromDate = date('Y-m-d',time()-60*60*24*8);

$historyHandler = $db->prepare("SELECT DISTINCT `id` FROM HistoryLog WHERE type = 'User' AND `changed` >= $fromDate");
$historyInfoHandler = $db->prepare("SELECT `id`, `action`, `message`, `updater` FROM HistoryLog WHERE type = 'User' AND `changed` >= $fromDate AND id = :UserId");
$historyInfoHandler->bindParam(':UserId', $userId);
$userHandler = $db->prepare('SELECT Users.`id`, `userName`, Users.`fullName`, `EPPN`, `email`, `sshKey`, `sshEnabled`, `accessLevel`, `shortName`, `expireDate` FROM Users, Organizations WHERE Organizations_id = Organizations.id AND Users.id = :UserId');
$userHandler->bindParam(':UserId', $userId);
$writeHandler = $db->prepare('SELECT `group` FROM UserWrite, Groups WHERE `Users_id` = :UserId AND `Groups_id` = Groups.`id` ORDER BY `group`');
$writeHandler->bindParam(':UserId', $userId);
$readHandler = $db->prepare('SELECT `group` FROM UserRead, Groups WHERE `Users_id` = :UserId AND `Groups_id` = Groups.`id` ORDER BY `group`');
$readHandler->bindParam(':UserId', $userId);
$firewallHandler = $db->prepare('SELECT `group` FROM UserFirewall, Groups WHERE `Users_id` = :UserId AND `Groups_id` = Groups.`id` ORDER BY `group`');
$firewallHandler->bindParam(':UserId', $userId);

$historyHandler->execute();
$mesg = '';
while ($history = $historyHandler->fetch(PDO::FETCH_ASSOC)) {
	$userId = $history['id'];
	$userHandler->execute();
	if ($user = $userHandler->fetch(PDO::FETCH_ASSOC)) {
		if ($user['sshEnabled']) {
			$historyInfoHandler->execute();
			while ($historyInfo = $historyInfoHandler->fetch(PDO::FETCH_ASSOC) ) {
				$mesg .= sprintf ('# %s by %s<br>%s', $historyInfo['message'], utf8_decode($historyInfo['updater']), "\n");
				#$mesg .= sprintf ('# %s by %s<br>%s', $historyInfo['message'], $historyInfo['updater'], "\n");
				#printf ('%s <-> %s%s', $historyInfo['updater'],utf8_decode($historyInfo['updater']), "\n");
			}
			$mesg .= sprintf ('customer-users %s {<ul style="list-style-type:none">%s	<li>description	"%s, %s";</li>%s	<li>ssh-keys           [ "%s" ];</li>%s', $user['userName'], "\n", utf8_decode($user['fullName']), $user['email'], "\n",  $user['sshKey'], "\n");
			$userId = $user['id'];
			$writeHandler->execute();
			if ($group = $writeHandler->fetch(PDO::FETCH_ASSOC)) {
				$mesg .= sprintf('	<li>write-device-group [ %s', $group['group']);
				while ($group = $writeHandler->fetch(PDO::FETCH_ASSOC)) {
					$mesg .= sprintf(' %s', $group['group']);
				}
				$mesg .= " ];</li>\n";
			}
			$readHandler->execute();
			if ($group = $readHandler->fetch(PDO::FETCH_ASSOC)) {
				$mesg .= sprintf('	<li>read-device-group  [ %s', $group['group']);
				while ($group = $readHandler->fetch(PDO::FETCH_ASSOC)) {
					$mesg .= sprintf(' %s', $group['group']);
				}
				$mesg .= " ];</li>\n";
			}
			$firewallHandler->execute();
			if ($group = $firewallHandler->fetch(PDO::FETCH_ASSOC)) {
				$mesg .= sprintf('	<li>firewall-device-group [ %s', $group['group']);
				while ($group = $firewallHandler->fetch(PDO::FETCH_ASSOC)) {
					$mesg .= sprintf(' %s', $group['group']);
				}
				$mesg .= " ];</li>\n";
			}
			$mesg .= sprintf('	<li>expire-date        %s;</li>%s</ul>}%s<br>', $user['expireDate'], "\n", "\n");
		}
	}
}

if ($mesg) {
	$mailer = new PHPMailer(true);
	// $mailer->SMTPDebug = 2;
	$mailer->isSMTP();
	$mailer->Host = $SMTPHost;
	$mailer->SMTPAuth = true;
	$mailer->SMTPAutoTLS = true;
	$mailer->Port = 587;
	$mailer->SMTPAuth = true;
	$mailer->Username = $SASLUser;
	$mailer->Password = $SASLPassword;
	$mailer->SMTPSecure = 'tls';
	$mailer->CharSet = 'UTF-8';

	//Recipients
	$mailer->setFrom($MailFrom, 'SUNET-verify');
	$mailer->addBCC('bjorn@sunet.se');
	$mailer->addReplyTo('noc@sunet.se', 'NOC');

	if ($SendOut)
		$mailer->addAddress('NOC@sunet.se');

	//Content
	$mailer->isHTML(true);
	$mailer->Body		= $mesg;
	$mailer->AltBody	= str_replace(['<ul style="list-style-type:none">', '</ul>', '<li>', '</li>', '<br>'], ' ', $mesg);
	$mailer->Subject	= 'Updates from verify.sunet.se';

	try {
		$mailer->send();
	} catch (Exception $e) {
		echo 'Message could not be sent to contacts.<br>';
		echo 'Mailer Error: ' . $mailer->ErrorInfo . '<br>';
	}
}
