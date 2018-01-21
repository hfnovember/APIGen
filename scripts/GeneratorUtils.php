<?php

    date_default_timezone_set("Europe/Athens");

    /**
     * Creates a file header based on given information.
     * @param $dbName (String)
     * @param $fileName (String)
     * @param $tableName (String)
     * @param $description (String)
     * @return string
     */
    function getGeneratorHeader($dbName, $fileName, $tableName, $description) {

        if ($description == "") $description = "N/A";

        $outputString = "
/**********************************************************************************/
/*** THIS FILE HAS BEEN AUTOMATICALLY GENERATED BY THE PANICKAPPS API GENERATOR ***/\r\n
/*                It is HIGHLY suggested that you do not edit this file.          */\r\n
//  DATABASE:     " . $dbName . "
//  FILE:         " . $fileName . "
//  TABLE:        " . $tableName . "
//  DATETIME:     " . date("Y-m-d h:i:sa", time()) . "
//  DESCRIPTION:  " . $description . "\r\n
/**********************************************************************************/
			\r\n\r\n\r\n";

        return $outputString;
    }//end getGeneratorHeader()

    /**
     * Creates a file header based on given information.
     * @param $dbName (String)
     * @param $fileName (String)
     * @param $description (String)
     * @return string
     */
    function getGeneratorHeaderNoTable($dbName, $fileName, $description) {

        if ($description == "") $description = "N/A";

        $outputString = "
/**********************************************************************************/
/*** THIS FILE HAS BEEN AUTOMATICALLY GENERATED BY THE PANICKAPPS API GENERATOR ***/\r\n
/*                It is HIGHLY suggested that you do not edit this file.          */\r\n
//  DATABASE:     " . $dbName . "
//  FILE:         " . $fileName . "
//  DATETIME:     " . date("Y-m-d h:i:sa", time()) . "
//  DESCRIPTION:  " . $description . "\r\n
/**********************************************************************************/
            \r\n\r\n\r\n";

        return $outputString;
    }//end getGeneratorHeader()



    /**
* Writes a given string of text into a given PHP file in a given filepath.
     * @param $filePath (String)
     * @param $fileName (String)
     * @param $text (String)
     */
    function writeToPHPFile($filePath, $fileName, $text) {
        if (!is_dir($filePath)) mkdir( $filePath, 0777, true );
        $file = fopen($filePath . $fileName, "w") or die("Unable to open file!");
        fwrite($file, "<?php\r\n\r\n" . $text . "\r\n\r\n?>");
        fclose($file);
    }//end writeToPHPFile()

    /**
     * Writes a given string of text into a given PHP file in a given filepath.
     * @param $filePath (String)
     * @param $fileName (String)
     * @param $text (String)
     */
    function writeToJavaFile($filePath, $fileName, $text) {
        if (!is_dir($filePath)) mkdir( $filePath, 0777, true );
        $file = fopen($filePath . $fileName, "w") or die("Unable to open file!");
        fwrite($file, "\r\n\r\n" . $text . "\r\n\r\n");
        fclose($file);
    }//end writeToJavaFile()


/**
 * @param $dbHostIP
 * @param $dbUsername
 * @param $dbPassword
 * @param $dbName
 * @return int
 */
    function createNewDatabase($dbHostIP, $dbUsername, $dbPassword, $dbName) {
        if (is_file("../Generated/Scripts/DBLogin.php")) {

            $conn = new mysqli($dbHostIP, $dbUsername, $dbPassword);
            if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

            $sql = "CREATE DATABASE " . $dbName;
            if ($conn->query($sql) === TRUE) {

                $conn2 = new mysqli($dbHostIP, $dbUsername, $dbPassword, $dbName);
                if ($conn2->connect_error) die("Connection failed: " . $conn2->connect_error);

                $sqlCreateTableUsers = "CREATE TABLE Users (
                  UserID INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                  Username VARCHAR(100) NOT NULL UNIQUE,
                  Password VARCHAR(255) NOT NULL,
                  UserLevelID INT UNSIGNED NOT NULL
                )";

                $sqlCreateTableUserLevels = "CREATE TABLE UserLevels (
                  UserLevelID INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                  UserLevelName VARCHAR(255) NOT NULL UNIQUE
                )";

                $sqlInsertDefaultUserLevels = "INSERT INTO UserLevels (UserLevelName) VALUES (\"Administrator\"), (\"Manager\"), (\"User\"), (\"Public\");";
                $sqlCreateTableSession = "CREATE TABLE Sessions (
                  SessionID VARCHAR(255) NOT NULL PRIMARY KEY,
                  UserID INT UNSIGNED NOT NULL,
                  InitiatedOn INT UNSIGNED NOT NULL,
                  FinalizedOn INT UNSIGNED,
                  ClientIPAddress VARCHAR(255) NOT NULL
                )";

                if ($conn2->query($sqlCreateTableUsers) === TRUE &&
                    $conn2->query($sqlCreateTableUserLevels) === TRUE &&
                    $conn2->query($sqlInsertDefaultUserLevels) === TRUE &&
                    $conn2->query($sqlCreateTableSession) === TRUE) {

                    $conn2->close();
                    return 1;
                }

                else {
                    $conn2->close();
                    return 2;
                }
            }
            else {
                $conn->close();
                return 2;
            }
        }
        else return 3;
    }//end createNewDatabase()

    function createSessionCreationScript($dbHostIP, $dbUsername, $dbPassword, $dbName) {
        $conn = new mysqli($dbHostIP, $dbUsername, $dbPassword, $dbName);
        if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

        $fileLocation = "../Generated/API/Login/";
        $fileName = "index.php";

        $fileText = getGeneratorHeaderNoTable($dbName, "Login", "Logs the user into the system.") .

            "
        
        
    function generateRandomString(\$length = 5) {
        \$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        \$charactersLength = strlen(\$characters);
        \$randomString = '';
        for (\$i = 0; \$i < \$length; \$i++) \$randomString .= \$characters[rand(0, \$charactersLength - 1)];
        return \$randomString;
    }
    
    function getRealIPAddress() {
        if (!empty(\$_SERVER['HTTP_CLIENT_IP'])) \$ip=\$_SERVER['HTTP_CLIENT_IP'];
        elseif (!empty(\$_SERVER['HTTP_X_FORWARDED_FOR'])) \$ip=\$_SERVER['HTTP_X_FORWARDED_FOR'];
        else \$ip=\$_SERVER['REMOTE_ADDR'];
        return \$ip;
    }//end getRealIPAddress()
                
    //Statuses:
    const STATUS_ERROR = \"Error\";
    const STATUS_OK = \"OK\";

    //Titles/Messages:
    const STATUS = \"Status\";
    const TITLE = \"Title\";
    const MESSAGE = \"Message\";
    const DATA = \"Data\";
    const SESSIONID = \"SessionID\";
    const INVALID_PARAMS_TITLE = \"Invalid Parameters\";
    const INVALID_PARAMS_MESSAGE = \"Invalid parameters. Expected Parameters: Username (String), Password (String).\";
    const TECHNICAL_ERROR_TITLE = \"Technical Error\";
    const TECHNICAL_ERROR_MESSAGE = \"A technical error has occured. Please consult the system's administrator.\";
    const AUTHORIZATION_ERROR_TITLE = \"Authorization Error\";
    const AUTHORIZATION_ERROR_MESSAGE = \"You are not authorized to access this procedure. If you think you should be able to do so, please consult your system's administrator.\";
    const LOGIN_SUCCESS_TITLE = \"Login Success.\";
    const LOGIN_SUCCESS_MESSAGE = \"You have successfully logged in.\";
    const LOGIN_ERROR_TITLE = \"Login Failed.\";
    const LOGIN_ERROR_MESSAGE = \"Failed to log in.\";

    //JSON returns:
    \$JSON_INVALID_PARAMS = array(STATUS => STATUS_ERROR, TITLE => INVALID_PARAMS_TITLE, MESSAGE => INVALID_PARAMS_MESSAGE);
    \$JSON_TECHNICAL_ERROR = array(STATUS => STATUS_ERROR, TITLE => TECHNICAL_ERROR_TITLE, MESSAGE => TECHNICAL_ERROR_MESSAGE);
    \$JSON_AUTHORIZATION_ERROR = array(STATUS => STATUS_ERROR, TITLE => AUTHORIZATION_ERROR_TITLE, MESSAGE => AUTHORIZATION_ERROR_MESSAGE);
    \$JSON_LOGIN_SUCCESS = array(STATUS => STATUS_OK, TITLE => LOGIN_SUCCESS_TITLE, MESSAGE => LOGIN_SUCCESS_MESSAGE);
    \$JSON_LOGIN_ERROR = array(STATUS => STATUS_ERROR, TITLE => LOGIN_ERROR_TITLE, MESSAGE => LOGIN_ERROR_MESSAGE);
    
    if (!isset(\$_POST[\"Username\"]) || \$_POST[\"Username\"] == \"\") die(json_encode(\$JSON_INVALID_PARAMS));
    if (!isset(\$_POST[\"Password\"]) || \$_POST[\"Password\"] == \"\") die(json_encode(\$JSON_INVALID_PARAMS));
    
    include_once(\"../../Scripts/DBLogin.php\");
    \$conn = dbLogin();
    \$username = \$_POST[\"Username\"];
    \$password = \$_POST[\"Password\"];
    
    \$sql = \"SELECT * FROM Users WHERE Username = \\\"\".\$username.\"\\\" AND Password = \\\"\".\$password.\"\\\"\";
    \$result = \$conn->query(\$sql);
    if (\$result->num_rows > 0) {
        \$currentUser = \$result->fetch_object();
        
        \$sql = \"SELECT * FROM Sessions WHERE UserID = \" . \$currentUser->UserID;
        \$result = \$conn->query(\$sql);
        if (\$result->num_rows > 0) {
            \$session = \$result->fetch_object();
            \$sessionID = \$session->SessionID;
            \$successResponse = array(STATUS => STATUS_OK, TITLE => LOGIN_SUCCESS_TITLE, MESSAGE => LOGIN_SUCCESS_MESSAGE, SESSIONID => \"\$sessionID\");
            echo json_encode(\$successResponse);
            exit();
        }
        else {
            \$newSessionID = md5(time() . \$currentUser->Username . generateRandomString(5)); //MD5 Hash will be: Timestamp + Username + generateRandomString(5)
            \$sql = \"INSERT INTO Sessions (SessionID, UserID, InitiatedOn, ClientIPAddress) VALUES (\\\"\".\$newSessionID.\"\\\", \".\$currentUser->UserID.\", \".time().\", \\\"\".getRealIPAddress().\"\\\")\";
            \$result = \$conn->query(\$sql);
            if (\$result === TRUE) {
                \$successResponse = array(STATUS => STATUS_OK, TITLE => LOGIN_SUCCESS_TITLE, MESSAGE => LOGIN_SUCCESS_MESSAGE, SESSIONID => \"\$newSessionID\");
                echo json_encode(\$successResponse);
            }
            else echo json_encode(\$JSON_TECHNICAL_ERROR); exit();
        }
    }
    else echo json_encode(\$JSON_LOGIN_ERROR); exit();
            
        ";

        writeToPHPFile($fileLocation, $fileName, $fileText);

    }//end createSessionCreationScript()

    function createSessionDestructionScript($dbHostIP, $dbUsername, $dbPassword, $dbName) {
        $conn = new mysqli($dbHostIP, $dbUsername, $dbPassword, $dbName);
        if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

        $fileLocation = "../Generated/API/Logout/";
        $fileName = "index.php";

        $fileText = getGeneratorHeaderNoTable($dbName, "Logout", "Logs the user out of the system.") .

            "
        
    //Locals:
    const ENDPOINT_NAME = \"\";
    const ENDPOINT_SAMPLE_CALL = \"API/\"; //TODO Endpoint Sample call
                
    //Statuses:
    const STATUS_ERROR = \"Error\";
    const STATUS_OK = \"OK\";

    //Titles/Messages:
    const STATUS = \"Status\";
    const TITLE = \"Title\";
    const MESSAGE = \"Message\";
    const DATA = \"Data\";
    const SESSIONID = \"SessionID\";
    const INVALID_PARAMS_TITLE = \"Invalid Parameters\";
    const INVALID_PARAMS_MESSAGE = \"Invalid parameters. Expected Parameters: Username (String), Password (String).\";
    const TECHNICAL_ERROR_TITLE = \"Technical Error\";
    const TECHNICAL_ERROR_MESSAGE = \"A technical error has occured. Please consult the system's administrator.\";
    const AUTHORIZATION_ERROR_TITLE = \"Authorization Error\";
    const AUTHORIZATION_ERROR_MESSAGE = \"You are not authorized to access this procedure. If you think you should be able to do so, please consult your system's administrator.\";
    const LOGOUT_SUCCESS_TITLE = \"Logout Success.\";
    const LOGOUT_SUCCESS_MESSAGE = \"You have successfully logged out.\";
    const LOGOUT_ERROR_TITLE = \"Logout Failed.\";
    const LOGOUT_ERROR_MESSAGE = \"Failed to log out.\";

    //JSON returns:
    \$JSON_INVALID_PARAMS = array(STATUS => STATUS_ERROR, TITLE => INVALID_PARAMS_TITLE, MESSAGE => INVALID_PARAMS_MESSAGE);
    \$JSON_TECHNICAL_ERROR = array(STATUS => STATUS_ERROR, TITLE => TECHNICAL_ERROR_TITLE, MESSAGE => TECHNICAL_ERROR_MESSAGE);
    \$JSON_AUTHORIZATION_ERROR = array(STATUS => STATUS_ERROR, TITLE => AUTHORIZATION_ERROR_TITLE, MESSAGE => AUTHORIZATION_ERROR_MESSAGE);
    \$JSON_LOGOUT_SUCCESS = array(STATUS => STATUS_OK, TITLE => LOGOUT_SUCCESS_TITLE, MESSAGE => LOGOUT_SUCCESS_MESSAGE);
    \$JSON_LOGOUT_ERROR = array(STATUS => STATUS_ERROR, TITLE => LOGOUT_ERROR_TITLE, MESSAGE => LOGOUT_ERROR_MESSAGE);
    
    if (!isset(\$_POST[\"SessionID\"]) || \$_POST[\"SessionID\"] == \"\") die(json_encode(\$JSON_INVALID_PARAMS));
    \$sessionID = \$_POST[\"SessionID\"];

    include_once(\"../../Scripts/DBLogin.php\");
    \$conn = dbLogin();
    
    \$sql = \"SELECT * FROM Sessions WHERE SessionID = \\\"\" . \$sessionID . \"\\\"\";
    \$result = \$conn->query(\$sql);
    
    if (\$result->num_rows > 0) {
        \$sql = \"DELETE FROM Sessions WHERE SessionID = \\\"\" . \$sessionID . \"\\\"\";
        \$result = \$conn->query(\$sql);
        if (\$result === TRUE) die(json_encode(\$JSON_LOGOUT_SUCCESS));
        else die (json_encode(\$JSON_LOGOUT_ERROR));
    }
    else die (json_encode(\$JSON_LOGOUT_ERROR));
        
        ";

        writeToPHPFile($fileLocation, $fileName, $fileText);

    }//createSessionDestructionScript()

?>