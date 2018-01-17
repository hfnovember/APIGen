<?php

    if (!isset($_GET["dbName"]) || $_GET["dbName"] == "") {
        header("Location: ../createDB.php?status=EmptyDBName"); exit();
    }
    else if (!isset($_GET["dbHostIP"]) || $_GET["dbHostIP"] == "") {
        header("Location: ../createDB.php?status=EmptyDBHostIP"); exit();
    }
    else if (!isset($_GET["dbUser"]) || $_GET["dbUser"] == "") {
        header("Location: ../createDB.php?status=EmptyDBUser"); exit();
    }
    else if (!isset($_GET["dbPassword"]) || $_GET["dbPassword"] == "") {
        header("Location: ../createDB.php?status=EmptyDBPassword"); exit();
    }

    include_once("GeneratorUtils.php");
    $fileStream = getGeneratorHeaderNoTable($_GET["dbName"], "DBLogin.php", "This file is used to log in to the database.");
    $fileStream = $fileStream .
        "
    //Logs in to the database and returns a connection object.
    function dbLogin() {
        \$conn = new mysqli(\"".$_GET["dbHostIP"]."\", \"".$_GET["dbUser"]."\", \"".$_GET["dbPassword"]."\", \"".$_GET["dbName"]."\");
        if (\$conn->connect_error) die(\"Connection failed: \" . \$conn->connect_error);
        return \$conn;
    }
    
    //Provides a fix where PHP false values are converted to NULL objects that are invalid for MySQL queries.
    function booleanFix(\$value) {
        if (\$value === TRUE) return 1;
        else if (\$value === FALSE) return 0;
        else return \$value;
    }\r\n
        ";
    writeToFile("../Generated/Scripts/", "DBLogin.php", $fileStream);

    header("Location: ../index.php?status=DBLoginCreated");

?>