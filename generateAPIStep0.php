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



?>

<h1>Step 0</h1>

<h2>Provide Database credentials</h2>

<form name="generateAPIStep0" action="generateAPIStep1.php">

    <p>Please fill in the required information and click on "Proceed" to proceed to the next step.</p>

    <p style="color:red"><b>Important Note:</b> Your database should be correctly formatted according to the <a href="specification.html">specification of the API generator.</a> Your database will be checked for consistency.</p>

    <p>Database Name: <input type="text" name="dbName" title="Database name" placeholder="Database name" /></p>
    <p>Database Host IP: <input type="text" name="dbHostIP" title="Database host IP" placeholder="Database host IP" /></p>
    <p>Database User: <input type="text" name="dbUser" title="Database user" placeholder="Database user" /></p>
    <p>Database Password: <input type="text" name="dbPassword" title="Database password" placeholder="Database password" /></p>

    <input style="float: right;" class="button" value="Proceed to Step 1 ->" type="submit" />
    <a style="float: left;" class="button" href="index.php"><- Back to menu</a>

</form>



</body>


</html>