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
         case "Saved":
             echo "<p class='successCard'>Your preferences have been saved.</p>";
             break;
        case "EmptyDBName":
            echo "<p class='errorCard'>Database name cannot be empty.</p>";
            break;
        case "EmptyDBHostIP":
            echo "<p class='errorCard'>Database host IP cannot be empty.</p>";
            break;
        case "EmptyDBUser":
            echo "<p class='errorCard'>Database user cannot be empty.</p>";
            break;
        case "EmptyDBPassword":
            echo "<p class='errorCard'>Database password cannot be empty.</p>";
            break;
        case "NoDBLoginScriptFound":
            echo "<p class='errorCard'>DBLogin script was not found.</p>";
            break;

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

<h1>Preferences</h1>

<form name="preferencesForm" action="scripts/SavePreferences.php">

    <h3>Database Preset</h3>

    <p style="color: blue;"><b>Info: </b>Database presets will be lost when your browser is closed.</p>

    <p>Database Name: <input type="text" name="dbName" title="Database name" placeholder="Database name" value="<?php echo $pref_dbName; ?>" /></p>
    <p>Database Host IP: <input type="text" name="dbHostIP" title="Database host IP" placeholder="Database host IP" value="<?php echo $pref_dbHostIP; ?>" /></p>
    <p>Database User: <input type="text" name="dbUser" title="Database user" placeholder="Database user" value="<?php echo $pref_dbUser; ?>" /></p>
    <p>Database Password: <input type="text" name="dbPassword" title="Database password" placeholder="Database password" value="<?php echo $pref_dbPass; ?>" /></p>

    <a class="button red" href="index.php">Back to menu</a>
    <input class="button green" value="Save" type="submit" />


</form>



</body>


</html>