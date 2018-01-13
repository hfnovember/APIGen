<?php

    /**
     *  File: CreateDB.php
     *  Date: 13-Jan-2018
     *
     *  This file generates the DBLogin.php file based on the information provided by the user.
     *
     *  Requires:
     *  - dbName as String
     *  - dbHostIP as String
     *  - dbUser as String
     *  - dbPassword as String
     */

    include_once("GeneratorUtils.php");

    $fileName = "DBLogin.php";

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

    $fileStream = getGeneratorHeaderNoTable($_GET["dbName"], $fileName, "This file is used to log in to the database.");
    $fileStream = $fileStream .
	"
//Logs in to the database and returns a connection object.
function dbLogin() {
    \$conn = new mysqli(\"".$_GET["dbHostIP"]."\", \"".$_GET["dbUser"]."\", \"".$_GET["dbPassword"]."\", \"".$_GET["dbName"]."\");
    if (\$conn->connect_error) die(\"Connection failed: \" . \$conn->connect_error);
    return \$conn;
}\r\n
	";

    writeToFile("../Generated/Scripts/", $fileName, $fileStream);
    $result = createNewDatabase($_GET["dbHostIP"], $_GET["dbUser"], $_GET["dbPassword"], $_GET["dbName"]);

    switch ($result) {
        case 1:
            header("Location: ../index.php?status=DBCreated&dbName=" . $_GET["dbName"]);
            exit();
            break;
        case 2:
            header("Location: ../createDB.php?status=CantCreateDB&dbName=" . $_GET["dbName"]);
            exit();
            break;
        case 3:
            header("Location: ../createDB.php?status=NoDBLoginScriptFound");
            exit();
            break;
    }


?>