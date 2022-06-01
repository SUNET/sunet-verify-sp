CREATE TABLE Organizations (
	`id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
	`shortName` VARCHAR(50),
	`fullName` VARCHAR(256)
	);

CREATE TABLE Users (
	`id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
	`Organizations_id` INT UNSIGNED,
	`userName` VARCHAR(50),
	`fullName` TEXT,
	`EPPN` TINYTEXT,
	`email` TINYTEXT,
	`sshKey` TEXT,
	`sshEnabled` TINYINT UNSIGNED,
	`accessLevel` TINYINT UNSIGNED,
	`lastChanged` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
	`expireDate` DATE);

CREATE TABLE Groups (
	`id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
	`group` TINYTEXT);

CREATE TABLE UserWrite (
	`Users_id` INT UNSIGNED,
	`Groups_id` INT UNSIGNED);

CREATE TABLE UserRead (
	`Users_id` INT UNSIGNED,
	`Groups_id` INT UNSIGNED);

CREATE TABLE UserFirewall (
	`Users_id` INT UNSIGNED,
	`Groups_id` INT UNSIGNED);

CREATE TABLE Params (
	`name` VARCHAR(20),
	`value` VARCHAR(20) );

CREATE TABLE HistoryLog (
	`id` INT UNSIGNED,
	`type` ENUM('User', 'Org'),
	`action` VARCHAR(20),
	`message` TINYTEXT,
	`updater` TINYTEXT,
	`changed` timestamp NOT NULL DEFAULT current_timestamp() );

INSERT INTO Organizations (`id`,`shortName`, `fullName`) VALUES (1,'MyOrg', 'MyOrganization rename');

INSERT INTO Users (`Organizations_id`, `userName`, `fullName`, `EPPN`, `email`, `sshKey`, `sshEnabled`, `accessLevel`) VALUES (1, 'admin', '', '', 'admin@sunet.se', '', 0, 7);

INSERT INTO Params (`name`, `value`) VALUES ('expireDate', '2023-04-30');