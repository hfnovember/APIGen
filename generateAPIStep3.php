<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" type="text/css"  href="styles.css">
    <title>PaNick Apps API Generator in PHP</title>
</head>


<body>

<?php

session_start();

if (!isset($_SESSION["tempDBName"]) || $_SESSION["tempDBName"] == "") {
    header("Location: ../index.php?status=EmptyDBName"); exit();
}
else if (!isset($_SESSION["tempHostIP"]) || $_SESSION["tempHostIP"] == "") {
    header("Location: ../index.php?status=EmptyDBHostIP"); exit();
}
else if (!isset($_SESSION["tempDBUser"]) || $_SESSION["tempDBUser"] == "") {
    header("Location: ../index.php?status=EmptyDBUser"); exit();
}
else if (!isset($_SESSION["tempDBPassword"]) || $_SESSION["tempDBPassword"] == "") {
    header("Location: ../index.php?status=EmptyDBPassword"); exit();
}

$dbName = $_SESSION["tempDBName"];
$dbHostIP = $_SESSION["tempHostIP"];
$dbUsername = $_SESSION["tempDBUser"];
$dbPassword = $_SESSION["tempDBPassword"];

if (isset($_GET["status"])) {
    switch($_GET["status"]) {
        case "Generated":
            echo "<p class='successCard'>Table-related API endpoints generated successfully.</p>";
            break;
        case "NameInvalid":
            echo "<p class='errorCard'>One or more of the endpoint names you provided is invalid.</p>";
            break;
        case "InconsistentSizes":
            echo "<p class='errorCard'>A technical error has occured (endpoint info sizes are inconsistent).</p>";
            break;
        case "LevelInvalid":
            echo "<p class='errorCard'>One or more of the endpoint user levels you provided is invalid.</p>";
            break;
        case "ParametersInvalid":
            echo "<p class='errorCard'>One or more of the endpoint parameters you provided is invalid.</p>";
            break;

    }
}

$conn = new mysqli($dbHostIP, $dbUsername, $dbPassword, $dbName);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);
//Get UserLevels:
$sql_getUserLevels = "SELECT * FROM UserLevels";
$result = $conn->query($sql_getUserLevels);
$userLevels = array();
while ($row = $result->fetch_object()) array_push($userLevels, $row);

//Names:
if (isset($_SESSION["step3_endpointNames"]) && isset($_POST["endpointName"])) {
    if ($_POST["endpointName"] != "") $_SESSION["step3_endpointNames"] .= $_POST["endpointName"] . "|";
    else {
        $_POST["endpointName"] = " ";
        $_SESSION["step3_endpointNames"] .= $_POST["endpointName"] . "|";
    }
}
else {
    $_SESSION["step3_endpointNames"] = "";
}

//Access rights:
if (isset($_SESSION["step3_endpointAccess"]) && isset($_POST["endpointAccess"])) {
    if ($_POST["endpointAccess"] != "") $_SESSION["step3_endpointAccess"] .= $_POST["endpointAccess"] . "|";
    else {
        $_POST["endpointAccess"] = " ";
        $_SESSION["step3_endpointAccess"] .= $_POST["endpointAccess"] . "|";
    }
}
else {
    $_SESSION["step3_endpointAccess"] = "";
}

//Parameters:
if (isset($_SESSION["step3_endpointParameters"]) && isset($_POST["endpointParameters"])) {
    if ($_POST["endpointParameters"] != "") $_SESSION["step3_endpointParameters"] .= $_POST["endpointParameters"] . "|";
    else {
        $_POST["endpointParameters"] = " ";
        $_SESSION["step3_endpointParameters"] .= $_POST["endpointParameters"] . "|";
    }
}
else {
    $_SESSION["step3_endpointParameters"] = "";
}


?>

<script>

    function checkForUserLevels() {
        var a = document.getElementsByTagName("input");
        var x = document.getElementById("endpointAccess");
        x.value = "";
        for (var i = 0; i < a.length; i++) {
            if (a[i].type === "checkbox") {
                if (a[i].name.indexOf("generateFor_") !== -1 && a[i].checked === true) {
                    x.value += a[i].id + ",";
                }
            }
        }
        x.value = x.value.substring(0, x.value.length - 1);
        console.log(x.value);
    }

    function validate() {
        var x = document.getElementById("endpointName");
        if (x.value === "") {
            alert("Endpoint name cannot be empty.")
            return false;
        }
        else {
            checkForUserLevels();
            return true;
        }
    }

</script>

<h1>Step 3</h1>

<h2>Generate Custom API endpoints</h2>


<p>Use this step to generate any custom-made endpoints. These endpoints will be appropriately configured to follow the same authentication procedures as the other table-generated calls. These endpoints will be accessible through API/Custom/.</p>

<h3>Custom Endpoints</h3>

<table cellpadding="2" border="1">
    <tr>
        <th>Endpoint name</th>
        <th>Endpoint access</th>
        <th>Endpoint parameters</th>
    </tr>
    <?php
        if (isset($_SESSION["step3_endpointNames"]) && isset($_SESSION["step3_endpointAccess"]) && isset($_SESSION["step3_endpointParameters"])) {

            $_SESSION["step3_endpointNames"] = substr($_SESSION["step3_endpointNames"], 0, strlen($_SESSION["step3_endpointNames"]) - 1);
            $_SESSION["step3_endpointAccess"] = substr($_SESSION["step3_endpointAccess"], 0, strlen($_SESSION["step3_endpointAccess"]) - 1);
            $_SESSION["step3_endpointParameters"] = substr($_SESSION["step3_endpointParameters"], 0, strlen($_SESSION["step3_endpointParameters"]) - 1);

            $endpointNames = explode("|", $_SESSION["step3_endpointNames"]);
            $endpointAccess = explode("|", $_SESSION["step3_endpointAccess"]);
            $endpointParameters = explode("|", $_SESSION["step3_endpointParameters"]);
            if (sizeof($endpointNames) > 0) {
                for ($i = 0; $i < sizeof($endpointNames); $i++) {
                    $accesses = explode(",", $endpointAccess[$i]);
                    $userLevelName = "";
                    foreach ($accesses as $access) {
                        foreach ($userLevels as $userLevel) {
                            if ($access == $userLevel->UserLevelID) {
                                $userLevelName .= $userLevel->UserLevelName . ",";
                                break;
                            }
                        }
                    }
                    $userLevelName = substr($userLevelName, 0, strlen($userLevelName) - 1);
                    echo "<tr><td>" . $endpointNames[$i] . "</td><td>" . $userLevelName . "</td><td>" . $endpointParameters[$i] . "</td></tr>";
                }
            }

            $_SESSION["step3_endpointNames"] .= "|";
            $_SESSION["step3_endpointAccess"] .= "|";
            $_SESSION["step3_endpointParameters"] .= "|";


        }
    ?>
</table>

<h3>Add endpoint</h3>

<form name="addNewEndpointForm" action="generateAPIStep3.php" onsubmit="return validate()" method="post">

    <table cellpadding="5" border="1">
        <tr>
            <th>Endpoint name</th>
            <th>Endpoint access</th>
            <th>Endpoint parameters</th>
        </tr>

        <tr>
            <td><input placeholder="Endpoint name..." title="New endpoint name" type="text" id="endpointName" name="endpointName" maxlength="255" /></td>
            <td>
                <table cellpadding="2">

                        <?php

                            foreach ($userLevels as $userLevel) {
                                echo "<tr><td><input onclick='checkForUserLevels()' type='checkbox' style='border: 1px solid black;' checked='checked' id='".$userLevel->UserLevelID."' name='generateFor_".$userLevel->UserLevelName."' />".$userLevel->UserLevelName."</td></tr>";
                            }

                            ?>

                </table>

            </td>
            <td>Hint: Separate parameter names using commas: <br><br><input placeholder="Parameters list..." title="Parameters list..." type="text" name="endpointParameters" /></td>
        </tr>

    </table>

    <br>

    <input type="hidden" id="endpointAccess" name="endpointAccess" />

    <input type="submit" name="submit" value="Add endpoint" class="button green" />

    <script>checkForUserLevels()</script>
</form>

<br><br><br><br>

<form action="scripts/GenerateCustomEndpoints.php" method="post">

    <a href="generateAPIStep2.php" class="button red">Back</a>
    <input type="submit" name="submit" value="Proceed" class="button green" />

</form>



</body>


</html>