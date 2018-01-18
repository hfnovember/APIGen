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

$fullTable = "";

foreach ($tableNames as $tableName) {

    $indexers = array();

    $sqlGetTableIndexers = "DESCRIBE " . $tableName;
    $result = $conn->query($sqlGetTableIndexers);
    while ($row = $result->fetch_assoc()) {
        if ($row["Key"] == "UNI") array_push($indexers, $row);
    }//end while

    $indexerTableHTML = "";
    $indexFunctions = "";

    foreach ($indexers as $indexer) {
        $x = "
        <tr style='background-color: beige'>
            <td style=\"text-align: center;\">Get By " . ucfirst($indexer["Field"]) . "</td>
            <td style=\"text-align: center\"><input title=\"Generate " . $tableName . " get by #indexName\" class=\"" . $tableName . "\" type=\"checkbox\" checked=\"checked\" id=\"generate_" . $tableName . "_getBy".ucfirst($indexer["Field"])."\" name=\"generate_" . $tableName . "_getBy" . ucfirst($indexer["Field"]) . "\" onclick=\"" . $tableName . "_GetBy" . ucfirst($indexer["Field"]) . "()\"/> </td>
        ";
        foreach ($userLevels as $userLevel) {
            $ul = "<td style=\"text-align: center\"><input title=\"" . ucfirst($indexer["Field"]) . " access to get by " . ucfirst($indexer["Field"]) . " " . $tableName . "\" type=\"checkbox\" checked=\"checked\" name=\"getBy" . ucfirst($indexer["Field"]) . "_" . $tableName . "_" . $userLevel->UserLevelName . "\" class=\"" . $tableName . " getBy".ucfirst($indexer["Field"])."\"/> </td>";
            $x .= $ul;
        }
        $indexerTableHTML .= $x . "</tr>";

        $indexFunctions .= "
        
        function " . $tableName . "_GetBy".ucfirst($indexer["Field"])."() {
            var c = document.getElementById(\"generate_" . $tableName . "_getBy".ucfirst($indexer["Field"])."\");
            var a = document.getElementsByClassName(\"" . $tableName . " getBy" . ucfirst($indexer["Field"]) . "\");
            for (i = 0; i < a.length; i++) a[i].checked = c.checked;
        }
        
        ";
    }

    $headerUserTypes = "";
    $create_users = "";
    $getByID_users = "";
    $getMultiple_users = "";
    $update_users = "";
    $searchByField_users = "";
    $delete_users = "";
    $getsize_users = "";
    $isEmpty_users = "";

    foreach ($userLevels as $userLevel) {
        $uln = $userLevel->UserLevelName;
        $headerUserTypes .= "<th>" . $uln . "</th>\r\n";
        $create_users .= "<td style=\"text-align: center\"><input title=\"" . $uln . " access to create " . $tableName . "\" type=\"checkbox\" checked=\"checked\" name=\"create_" . $tableName . "_" . $uln . "\" class=\"" . $tableName . " create\"/> </td>\r\n";
        $getByID_users .= "<td style=\"text-align: center\"><input title=\"" . $uln . " access to get by ID " . $tableName . "\" type=\"checkbox\" checked=\"checked\" name=\"getByID_" . $tableName . "_" . $uln . "\" class=\"" . $tableName . " getByID\"/> </td>\r\n";
        $getMultiple_users .= "<td style=\"text-align: center\"><input title=\"" . $uln . " access to get multiple " . $tableName . "\" type=\"checkbox\" checked=\"checked\" name=\"getMultiple_" . $tableName . "_" . $uln . "\" class=\"" . $tableName . " getMultiple\"/> </td>\r\n";
        $update_users .= "<td style=\"text-align: center\"><input title=\"" . $uln . " access to update " . $tableName . "\" type=\"checkbox\" checked=\"checked\" name=\"update_" . $tableName . "_" . $uln . "\" class=\"" . $tableName . " update\"/> </td>\r\n";
        $searchByField_users .= "<td style=\"text-align: center\"><input title=\"" . $uln . " access to searchByField " . $tableName . "\" type=\"checkbox\" checked=\"checked\" name=\"searchByField_" . $tableName . "_" . $uln . "\" class=\"" . $tableName . " searchByField\"/> </td>\r\n";
        $delete_users .= "<td style=\"text-align: center\"><input title=\"" . $uln . " access to delete " . $tableName . "\" type=\"checkbox\" checked=\"checked\" name=\"delete_" . $tableName . "_" . $uln . "\" class=\"" . $tableName . " delete\"/> </td>\r\n";
        $getsize_users .= "<td style=\"text-align: center\"><input title=\"" . $uln . " access to get size " . $tableName . "\" type=\"checkbox\" checked=\"checked\" name=\"getSize_" . $tableName . "_" . $uln . "\" class=\"" . $tableName . " getSize\"/> </td>\r\n";
        $isEmpty_users .= "<td style=\"text-align: center\"><input title=\"" . $uln . " access to isEmpty " . $tableName . "\" type=\"checkbox\" checked=\"checked\" name=\"isEmpty_" . $tableName . "_" . $uln . "\" class=\"" . $tableName . " isEmpty\"/> </td>\r\n";
    }

    $str = "

    <hr/>
    
    <h3>" . ucfirst($tableName) . "</h3>
    
     <p><b>Base URL:</b> <i>API/ </i><input style=\"font-style: italic;\" type=\"text\" maxlength=\"50\" title=\"" . $tableName . " URL\" value=\"" . $tableName . "\" name=\"url_" . $tableName . "\"/> Generate:
        <input type=\"checkbox\" id=\"" . $tableName . "_generate\" checked=\"checked\" onclick=\"" . $tableName . "_Generate()\"/></p>
    
    <table border=\"1\" cellpadding='5'>

        <tr style='background-color: black; color: white;'>
            <th>Functionality</th>
            <th>Generate?</th>
            " . $headerUserTypes . "
        </tr>

        <tr>
            <td style=\"text-align: center\">Create</td>
            <td style=\"text-align: center\"><input title=\"Generate " . $tableName . " create\" class=\"" . $tableName . "\" type=\"checkbox\" checked=\"checked\" id=\"generate_" . $tableName . "_create\" name=\"generate_" . $tableName . "_create\" onclick=\"" . $tableName . "_Create()\"/> </td>
            " . $create_users . "
        </tr>

        <tr>
            <td style=\"text-align: center\">Get By ID</td>
            <td style=\"text-align: center\"><input title=\"Generate " . $tableName . " get by ID\" class=\"" . $tableName . "\" type=\"checkbox\" checked=\"checked\" id=\"generate_".$tableName."_getByID\" name=\"generate_" . $tableName . "_getByID\" onclick=\"" . $tableName . "_GetByID()\"/> </td>
            " . $getByID_users . "
        </tr>
        
        " . $indexerTableHTML . "

        <tr>
            <td style=\"text-align: center\">Get Multiple</td>
            <td style=\"text-align: center\"><input title=\"Generate " . $tableName . " get multiple\" class=\"" . $tableName . "\" type=\"checkbox\" checked=\"checked\" id=\"generate_" . $tableName . "_getMultiple\" name=\"generate_" . $tableName . "_getMultiple\" onclick=\"" . $tableName . "_GetMultiple()\"/> </td>
            " . $getMultiple_users . "
        </tr>

        <tr>
            <td style=\"text-align: center\">Update</td>
            <td style=\"text-align: center\"><input title=\"Generate " . $tableName . " update\" class=\"" . $tableName . "\" type=\"checkbox\" checked=\"checked\"  id=\"generate_" . $tableName . "_update\" name=\"generate_" . $tableName . "_update\" onclick=\"" . $tableName . "_Update()\"/> </td>
            " . $update_users . "
        </tr>

        <tr>
            <td style=\"text-align: center\">Search by Field</td>
            <td style=\"text-align: center\"><input title=\"Generate " . $tableName . " searchByField\" class=\"" . $tableName . "\" type=\"checkbox\" checked=\"checked\" id=\"generate_" . $tableName . "_searchByField\" name=\"generate_" . $tableName . "_searchByField\" onclick=\"" . $tableName . "_SearchByField()\"/> </td>
            " . $searchByField_users . "       
        </tr>

        <tr>
            <td style=\"text-align: center\">Delete</td>
            <td style=\"text-align: center\"><input title=\"Generate " . $tableName . " delete\" class=\"" . $tableName . "\" type=\"checkbox\" checked=\"checked\" id=\"generate_" . $tableName . "_delete\" name=\"generate_" . $tableName . "_delete\" onclick=\"" . $tableName . "_Delete()\"/> </td>
            " . $delete_users . "
        </tr>

        <tr>
            <td style=\"text-align: center\">Get Size</td>
            <td style=\"text-align: center\"><input title=\"Generate " . $tableName . " get size\" class=\"" . $tableName . "\" type=\"checkbox\" checked=\"checked\" id=\"generate_" . $tableName . "_getSize\" name=\"generate_" . $tableName . "_getSize\" onclick=\"" . $tableName . "_GetSize()\"/> </td>
            " . $getsize_users . "
        </tr>

        <tr>
            <td style=\"text-align: center\">Is Empty</td>
            <td style=\"text-align: center\"><input title=\"Generate " . $tableName . " isEmpty\" class=\"" . $tableName . "\" type=\"checkbox\" checked=\"checked\" id=\"generate_" . $tableName . "_isEmpty\" name=\"generate_" . $tableName . "_isEmpty\" onclick=\"" . $tableName . "_IsEmpty()\"/> </td>
            " . $isEmpty_users . "
        </tr>
        
    </table>

        <script>
            function " . $tableName . "_Create() {
                var c = document.getElementById(\"generate_" . $tableName . "_create\");
                var a = document.getElementsByClassName(\"" . $tableName . " create\");
                for (i = 0; i < a.length; i++) a[i].checked = c.checked;
            }

            function " . $tableName . "_GetByID() {
                var c = document.getElementById(\"generate_" . $tableName . "_getByID\");
                var a = document.getElementsByClassName(\"" . $tableName . " getByID\");
                for (i = 0; i < a.length; i++) a[i].checked = c.checked;
            }

            " . $indexFunctions . "

            function " . $tableName . "_GetMultiple() {
                var c = document.getElementById(\"generate_" . $tableName . "_getMultiple\");
                var a = document.getElementsByClassName(\"" . $tableName . " getMultiple\");
                for (i = 0; i < a.length; i++) a[i].checked = c.checked;
            }

            function " . $tableName . "_Update() {
                var c = document.getElementById(\"generate_" . $tableName . "_update\");
                var a = document.getElementsByClassName(\"" . $tableName . " update\");
                for (i = 0; i < a.length; i++) a[i].checked = c.checked;
            }

            function " . $tableName . "_SearchByField() {
                var c = document.getElementById(\"generate_" . $tableName . "_searchByField\");
                var a = document.getElementsByClassName(\"" . $tableName . " searchByField\");
                for (i = 0; i < a.length; i++) a[i].checked = c.checked;
            }

            function " . $tableName . "_Delete() {
                var c = document.getElementById(\"generate_" . $tableName . "_delete\");
                var a = document.getElementsByClassName(\"" . $tableName . " delete\");
                for (i = 0; i < a.length; i++) a[i].checked = c.checked;
            }

            function " . $tableName . "_GetSize() {
                var c = document.getElementById(\"generate_" . $tableName . "_getSize\");
                var a = document.getElementsByClassName(\"" . $tableName . " getSize\");
                for (i = 0; i < a.length; i++) a[i].checked = c.checked;
            }

            function " . $tableName . "_IsEmpty() {
                var c = document.getElementById(\"generate_" . $tableName . "_isEmpty\");
                var a = document.getElementsByClassName(\"" . $tableName . " isEmpty\");
                for (i = 0; i < a.length; i++) a[i].checked = c.checked;
            }

            function " . $tableName . "_Generate() {
                var c = document.getElementById(\"" . $tableName . "_generate\");
                var a = document.getElementsByClassName(\"" . $tableName . "\");
                for (i = 0; i < a.length; i++) a[i].checked = c.checked;
            }

        </script>
    
    ";

    $fullTable .= $str;

}//end foreach table


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

    <?php echo $fullTable; ?>

    <div style="margin-top: 30px; clear:both; float:none;"></div>

    <input style="float: right;" class="button" type="submit" value="Next ->" />
    <a class="button" style="float: left;" href="generateAPIStep0.php"><- Back to Step 0</a>

</form>


</body>


</html>