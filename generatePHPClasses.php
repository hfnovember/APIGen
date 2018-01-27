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

<div class="w3-panel header-panel w3-indigo">
    <h1>Generate PHP Classes</h1>
</div>

<div class="w3-container">

<?php

session_start();

$pref_dbName = "";
$pref_dbHostIP = "";
$pref_dbUser = "";
$pref_dbPass = "";

if (isset($_SESSION["pref_dbName"])) $pref_dbName = $_SESSION["pref_dbName"];
if (isset($_SESSION["pref_dbHostIP"])) $pref_dbHostIP = $_SESSION["pref_dbHostIP"];
if (isset($_SESSION["pref_dbUser"])) $pref_dbUser = $_SESSION["pref_dbUser"];
if (isset($_SESSION["pref_dbPassword"])) $pref_dbPass = $_SESSION["pref_dbPassword"];

?>

<form name="generateLoginScriptForm" action="scripts/GeneratePHPClasses.php">

    <p>Please fill in the required information and click on "Generate PHP Classes" to generate entity classes for your database tables as well as static methods that are utilized by the API and
        can be used to implement your own custom API endpoints. The generated files can be found in Generated/Scripts/PHP.</p>

    <p>Database Name: <input class="w3-input w3-border" type="text" name="dbName" title="Database name" placeholder="Database name" value="<?php echo $pref_dbName; ?>" /></p>
    <p>Database Host IP: <input class="w3-input w3-border" type="text" name="dbHostIP" title="Database host IP" placeholder="Database host IP" value="<?php echo $pref_dbHostIP; ?>" /></p>
    <p>Database User: <input class="w3-input w3-border" type="text" name="dbUser" title="Database user" placeholder="Database user" value="<?php echo $pref_dbUser; ?>" /></p>
    <p>Database Password: <input class="w3-input w3-border" type="text" name="dbPassword" title="Database password" placeholder="Database password" value="<?php echo $pref_dbPass; ?>" /></p>

    <a  class="w3-button w3-black w3-hover-red" href="generateClasses.php">Back</a>
    <input  class="w3-button w3-black w3-hover-green" value="Generate PHP Classes" type="submit" />

</form>


</div>
</body>


</html>