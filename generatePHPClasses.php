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

<h1>Generate PHP Classes</h1>

<form name="generateLoginScriptForm" action="scripts/GeneratePHPClasses.php">

    <p>Please fill in the required information and click on "Generate PHP Classes" to <b>generate classes</b> for your database tables supporting basic <b>CRUD operations</b>.</p>

    <p style="color:blue"><b>Info:</b> Basic CRUD operations are: Create, Retrieve, Update and Delete. PHP classes will be created representing each table and these basic operations.</p>

    <p>Database Name: <input type="text" name="dbName" title="Database name" placeholder="Database name" /></p>
    <p>Database Host IP: <input type="text" name="dbHostIP" title="Database host IP" placeholder="Database host IP" /></p>
    <p>Database User: <input type="text" name="dbUser" title="Database user" placeholder="Database user" /></p>
    <p>Database Password: <input type="text" name="dbPassword" title="Database password" placeholder="Database password" /></p>

    <input class="button" value="Generate PHP Classes" type="submit" />
    <a class="button" href="generateClasses.php">Back</a>

</form>



</body>


</html>