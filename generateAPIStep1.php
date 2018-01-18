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

//---- TABLE CHECKING BEGINS

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

$sqlDescribeUsers = "DESCRIBE users";
$sqlDescribeUserLevels = "DESCRIBE userlevels";
$sqlDescribeSessions = "DESCRIBE sessions";
$resultDescribeUsers = $conn->query($sqlDescribeUsers);
$resultDescribeUserLevels = $conn->query($sqlDescribeUserLevels);
$resultDescribeSessions = $conn->query($sqlDescribeSessions);

if ($resultDescribeUsers->num_rows > 0 && $resultDescribeUserLevels->num_rows > 0 && $resultDescribeSessions->num_rows > 0) {
    $usersFields = array();
    $userLevelsFields = array();
    $sessionsFields = array();

    while ($row = $resultDescribeUsers->fetch_assoc()) array_push($usersFields, $row);
    while ($row = $resultDescribeUserLevels->fetch_assoc()) array_push($userLevelsFields, $row);
    while ($row = $resultDescribeSessions->fetch_assoc()) array_push($sessionsFields, $row);

    //Users Table Fields checking:
    $check_userID = false;
    $check_username = false;
    $check_password = false;
    $check_userlevelID = false;

    $usersTableOK = false;

    foreach ($usersFields as $field) {
        if ($field["Field"] == "UserID" && $field["Type"] == "int(10) unsigned" && $field["Key"] == "PRI" && $field["Extra"] == "auto_increment") $check_userID = true;
        if ($field["Field"] == "Username" && $field["Type"] == "varchar(100)") $check_username = true;
        if ($field["Field"] == "Password" && $field["Type"] == "varchar(255)") $check_password = true;
        if ($field["Field"] == "UserLevelID" && $field["Type"] == "int(10) unsigned") $check_userlevelID = true;
    }//end foreach userField

    if ($check_userID && $check_username && $check_password && $check_userlevelID) $usersTableOK = true;


    //UserLevels Table Fields Checking:
    $check_userLevelID = false;
    $check_userLevelName = false;
    $userLevelsTableOK = false;

    foreach ($userLevelsFields as $field) {
        if ($field["Field"] == "UserLevelID" && $field["Type"] == "int(10) unsigned" && $field["Key"] == "PRI" && $field["Extra"] == "auto_increment") $check_userLevelID = true;
        if ($field["Field"] == "UserLevelName" && $field["Type"] == "varchar(255)") $check_userLevelName = true;
    }//end foreach userLevelField

    if ($check_userLevelID && $check_userLevelName) $userLevelsTableOK = true;

    //Sessions Table Fields Checking:
    $check_sessionID = false;
    $check_userID = false;
    $check_initiatedOn = false;
    $check_finalizedOn = false;
    $check_clientIPAddress= false;

    $sessionsTableOK = false;

    foreach($sessionsFields as $field) {
        if ($field["Field"] == "SessionID" && $field["Type"] == "varchar(255)" && $field["Key"] == "PRI") $check_sessionID = true;
        if ($field["Field"] == "UserID" && $field["Type"] == "int(10) unsigned") $check_userID = true;
        if ($field["Field"] == "InitiatedOn" && $field["Type"] == "int(10) unsigned") $check_initiatedOn = true;
        if ($field["Field"] == "FinalizedOn" && $field["Type"] == "int(10) unsigned") $check_finalizedOn = true;
        if ($field["Field"] == "ClientIPAddress" && $field["Type"] == "varchar(255)") $check_clientIPAddress = true;
    }//end foreach sessionField

    if ($check_sessionID && $check_userID && $check_initiatedOn && $check_finalizedOn && $check_clientIPAddress) $sessionsTableOK = true;

    //Check ALL:
    if (($usersTableOK && $userLevelsTableOK && $sessionsTableOK) == false) {
        header("Location: ../index.php?status=DatabaseInconsistency"); exit();
    }

}//end if there are table in the database
else {
    header("Location: ../index.php?status=DatabaseInconsistency"); exit();
}

//---- TABLE CHECKING ENDS


$sqlUsersTable = "SELECT * FROM userlevels";
$result = $conn->query($sqlUsersTable);
$userLevels = array();
while ($row = $result->fetch_object()) array_push($userLevels, $row);

//TODO


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

    

    <div style="margin-top: 30px;"></div>

    <input style="float: right;" class="button" type="submit" value="Next ->" />
    <a class="button" style="float: left;" href="generateAPIStep0.php"><- Back to Step 0</a>

</form>


</body>


</html>