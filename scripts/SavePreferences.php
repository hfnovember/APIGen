<?php

    session_start();

    if (!isset($_GET["dbName"]) || $_GET["dbName"] == "") {
        header("Location: ../preferences.php?status=EmptyDBName"); exit();
    }
    else if (!isset($_GET["dbHostIP"]) || $_GET["dbHostIP"] == "") {
        header("Location: ../preferences.php?status=EmptyDBHostIP"); exit();
    }
    else if (!isset($_GET["dbUser"]) || $_GET["dbUser"] == "") {
        header("Location: ../preferences.php?status=EmptyDBUser"); exit();
    }
    else if (!isset($_GET["dbPassword"]) || $_GET["dbPassword"] == "") {
        header("Location: ../preferences.php?status=EmptyDBPassword"); exit();
    }

    $_SESSION["pref_dbName"] = $_GET["dbName"];
    $_SESSION["pref_dbHostIP"] = $_GET["dbHostIP"];
    $_SESSION["pref_dbUser"] = $_GET["dbUser"];
    $_SESSION["pref_dbPassword"] = $_GET["dbPassword"];

    header("Location: ../preferences.php?status=Saved"); exit();

?>