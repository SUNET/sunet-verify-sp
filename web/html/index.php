<?php
$baseDir = dirname($_SERVER['SCRIPT_FILENAME'], 1);
include $baseDir . '/config.php';

include 'include/Html.php';
$html = new HTML($DiscoveryService);

try {
  $db = new PDO("mysql:host=$dbServername;dbname=$dbName", $dbUsername, $dbPassword);
  // set the PDO error mode to exception
  $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
  echo "Error: " . $e->getMessage();
}

$html->showHeaders('User verification');
if (isset($_GET['Privacy'])) { ?>
    <div class="row">
      <div class="col">
        <h1>Transfer of personal data to User verification when using federated login</h1>
        <h2>Description of User verification</h2>
        <p>User verification is a system to handle SSH-keys in SUNET routers for system administrators at Sunet connected organizations.</p>
        <h2>Processing of personal data</h2>
        <h3>Transfer of personal data</h3>
        <p>Personal data is transferred from the identity provider (your login service) to User verification to login.</p>
        <p>When logging in to the service, the following personal data is requested from the identity provider you use:</p>
        <table class="table table-striped table-bordered">
          <tr>
            <th>Personal data</th>
            <th>Purpose</th>
            <th>Technical representation</th>
          </tr>
          <tr>
            <td>Unique identifiers</td>
            <td>To match user against a pre-configured user ID.</td>
            <td>eduPersonPrincipalName</td>
          </tr>
          <tr>
            <td>Assurance level</td>
            <td>To allow restriction of logins to a specific assurance level.</td>
            <td>eduPersonAssurance</td>
          </tr>
          <tr>
            <td>E-mail</td>
            <td>To match user against a pre-configured user ID.</td>
            <td>email to boostrap the first time if ePPN is missing in database.</td>
          </tr>
          <tr>
            <td>Name</td>
            <td>To allow display of name in User verification.</td>
            <td>displayName<br>givenName<br>sn</td>
          </tr>
        </table>
        <p>In addition to direct personal data, indirect personal data is also transferred, such as which organisation the user belongs to and which identity provider has been used when logging in. In combination with the above personal data, this can be used to uniquely identify a person.</p>
        <h3>Other processing of personal data within the service</h3>
        <p>User verification stores technical logs for debugging purposes and security related incidents. These technical logs contain information regarding all authentications made to the service and the personal data transferred.</p>
        <h3>Transfer of personal data to third parties</h3>
        <p>No personal data is transferred to third parties.</p>
        <h3>Lawful basis</h3>
        <p>Personal data is processed on the basis of authentication. Personal data must be transferred in order to match a user to a preconfigured user account.</p>
        <h3>Right of access, right of rectification and right of erasure of personal data</h3>
        <p>For access, rectification and erasure of your personal data, contact the Personal data controller.</p>
        <h3>Purging of personal data</h3>
        <p>Personal data as described above is not automatically purged from the service.</p>
        <h2>Personal data controller</h2>
        <p>Personal data controller for the processing of personal data is The Swedish Research Council, Sweden. If you have questions about how personal data is processed within the service, please contact <a href="mailto:noc@sunet.se" rel="nofollow">noc@sunet.se</a>.</p>
        <p>Contact information for The Swedish Research Council’s data protection officer can be found at <a href="https://www.vr.se/behandling-av-personuppgifter.html" rel="nofollow">https://www.vr.se/behandling-av-personuppgifter.html</a>.</p>
        <h2>GÉANT Data Protection Code of Conduct</h2>
        <p>This service complies with the international framework GÉANT Data Protection Code of Conduct (<a href="http://www.geant.net/uri/dataprotection-code-of-conduct/v1" class="external-link" rel="nofollow">http://www.geant.net/uri/dataprotection-code-of-conduct/v1</a>) for the transfer of personal data from identity providers to the service. This framework is intended for services in Sweden, the EU and the EEA which are used in research and higher education.</p>
      </div>
    </div>
<?php
} else {
?>
    <div class="row">
      <div class="col">
        <p>To handle your SSH-access to SUNET-routers, Please login.</p>
        <p>You need to login with at least SWAMID AL2</p>
        Your IdP needs to deliver the following Attributes
        <ul>
          <li>eduPersonAssurance - with at least SWAMID AL2</li>
          <li>eduPersonPrincipalName</li>
          <li>mail</li>
        </ul>
        The folloing Attribues are not mandatory, but are used for better UI :-)
        <ul>
          <li>displayName</li>
          <li>givenName</li>
          <li>sn</li>
        </ul>
      </div>
    </div>
<?php
}
$html->showFooter(array(),true);
