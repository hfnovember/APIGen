<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" type="text/css"  href="styles.css">
    <title>PaNick Apps API Generator in PHP</title>
</head>


<body>

<?php


if (isset($_GET["status"])) {
    switch($_GET["status"]) {
        /* case "EmptyDBName":
             echo "<p class='errorCard'>Database name cannot be empty.</p>";
             break; */



    }
}

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

<h1>Generate Java Classes</h1>

<form name="generateLoginScriptForm" action="scripts/GenerateJavaClasses.php">

    <p>Please fill in the required information and click on "Generate Java Classes" to <b>generate classes</b> for your database tables supporting basic <b>CRUD operations</b>.</p>

    <p style="color:blue"><b>Info:</b> Java classes will be created representing each table and its fields as class members.</p>

    <p>Database Name: <input type="text" name="dbName" title="Database name" placeholder="Database name" value="<?php echo $pref_dbName; ?>" /></p>
    <p>Database Host IP: <input type="text" name="dbHostIP" title="Database host IP" placeholder="Database host IP" value="<?php echo $pref_dbHostIP; ?>" /></p>
    <p>Database User: <input type="text" name="dbUser" title="Database user" placeholder="Database user" value="<?php echo $pref_dbUser; ?>" /></p>
    <p>Database Password: <input type="text" name="dbPassword" title="Database password" placeholder="Database password" value="<?php echo $pref_dbPass; ?>" /></p>

    <a class="button red" href="generateClasses.php">Back</a>
    <input class="button green" value="Generate Java Classes" type="submit" />

</form>



</body>


</html>