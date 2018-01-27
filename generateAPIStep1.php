<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="assets/css/w3.css">
    <link rel="stylesheet" type="text/css"  href="assets/css/styles.css">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PaNick Apps API Generator v1</title>
</head>


<body>

<div class="header-panel w3-panel w3-indigo">
    <h1>API Generation - Step 1</h1>
</div>

<div class="w3-container">

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
             echo "<div class='w3-panel w3-red w3-animate-left w3-center'><p>Invalid user level ID.</p></div>";
             break;
        case "UserLevelDeleted":
            echo "<div class='w3-panel w3-green w3-animate-right w3-center'><p>User level deleted.</p></div>";
            break;
        case "UserLevelNotDeleted":
            echo "<div class='w3-panel w3-red w3-animate-left w3-center'><p>Could not delete user level.</p></div>";
            break;
        case "InvalidUserLevelName":
            echo "<div class='w3-panel w3-red w3-animate-left w3-center'><p>Invalid user level name.</p></div>";
            break;
        case "UserLevelCreated":
            echo "<div class='w3-panel w3-green w3-animate-right w3-center'><p>User level created.</p></div>";
            break;
        case "UserLevelNotCreated":
            echo "<div class='w3-panel w3-red w3-animate-left w3-center'><p>Could not create user level.</p></div>";
            break;

    }
}

?>

<h2>Review user levels</h2>


<p>The generation of the API is based on existing user levels for security and authentication. If your system will use additional user types/levels to the ones below, please provide them now.</p>

<table border="1" cellpadding="5">
    <hr/>
    <tr class="w3-black">
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
                $echostr .= "<a class='w3-text-red' onclick='return confirm(\"Are you sure?\");' href='scripts/RemoveUserLevel.php?id=".$row->UserLevelID."&dbName=".$dbName."&dbHostIP=".$dbHostIP."&dbUser=".$dbUsername."&dbPassword=".$dbPassword."'>Remove</a>";
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
    <div>
        <p>User Level Name: <input class="w3-input w3-border shortButton" type="text" maxlength="50" name="addUserLevel_name" /><input style="margin-left: 15px;" type="submit" value="Add" /></p>
    </div>
    <hr/>
</form>


<form name="generateAPIStep1" action="generateAPIStep2.php">
    <input type="hidden" name="dbName" value="<?php echo $dbName;?>" />
    <input type="hidden" name="dbHostIP" value="<?php echo $dbHostIP;?>" />
    <input type="hidden" name="dbUser" value="<?php echo $dbUsername;?>" />
    <input type="hidden" name="dbPassword" value="<?php echo $dbPassword;?>" />

    <a class="w3-button w3-black w3-hover-red" href="generateAPIStep0.php">Back</a>
    <input class="w3-button w3-black w3-hover-green" value="Proceed" type="submit" />

</form>


</div>
</body>


</html>