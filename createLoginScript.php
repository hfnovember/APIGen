<!DOCTYPE html><html lang="en"><head>    <meta charset="UTF-8">    <link rel="stylesheet" href="w3.css">    <link rel="stylesheet" type="text/css"  href="styles.css">    <title>PaNick Apps API Generator v1</title></head><body><div class="w3-container"><?php    session_start();    $pref_dbName = "";    $pref_dbHostIP = "";    $pref_dbUser = "";    $pref_dbPass = "";    if (isset($_SESSION["pref_dbName"])) $pref_dbName = $_SESSION["pref_dbName"];    if (isset($_SESSION["pref_dbHostIP"])) $pref_dbHostIP = $_SESSION["pref_dbHostIP"];    if (isset($_SESSION["pref_dbUser"])) $pref_dbUser = $_SESSION["pref_dbUser"];    if (isset($_SESSION["pref_dbPassword"])) $pref_dbPass = $_SESSION["pref_dbPassword"];?><h1>Create script for existing database</h1><form name="generateLoginScriptForm" action="scripts/CreateLoginScript.php">    <p>Please fill in the required information and click on "Generated Login Script" to <b>generate a new DBLogin.php script</b> for your database.</p>    <p style="color:red"><b>Important Note:</b> Your database should be correctly formatted according to the <a href="specification.html">specification of the API generator.</a></p>    <p>Database Name: <input type="text" name="dbName" title="Database name" placeholder="Database name" value="<?php echo $pref_dbName; ?>" /></p>    <p>Database Host IP: <input type="text" name="dbHostIP" title="Database host IP" placeholder="Database host IP" value="<?php echo $pref_dbHostIP; ?>" /></p>    <p>Database User: <input type="text" name="dbUser" title="Database user" placeholder="Database user" value="<?php echo $pref_dbUser; ?>" /></p>    <p>Database Password: <input type="text" name="dbPassword" title="Database password" placeholder="Database password" value="<?php echo $pref_dbPass; ?>" /></p>    <a class="w3-button w3-black w3-hover-red" href="index.php">Back to menu</a>    <input class="w3-button w3-black w3-hover-green" value="Generate Login Script" type="submit" /></form></div></body></html>