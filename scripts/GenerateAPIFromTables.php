<?php

    session_start();

    if (!isset($_SESSION["tempDBName"]) || $_SESSION["tempDBName"] == "") {
        header("Location: ../index.php?status=EmptyDBName"); exit();
    }
    else if (!isset($_SESSION["tempHostIP"]) || $_SESSION["tempHostIP"] == "") {
        header("Location: ../index.php?status=EmptyDBHostIP"); exit();
    }
    else if (!isset($_SESSION["tempDBUser"]) || $_SESSION["tempDBUser"] == "") {
        header("Location: ../index.php?status=EmptyDBUser"); exit();
    }
    else if (!isset($_SESSION["tempDBPassword"]) || $_SESSION["tempDBPassword"] == "") {
        header("Location: ../index.php?status=EmptyDBPassword"); exit();
    }

    $dbName = $_SESSION["tempDBName"];
    $dbHostIP = $_SESSION["tempHostIP"];
    $dbUsername = $_SESSION["tempDBUser"];
    $dbPassword = $_SESSION["tempDBPassword"];

    session_unset();

    $conn = new mysqli($dbHostIP, $dbUsername, $dbPassword, $dbName);
    if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

    //Get tables:
    $sql_getTables = "SHOW TABLES";
    $result = $conn->query($sql_getTables);
    $tableNames = array();
    while ($row = $result->fetch_array()) array_push($tableNames, $row[0]);

    //Get UserLevels:
    $sql_getUserLevels = "SELECT * FROM UserLevels";
    $result = $conn->query($sql_getUserLevels);
    $userLevels = array();
    while ($row = $result->fetch_object()) array_push($userLevels, $row);

    include_once("GeneratorUtils.php");

    foreach ($tableNames as $table) {

        if (isset($_POST[$table . "_generate"])) {

            if (!isset($_POST["url_" . $table]) || $_POST["url_" . $table] == "") {
                $previous = "javascript:history.go(-1)";
                if(isset($_SERVER['HTTP_REFERER'])) $previous = $_SERVER['HTTP_REFERER'];
                header("Location: " . $_SERVER["HTTP_REFERER"] . "?status=BaseURLError&table=" . $table); exit();
            }//end if url not found
            $url = $_POST["url_" . $table];

            //Get fields for this table:
            $sql_fields = "DESCRIBE " . $table;
            $result = $conn->query($sql_fields);
            $allFields = array();
            $uniqueFields = array();
            while ($row = $result->fetch_object()) {
                array_push($allFields, $row);
                if ($row->Key == "UNI") array_push($uniqueFields, $row);
            }//end while

            //Endpoint "CREATE":
            if (isset($_POST["generate_" . $table . "_create"])) {

                $endpoint_CreateCode = "";

                foreach ($userLevels as $userLevel) {
                    if (isset($_POST["generate_" . $table . "_" . $userLevel->UserLevelName])) {
                        //TODO: Generate code for this API endpoint for the current userlevel where appropriate.
                    }
                }//end foreach userlevel



            }//end if create endpoint


        }//end if generate table is set

    }//end foreach table


    //TODO: Proceed to generateAPIStep2.php (Creating custom API endpoints)


?>