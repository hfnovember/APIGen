<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="w3.css">
    <link rel="stylesheet" type="text/css"  href="styles.css">
    <title>PaNick Apps API Generator v1</title>
</head>


<body>

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

<h1>Generate PHP Classes</h1>

<form name="generateLoginScriptForm" action="scripts/GeneratePHPClasses.php">

    <p>Please fill in the required information and click on "Generate PHP Classes" to <b>generate classes</b> for your database tables supporting basic <b>CRUD operations</b>.</p>

    <p style="color:blue"><b>Info:</b> PHP classes will be created representing each table and its fields as class members. PHP classes will also support static functions related to CRUD operations directly on the database.</p>

    <p>Database Name: <input type="text" name="dbName" title="Database name" placeholder="Database name" value="<?php echo $pref_dbName; ?>" /></p>
    <p>Database Host IP: <input type="text" name="dbHostIP" title="Database host IP" placeholder="Database host IP" value="<?php echo $pref_dbHostIP; ?>" /></p>
    <p>Database User: <input type="text" name="dbUser" title="Database user" placeholder="Database user" value="<?php echo $pref_dbUser; ?>" /></p>
    <p>Database Password: <input type="text" name="dbPassword" title="Database password" placeholder="Database password" value="<?php echo $pref_dbPass; ?>" /></p>

    <a  class="w3-button w3-black w3-hover-red" href="generateClasses.php">Back</a>
    <input  class="w3-button w3-black w3-hover-green" value="Generate PHP Classes" type="submit" />

</form>


</div>
</body>


</html>