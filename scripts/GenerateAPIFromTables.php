<?php

    session_start();

    //UTILITY FUNCTIONS
    function getHeader($endpointName, $tableName, $dbName) {
        return getGeneratorHeader($dbName, "API/" . $endpointName . "/index.php", $tableName, "");
    }

    function isQuotableType($typeName) {
        if (strpos($typeName, 'text') !== false ||
            strpos($typeName, 'char') !== false ||
            strpos($typeName, 'date') !== false ||
            strpos($typeName, 'time') !== false
        ) return true;
        return false;
    }//end isQuotableType()

    function toJavaType($fieldType) {
        if ($fieldType == "tinyint(1)") return "boolean";
        else if ($fieldType == "char(1)") return "char";
        else if ($fieldType == "float") return "float";
        else if ($fieldType == "double") return "double";
        else if ($fieldType == "text") return "String";
        else if ($fieldType == "longtext") return "String";
        else if ($fieldType == "date") return "Date";
        else if ($fieldType == "time") return "Time";
        else if (strpos($fieldType, "int") !== false) return "int";
        else if (strpos($fieldType, "varchar") !== false) return "String";
    }//end getFieldJavaType()

    function getTypeCheck($field) {
        if (toJavaType($field->Type) == "int") return "!is_int(\$_POST[\"" . $field->Field . "\"])";
        else if (toJavaType($field->Type) == "double") return "!is_float(\$_POST[\"" . $field->Field . "\"])";
        else if (toJavaType($field->Type) == "float") return "!is_float(\$_POST[\"" . $field->Field . "\"])";
        else if (toJavaType($field->Type) == "String") return "!is_string(\$_POST[\"" . $field->Field . "\"])";
        else if (toJavaType($field->Type) == "boolean")
            return "strtolower(\$_POST[\"".$field->Field."\"]) != \"true\" || strtolower(\$_POST[\"".$field->Field."\"]) != \"false\"";
    }//end getTypeCheck()

    //----------------------------------------------------------------------------------------

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
            $export_url = $_POST["url_" . $table];

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

                $endpointName = "create";

                $endpointCode_Create = "";

                //TODO: Implement create functionality...
                //OnRequest function:
                $onRequestFunctionCode_Create = "
    function onRequest() {
    
        //TODO: Write your code here.
        
    }
    
    /*!!!!!!!!!!!!!!!!!!!!! DO NOT EDIT CODE BELOW THIS POINT !!!!!!!!!!!!!!!!!!!!!*/
    
    ";

                //Create the allowedUserLevelIDs Array:
                $export_allowedUserLevelIDsArray = "const allowedUserLevelIDs = array(1, ";
                foreach ($userLevels as $userLevel) {
                    if (isset($_POST[$endpointName. "_" . $table . "_" . $userLevel->UserLevelName])) {
                        $export_allowedUserLevelIDsArray .= $userLevel->UserLevelID . ", ";
                    }//end if
                }//end foreach userlevel
                $export_allowedUserLevelIDsArray = substr($export_allowedUserLevelIDsArray, 0, strlen($export_allowedUserLevelIDsArray) - 2);
                $export_allowedUserLevelIDsArray .= ");";

                //Create the parameters list for invalid params message:
                $parametersMessage = "Invalid parameters. Expected Parameters: ";
                $parameterChecks = "\r\n\t//-- PARAMETER CHECKS\r\n\r\n";
                foreach ($allFields as $field) {
                    if (($field->Key != "PRI") || ($field->Key == "PRI" && isQuotableType($field->Type))) {
                        $parametersMessage .= $field->Field . " (".toJavaType($field->Type)."), ";
                    }//end if not primary key
                    $parameterChecks .= "\tif (!isset(\$_POST[\"".$field->Field."\"]) || \$_POST[\"".$field->Field."\"] == \"\" || ".getTypeCheck($field).") die(json_encode(JSON_INVALID_PARAMS));\r\n";
                }//end foreach field
                $parametersMessage = substr($parametersMessage, 0, strlen($parametersMessage) - 2);
                $parametersMessage .= ".";

                //Create the constant fields:
                $strConstants = "
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
    const INVALID_PARAMS_TITLE = \"Invalid Parameters\";
    const INVALID_PARAMS_MESSAGE = \"".$parametersMessage."\";
    const TECHNICAL_ERROR_TITLE = \"Technical Error\";
    const TECHNICAL_ERROR_MESSAGE = \"A technical error has occured. Please consult the system's administrator.\";
    const AUTHORIZATION_ERROR_TITLE = \"Authorization Error\";
    const AUTHORIZATION_ERROR_MESSAGE = \"You are not authorized to access this procedure. If you think you should be able to do so, please consult your system's administrator.\";

    //JSON returns:
    const JSON_INVALID_PARAMS = array(STATUS => STATUS_ERROR, TITLE => INVALID_PARAMS_TITLE, MESSAGE => INVALID_PARAMS_MESSAGE);
    const JSON_TECHNICAL_ERROR = array(STATUS => STATUS_ERROR, TITLE => TECHNICAL_ERROR_TITLE, MESSAGE => TECHNICAL_ERROR_MESSAGE);
    const JSON_AUTHORIZATION_ERROR = array(STATUS => STATUS_ERROR, TITLE => AUTHORIZATION_ERROR_TITLE, MESSAGE => AUTHORIZATION_ERROR_MESSAGE);
";

                echo $strConstants . "<br>";

                $includeOnceDBLogin = "\tinclude_once(\"../../../Scripts/DBLogin.php\");";

                $securityChecks = "
     \r\n\t//-- SECURITY CHECKS

    //Allowed user levels:
    ".$export_allowedUserLevelIDsArray."

    //Validate session if a session is required (not public)
    \$sessionID = \$_POST[\"SessionID\"];
    \$conn = dbLogin();
    \$sqlSessions = \"SELECT * FROM Sessions WHERE SessionID = '\" . \$sessionID . \"'\";
    \$result = \$conn->query(\$sqlSessions);
    if (\$result === FALSE) die(json_encode(JSON_AUTHORIZATION_ERROR));
    else {
        \$session = \$result->fetch_object();
        \$sqlGetUser = \"SELECT UserLevelID FROM Users WHERE UserID = \" . \$session->UserID;
        \$result = \$conn->query(\$sqlGetUser);
        \$user = \$result->fetch_object();
        \$allowed = false;
        foreach (allowedUserLevelIDs as \$id) {
            if (\$user->UserLevelID == \$id) {
                \$allowed = true; break;
            }//end if match
        }//end foreach UserLevelID
        if (!\$allowed) die(json_encode(JSON_AUTHORIZATION_ERROR));
    }//end if session found
    ";

                $funcCallAndConnClose = "\r\n\r\n\tonRequest(); \r\n\r\n\t\$conn->close();";

                $endpointCode_Create =
                    getHeader($endpointName, $table, $dbName) .
                    $onRequestFunctionCode_Create .
                    $strConstants .
                    $parameterChecks .
                    $includeOnceDBLogin .
                    $securityChecks .
                    $funcCallAndConnClose
                ;

                writeToPHPFile("../Generated/API/" . ucfirst($table) . "/" . ucfirst($endpointName) . "/", "index.php", $endpointCode_Create);
                echo "<p style='font-family: consolas;'>Created: " . ucfirst($table) . " -> " . ucfirst($endpointName) . "</p>";

            }//end if create endpoint

            //TODO Implement other endpoints

        }//end if generate table is set

    }//end foreach table


    //TODO: Proceed to generateAPIStep2.php (Creating custom API endpoints)


?>