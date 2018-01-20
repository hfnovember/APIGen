<?php


    if (!isset($_GET["dbName"]) || $_GET["dbName"] == "") {
        header("Location: ../index.php?status=EmptyDBName"); exit();
    }
    else if (!isset($_GET["dbHostIP"]) || $_GET["dbHostIP"] == "") {
        header("Location: ../index.php?status=EmptyDBHostIP"); exit();
    }
    else if (!isset($_GET["dbUser"]) || $_GET["dbUser"] == "") {
        header("Location: ../index.php?status=EmptyDBUser"); exit();
    }
    else if (!isset($_GET["dbPassword"]) || $_GET["dbPassword"] == "") {
        header("Location: ../index.php?status=EmptyDBPassword"); exit();
    }


    $dbName = $_GET["dbName"];
    $dbHostIP = $_GET["dbHostIP"];
    $dbUsername = $_GET["dbUser"];
    $dbPassword = $_GET["dbPassword"];
    $userlevelID = $_GET["id"];

    if (!isset($_GET["id"]) || $_GET["id"] == "" || $_GET["id"] == "1" || $_GET["id"] == "4") {
        header("Location: ../generateAPIStep1.php?status=InvalidUserLevelID&dbName=" . $dbName . "&dbHostIP=" . $dbHostIP . "&dbUser=" . $dbUsername . "&dbPassword=" . $dbPassword);
        exit();
    }


    $conn = new mysqli($dbHostIP, $dbUsername, $dbPassword, $dbName);
    if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);
    $sql = "DELETE FROM UserLevels WHERE UserLevelID = " . $userlevelID;
    $result = $conn->query($sql);
    if ($result === TRUE) {
        header("Location: ../generateAPIStep1.php?status=UserLevelDeleted&dbName=" . $dbName . "&dbHostIP=" . $dbHostIP . "&dbUser=" . $dbUsername . "&dbPassword=" . $dbPassword); exit();
    }
    else {
        header("Location: ../generateAPIStep1.php?status=UserLevelNotDeleted&dbName=" . $dbName . "&dbHostIP=" . $dbHostIP . "&dbUser=" . $dbUsername . "&dbPassword=" . $dbPassword); exit();
    }



?>