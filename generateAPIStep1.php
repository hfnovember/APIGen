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

if (isset($_GET["status"])) {
    switch($_GET["status"]) {
         case "InvalidUserLevelID":
             echo "<p class='errorCard'>Invalid user level ID.</p>";
             break;
        case "UserLevelDeleted":
            echo "<p class='successCard'>User level deleted.</p>";
            break;
        case "UserLevelNotDeleted":
            echo "<p class='errorCard'>Could not delete user level.</p>";
            break;
        case "InvalidUserLevelName":
            echo "<p class='errorCard'>Invalid user level name.</p>";
            break;
        case "UserLevelCreated":
            echo "<p class='successCard'>User level created.</p>";
            break;
        case "UserLevelNotCreated":
            echo "<p class='errorCard'>Could not create user level.</p>";
            break;

    }
}

?>

<h1>Step 1</h1>

<h2>Review user levels</h2>


<p>The generation of the API is based on existing user levels for security and authentication. If your system will use additional user types/levels to the ones below, please provide them now.</p>

<table border="1" cellpadding="5">
    <hr/>
    <tr>
       <th>Existing User Levels</th>
        <th></th>
    </tr>

    <?php

        $conn = new mysqli($dbHostIP, $dbUsername, $dbPassword, $dbName);
        if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);
        $sql = "SELECT * FROM UserLevels";
        $result = $conn->query($sql);
        while ($row = $result->fetch_object()) {
            $echostr = "<tr><td>" . $row->UserLevelName . "</td><td>";
            if ($row->UserLevelName != "Administrator" && $row->UserLevelName != "Public")
                $echostr .= "<a onclick='return confirm(\"Are you sure?\");' href='scripts/RemoveUserLevel.php?id=".$row->UserLevelID."&dbName=".$dbName."&dbHostIP=".$dbHostIP."&dbUser=".$dbUsername."&dbPassword=".$dbPassword."'>Remove</a>";
            $echostr .= "</td></tr>";
            echo $echostr;
        }

    ?>

</table>


<form name="addNewUserForm" action="scripts/AddUserLevel.php">
    <hr/>
    <input type="hidden" name="dbName" value="<?php echo $dbName;?>" />
    <input type="hidden" name="dbHostIP" value="<?php echo $dbHostIP;?>" />
    <input type="hidden" name="dbUser" value="<?php echo $dbUsername;?>" />
    <input type="hidden" name="dbPassword" value="<?php echo $dbPassword;?>" />
    <h3>Add new User Level</h3>
    <p>User Level Name: <input type="text" maxlength="50" name="addUserLevel_name" /><input style="margin-left: 15px;" type="submit" value="Add" /></p>
    <hr/>
</form>


<form name="generateAPIStep1" action="generateAPIStep2.php">
    <input type="hidden" name="dbName" value="<?php echo $dbName;?>" />
    <input type="hidden" name="dbHostIP" value="<?php echo $dbHostIP;?>" />
    <input type="hidden" name="dbUser" value="<?php echo $dbUsername;?>" />
    <input type="hidden" name="dbPassword" value="<?php echo $dbPassword;?>" />

    <a class="button red" href="generateAPIStep0.php">Back</a>
    <input class="button green" value="Proceed" type="submit" />

</form>



</body>


</html>