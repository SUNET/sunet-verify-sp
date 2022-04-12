# Database

## Organizations
Orgranizations to group all users. Only used for GUI in tool.
Field | Type | Usage
------|------| -----
id | INT UNSIGNED | Id number in the database for this Organisation
shortName | VARCHAR(50) | Short description of the Organization
fullName | VARCHAR(256) | Organization fullname

## Users
Users that exists in the system. Primary users from universitys that should have access to our routers. But also NOC/Admin users.
Field | Type | Usage
------|------| -----
id | INT UNSIGNED | Id number in the database for this User
Organizations_id | INT UNSIGNED | Foreign key (id) from Organisations table
userName | VARCHAR(50) | Username in routers
fullName | TEXT | Fullname of the user
EPPN | TINYTEXT | ePPN of the user
email | TINYTEXT | email of the user
sshKey | TEXT | sshKey for this user
sshEnabled | TINYINT UNSIGNED | If this user should be listed for export to routers
accessLevel | TINYINT UNSIGNED |  access-level for this user in the tool (#1 = show own Organisation, #2 = Show all Organisations / Users, #4 = Edit all Organisations / Users)
lastChanged | timestamp | Timestamp when this userinfo last was updated
expireDate | DATE | When this users ssh-key should expire from the routers

## Groups
Device groups that exists in teh routers and that can be allocated to an user.
Field | Type | Usage
------|------| -----
id | INT UNSIGNED | Id number for the group
group | TINYTEXT | Groupname

## UserWrite
Field | Type | Usage
------|------| -----
Users that should have Writeaccess in a Devicegroup.
Users_id | INT UNSIGNED | Foreign key (id) from Users table
Groups_id | INT UNSIGNED | Foreign key (id) from Groups table

## UserRead
Field | Type | Usage
------|------| -----
Users that should have Readaccess in a Devicegroup.
Users_id | INT UNSIGNED | Foreign key (id) from Users table
Groups_id | INT UNSIGNED | Foreign key (id) from Groups table

## UserFirewall
Users that should have Firewallaccess in a Devicegroup.
Field | Type | Usage
------|------| -----
Users_id | INT UNSIGNED | Foreign key (id) from Users table
Groups_id | INT UNSIGNED | Foreign key (id) from Groups table

## Params
Different parameters used in the system.
Field | Type | Usage
------|------| -----
name | VARCHAR(20) | Name
value | VARCHAR(20) | Value

## HistoryLog
Historylog for actions taken on on users and organizations.
Field | Type | Usage
------|------| -----
id | INT UNSIGNED | id for effected user or organization.
type | ENUM('User', 'Org') | Type of id
action | VARCHAR(20) | Action taken
message | TINYTEXT | Full message for action
updater | TINYTEXT | Person that updates this user/organization
