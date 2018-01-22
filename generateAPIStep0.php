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

<h1>Step 0</h1>

<h2>Provide Database credentials</h2>

<form name="generateAPIStep0" action="generateAPIStep1.php">

    <p>Please fill in the required information and click on "Proceed" to proceed to the next step.</p>

    <p style="color:red"><b>Important Note:</b> Your database should be correctly formatted according to the <a href="specification.html">specification of the API generator.</a> Your database will be checked for consistency.</p>

    <p>Database Name: <input type="text" name="dbName" title="Database name" placeholder="Database name" value="<?php echo $pref_dbName; ?>" /></p>
    <p>Database Host IP: <input type="text" name="dbHostIP" title="Database host IP" placeholder="Database host IP" value="<?php echo $pref_dbHostIP; ?>" /></p>
    <p>Database User: <input type="text" name="dbUser" title="Database user" placeholder="Database user" value="<?php echo $pref_dbUser; ?>" /></p>
    <p>Database Password: <input type="text" name="dbPassword" title="Database password" placeholder="Database password" value="<?php echo $pref_dbPass; ?>" /></p>

    <a class="button red" href="index.php">Back to menu</a>
    <input class="button green" value="Proceed" type="submit" />


</form>



</body>


</html>