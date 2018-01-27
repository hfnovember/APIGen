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
    <h1>Preferences</h1>
</div>

<div class="w3-container">

<?php


if (isset($_GET["status"])) {
    switch($_GET["status"]) {
         case "Saved":
             echo "<div class='w3-panel w3-green w3-animate-right w3-center'><p>Your preferences have been saved.</p></div>";
             break;
        case "EmptyDBName":
            echo "<div class='w3-panel w3-red w3-animate-left w3-center'><p>Database name cannot be empty.</p></div>";
            break;
        case "EmptyDBHostIP":
            echo "<div class='w3-panel w3-red w3-animate-left w3-center'><p>Database host IP cannot be empty.</p></div>";
            break;
        case "EmptyDBUser":
            echo "<div class='w3-panel w3-red w3-animate-left w3-center'><p>Database user cannot be empty.</p></div>";
            break;
        case "EmptyDBPassword":
            echo "<div class='w3-panel w3-red w3-animate-left w3-center'><p>Database password cannot be empty.</p></div>";
            break;
        case "NoDBLoginScriptFound":
            echo "<div class='w3-panel ww3-red w3-animate-left w3-center'><p>DBLogin script was not found.</p></div>";
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

<form name="preferencesForm" action="scripts/SavePreferences.php">

    <h3>Database Preset</h3>

    <p style="color: blue;"><b>Info: </b>Database presets will be lost when your browser is closed.</p>

    <p>Database Name: <input type="text" name="dbName" title="Database name" placeholder="Database name" value="<?php echo $pref_dbName; ?>" /></p>
    <p>Database Host IP: <input type="text" name="dbHostIP" title="Database host IP" placeholder="Database host IP" value="<?php echo $pref_dbHostIP; ?>" /></p>
    <p>Database User: <input type="text" name="dbUser" title="Database user" placeholder="Database user" value="<?php echo $pref_dbUser; ?>" /></p>
    <p>Database Password: <input type="text" name="dbPassword" title="Database password" placeholder="Database password" value="<?php echo $pref_dbPass; ?>" /></p>

    <a class="w3-button w3-black w3-hover-red" href="index.php">Back to menu</a>
    <input class="w3-button w3-black w3-hover-green" value="Save" type="submit" />


</form>


</div>
</body>


</html>