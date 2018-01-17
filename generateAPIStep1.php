<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" type="text/css"  href="styles.css">
    <title>PaNick Apps API Generator in PHP</title>
</head>


<body>

<?php

if (!isset($_GET["dbName"]) || $_GET["dbName"] == "") {
    header("Location: ../index.php?status=EmptyDBName"); exit();
}
else if (!isset($_GET["dbHostIP"]) || $_GET["dbHostIP"] == "") {
    header("Location: ../index.php?status=EmptyDBHostIP"); exit();
}
else if (!isset($_GET["dbUser"]) || $_GET["dbUser"] == "") {
    header("Location: ../index.php?status=EmptyDBUser"); exit();
}
else if (!isset($_GET["dbPassword"]) || $_GET["dbPassword"] == "") {
    header("Location: ../index.php?status=EmptyDBPassword"); exit();
}

$dbName = $_GET["dbName"];
$dbHostIP = $_GET["dbHostIP"];
$dbUsername = $_GET["dbUser"];
$dbPassword = $_GET["dbPassword"];


$conn = new mysqli($dbHostIP, $dbUsername, $dbPassword, $dbName);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$sql = "SHOW TABLES";
$result = $conn->query($sql);

$sessionsTableFound = false;
$userlevelsTableFound = false;
$usersTableFound = false;

$tableNames = array();

while ($row = $result->fetch_array()) {
    array_push($tableNames, $row[0]);
    if ($row[0] == "sessions") $sessionsTableFound = true;
    else if ($row[0] == "userlevels") $userlevelsTableFound = true;
    else if ($row[0] == "users") $usersTableFound = true;
}

if (!($sessionsTableFound && $usersTableFound && $userlevelsTableFound)) {
    header("Location: ../index.php?status=DatabaseInconsistency"); exit();
}

//TODO: Check that all necessary fields exist in the necessary tables.

$sqlUsersTable = "SELECT * FROM userlevels";
$result = $conn->query($sqlUsersTable);
$userLevels = array();
while ($row = $result->fetch_object()) array_push($userLevels, $row);
$userLevelsTableHeaderContents = "";
$userLevelsTableRowContents = "";
foreach ($userLevels as $userLevel) {
    $userLevelsTableHeaderContents .= "<th>" . $userLevel->UserLevelName . "</th>\r\n";
    $userLevelsTableRowContents .= "
                        <td>
                            <p><input class='writeDelete " . $userLevel->UserLevelID . "' title=\"" . $userLevel->UserLevelName . " Write/Delete\" type=\"checkbox\"/> Write/Delete</p>
                            <p><input class='view " . $userLevel->UserLevelID . "' title=\"" . $userLevel->UserLevelName . " View\" type=\"checkbox\"/> View</p>
                        </td>";
}

$allTablesFormHTML = "
    <table border=\"1\">
        <tr>
            <th>Endpoint Name</th>
            <th>Endpoint Page</th>
            <th>Access Authorization</th>
            <th>Generate?</th>
        </tr>";

foreach ($tableNames as $table) {
    $tableFormHTML = "<tr>
            <td style='text-align: center;'>" . ucfirst($table) . "</td>
            <td>API/<input class='endpointName' name='endpointName_" . ucfirst($table) . "' style='display:inline-block; max-width: 100px;' type='text' maxlength='20' value='" . $table . "'/>.php</td>
            <td>
                <table border=\"1\">
                    <tr>
                        " . $userLevelsTableHeaderContents . "
                        <th>Public</th>
                    </tr>
                    <tr>
                        " . $userLevelsTableRowContents . "
                        <td>
                            <p><input class='writeDelete public' title=\"Public Write/Delete\" type=\"checkbox\"/> Write/Delete</p>
                            <p><input class='view public' title=\"Public View\" type=\"checkbox\"/> View</p>
                        </td>
                    </tr>
                </table>
            </td>
            <td><p><input class='doGenerate " . $table . "' title=\"" . $table . " Generation\" checked=\"checked\" type=\"checkbox\"/> Generate</p></td>
        </tr>";
    $allTablesFormHTML .= $tableFormHTML;
}


if (isset($_GET["status"])) {
    switch($_GET["status"]) {
//        case "DBLoginCreated":
//            echo "<p class='successCard'>File DBLogin.php created</p>";
//            break;
    }
}

?>

<h1>Step 1</h1>

<h2>Generate Web API by database table</h2>

<form name="next" action="generateAPIStep2.php" type="post">

    <?php echo $allTablesFormHTML; ?>

    <div style="margin-top: 30px;"></div>

    <input style="float: right;" class="button" type="submit" value="Next ->" />
    <a class="button" style="float: left;" href="generateAPIStep0.php"><- Back to Step 0</a>

</form>


</body>


</html>