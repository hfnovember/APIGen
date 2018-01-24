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

    }
}

$conn = new mysqli($dbHostIP, $dbUsername, $dbPassword, $dbName);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);
//Get UserLevels:
$sql_getUserLevels = "SELECT * FROM UserLevels";
$result = $conn->query($sql_getUserLevels);
$userLevels = array();
while ($row = $result->fetch_object()) array_push($userLevels, $row);

?>

<h1>Step 3</h1>

<h2>Generate Custom API endpoints</h2>


<p>Use this step to generate any custom-made endpoints. These endpoints will be appropriately configured to follow the same authentication procedures as the other table-generated calls. These endpoints will be accessible through API/Custom/.</p>



<form name="addNewEndpointForm" action="generateAPIStep3.php" method="post">

    <table cellpadding="5" border="1">
        <tr>
            <th>Endpoint name</th>
            <th>Endpoint access</th>
            <th>Endpoint parameters</th>
        </tr>

        <tr>
            <td><input placeholder="Endpoint name..." title="New endpoint name" type="text" name="endpointName" maxlength="255" /></td>
            <td>
                <table cellpadding="2">

                        <?php

                            foreach ($userLevels as $userLevel) {
                                echo "<tr><td><input type='checkbox' style='border: 1px solid black;' checked='checked' name='generateFor_".$userLevel->UserLevelName."' />".$userLevel->UserLevelName."</td></tr>";
                            }

                            ?>

                </table>

            </td>
            <td>Hint: Separate parameter names using commas: <br><br><input placeholder="Parameters list..." title="Parameters list..." type="text" name="endpointParameters" /></td>
        </tr>

    </table>

    <br>

    <input type="submit" name="submit" value="Add endpoint" class="button green" />

</form>

<br><br><br><br>

<form><!--TODO-->

    <a href="generateAPIStep2.php" class="button red">Back</a>
    <input type="submit" name="submit" value="Proceed" class="button green" />

</form>



</body>


</html>