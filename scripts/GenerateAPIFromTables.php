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

    //Create the login API
    createSessionCreationScript($dbHostIP, $dbUsername, $dbPassword, $dbName);

    //Create the logout API
    createSessionDestructionScript($dbHostIP, $dbUsername, $dbPassword, $dbName);

    //Create the table APIs
    foreach ($tableNames as $table) {

        if (isset($_POST[$table . "_generate"])) {

            if (!isset($_POST["url_" . $table]) || $_POST["url_" . $table] == "") {
                $previous = "javascript:history.go(-1)";
                if(isset($_SERVER['HTTP_REFERER'])) $previous = $_SERVER['HTTP_REFERER'];
                header("Location: " . $_SERVER["HTTP_REFERER"] . "&status=BaseURLError&table=" . $table); exit();
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

//------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
            //Endpoint "CREATE":
            if (isset($_POST["generate_" . $table . "_create"])) {

                $endpointName = "create";

                //Create the allowedUserLevelIDs Array:
                $export_allowedUserLevelIDsArray = "\$allowedUserLevelIDs = array(1, ";
                $isPublicEndpoint = false;
                $allowedUsersInstructions = "Administrator (1)";
                foreach ($userLevels as $userLevel) {
                    if (isset($_POST[$endpointName. "_" . $table . "___" . $userLevel->UserLevelName])) {
                        if ($userLevel->UserLevelID == 4) $isPublicEndpoint = true;
                        $export_allowedUserLevelIDsArray .= $userLevel->UserLevelID . ", ";
                        $allowedUsersInstructions .= ", " . $userLevel->UserLevelName . "(" . $userLevel->UserLevelID . ")";
                    }//end if
                }//end foreach userlevel
                $export_allowedUserLevelIDsArray = substr($export_allowedUserLevelIDsArray, 0, strlen($export_allowedUserLevelIDsArray) - 2);
                $export_allowedUserLevelIDsArray .= ");";

                //Create the parameters lists:
                $parametersMessage = "Invalid parameters. Expected Parameters: ";
                $parameterChecks = "\r\n\t//-- PARAMETER CHECKS\r\n\r\n";
                $parametersForSampleCall = "?";
                $parametersList = "";
                $constructorParameters = "";
                foreach ($allFields as $field) {
                    $parametersMessage .= ucfirst($field->Field) . " (".toJavaType($field->Type)."), ";
                    $parameterChecks .= "\tif (!isset(\$_POST[\"".ucfirst($field->Field)."\"]) || \$_POST[\"".ucfirst($field->Field)."\"] == \"\") die(json_encode(\$JSON_INVALID_PARAMS));\r\n";
                    $parametersForSampleCall .= ucfirst($field->Field) . "...&";
                    $parametersList .= "\r\n\t\t" . ucfirst($field->Field) . " (" . toJavaType($field->Type) . ")";
                    $constructorParameters .= "\$_POST[\"" . ucfirst($field->Field) . "\"], ";
                }//end foreach field
                $constructorParameters = substr($constructorParameters, 0, strlen($constructorParameters) - 2);

                //Add session parameter:
                if (!$isPublicEndpoint) {
                    $parametersMessage .= "SessionID (String)";
                    $parameterChecks .= "\tif (!isset(\$_POST[\"SessionID\"]) || \$_POST[\"SessionID\"] == \"\") die(json_encode(\$JSON_INVALID_PARAMS));\r\n";
                    $parametersForSampleCall .= "SessionID=...";
                    $parametersList .= "\r\n\t\tSessionID (String)";
                }
                else {
                    $parametersMessage = substr($parametersMessage, 0, strlen($parametersMessage) - 2);
                    $parametersForSampleCall = substr($parametersForSampleCall, 0, strlen($parametersForSampleCall) - 1);
                    $parametersMessage .= ".";
                }

                $endpointCode_Create = "";

                //OnRequest function:
                $class = ucfirst($table);
                $onRequestFunctionCode_Create = "
    
    //The onRequest() function is called once all security constraints are passed and all parameters have been verified.
    //Important Notice: This function has been automatically generated based on your database.
    //Editing this function is OK, but not recommended.                              
    function onRequest() {
        \$JSON_ADD_SUCCESS = array(STATUS => STATUS_OK, TITLE => CREATE_SUCCESS_TITLE, MESSAGE => CREATE_SUCCESS_MESSAGE);
        \$JSON_ADD_ERROR = array(STATUS => STATUS_ERROR, TITLE => CREATE_ERROR_TITLE, MESSAGE => CREATE_ERROR_MESSAGE);
        \$JSON_EXISTS_ERROR = array(STATUS => STATUS_ERROR, TITLE => \"Item exists.\", MESSAGE => \"The item you tried to create already exists.\"); 
        
        include_once(\"../../../Scripts/Entity Classes/PHP/".$class.".php\");
        \$object = new ".$class."(".$constructorParameters.");
        \$result = ".$class."::create(\$object);
        if (\$result) die(json_encode(\$JSON_ADD_SUCCESS));
        else {
            if (".$class."::\$hasUniqueFields) die(json_encode(\$JSON_EXISTS_ERROR));
            else die (json_encode(\$JSON_ADD_ERROR));
        }
    }
    
    /*!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!! DO NOT EDIT CODE BELOW THIS POINT !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!*/
    //Editing the code below will compromise the reliability and security of your API.
    
    ";

                //Create the constant fields:
                $strConstants = "
    //Locals:
    const ENDPOINT_NAME = \"API/".ucfirst($table)."/".ucfirst($endpointName)."\";
                
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
    const CREATE_SUCCESS_TITLE = \"Item added.\";
    const CREATE_SUCCESS_MESSAGE = \"Item added successfully.\";
    const CREATE_ERROR_TITLE = \"Item add failed.\";
    const CREATE_ERROR_MESSAGE = \"Failed to add item.\";
    

    //JSON returns:
    \$JSON_INVALID_PARAMS = array(STATUS => STATUS_ERROR, TITLE => INVALID_PARAMS_TITLE, MESSAGE => INVALID_PARAMS_MESSAGE);
    \$JSON_TECHNICAL_ERROR = array(STATUS => STATUS_ERROR, TITLE => TECHNICAL_ERROR_TITLE, MESSAGE => TECHNICAL_ERROR_MESSAGE);
    \$JSON_AUTHORIZATION_ERROR = array(STATUS => STATUS_ERROR, TITLE => AUTHORIZATION_ERROR_TITLE, MESSAGE => AUTHORIZATION_ERROR_MESSAGE);
    
";


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
    if (\$result === FALSE) die(json_encode(\$JSON_AUTHORIZATION_ERROR));
    else {
        \$session = \$result->fetch_object();
        if (!\$session) die(json_encode(\$JSON_AUTHORIZATION_ERROR));
        \$sqlGetUser = \"SELECT UserLevelID FROM Users WHERE UserID = \" . \$session->UserID;
        \$result = \$conn->query(\$sqlGetUser);
        \$user = \$result->fetch_object();
        \$allowed = false;
        foreach (\$allowedUserLevelIDs as \$id) {
            if (\$user->UserLevelID == \$id) {
                \$allowed = true; break;
            }//end if match
        }//end foreach UserLevelID
        if (!\$allowed) die(json_encode(\$JSON_AUTHORIZATION_ERROR));
    }//end if session found
    \$conn->close();
    ";

                //Endpoint instructions:

                $instructions_public = "This endpoint is public and requires no authorization.";
                $instructions_nonpublic = "This endpoint is not public and requires a Session ID to be provided for authorization.";

                $instructions_additional = "";
                if ($isPublicEndpoint)  $instructions_additional = $instructions_public;
                else $instructions_additional = $instructions_nonpublic;

                $instructions = "
                
/*
        ~~~~~~ API Endpoint Instructions ~~~~~~
        
        " . $instructions_additional . "
        
        Sample call for API/".ucfirst($table)."/".ucfirst($endpointName).":

            API/".ucfirst($table)."/".ucfirst($endpointName). $parametersForSampleCall . "

        /----------------------------------------------------------------/

        User Types/Levels who can access this endpoint:
         ".$allowedUsersInstructions."

        Call Parameters List:
        ".$parametersList."

        /----------------------------------------------------------------/


        This endpoint responds with JSON data in the following ways.

        Response Format:

        1) Response OK

            --> \"Status\": \"OK\"
            --> \"Title\": \"Item added.\"
            --> \"Message\": \"Item added successfully.\"

        2) Response ERROR
        
            --> \"Status\": \"Error\"
            --> \"Title\": \"Item exists.\"
            --> \"Message\": \"The item you tried to create already exists.\"

                (Invalid parameters)

            --> \"Status\": \"Error\"
            --> \"Title\": \"Invalid Parameters\"
            --> \"Message\": \"".$parametersMessage."\"

                (Technical error)

            --> \"Status\": \"Error\"
            --> \"Title\": \"Technical Error\"
            --> \"Message\": \"A technical error has occured. Please consult the system's administrator.\"

                (Invalid identification)

            --> \"Status\": \"Error\"
            --> \"Title\": \"Authorization Error\"
            --> \"Message\": \"You are not authorized to access this procedure. If you think you should be able to do so, please consult your system's administrator.\"

    */
                
                
                ";

                if ($isPublicEndpoint) $securityChecks = "";

                $funcCallAndConnClose = "\r\n\r\n\tonRequest(); ";

                $endpointCode_Create =
                    getHeader($endpointName, $table, $dbName) .
                    $instructions .
                    $onRequestFunctionCode_Create .
                    $strConstants .
                    $parameterChecks .
                    $includeOnceDBLogin .
                    $securityChecks .
                    $funcCallAndConnClose
                ;

                writeToPHPFile("../Generated/API/" . ucfirst($table) . "/" . ucfirst($endpointName) . "/", "index.php", $endpointCode_Create);

            }//end if create endpoint

//------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
            //Endpoint "GET BY ID":
            if (isset($_POST["generate_" . $table . "_getByID"])) {

                $endpointName = "getByID";

                //Create the allowedUserLevelIDs Array:
                $export_allowedUserLevelIDsArray = "\$allowedUserLevelIDs = array(1, ";
                $isPublicEndpoint = false;
                $allowedUsersInstructions = "Administrator (1)";
                foreach ($userLevels as $userLevel) {
                    if (isset($_POST[$endpointName. "_" . $table . "___" . $userLevel->UserLevelName])) {
                        if ($userLevel->UserLevelID == 4) $isPublicEndpoint = true;
                        $export_allowedUserLevelIDsArray .= $userLevel->UserLevelID . ", ";
                        $allowedUsersInstructions .= ", " . $userLevel->UserLevelName . "(" . $userLevel->UserLevelID . ")";
                    }//end if
                }//end foreach userlevel
                $export_allowedUserLevelIDsArray = substr($export_allowedUserLevelIDsArray, 0, strlen($export_allowedUserLevelIDsArray) - 2);
                $export_allowedUserLevelIDsArray .= ");";

                //Create the parameters lists:
                $parametersMessage = "Invalid parameters. Expected Parameters: ";
                $parameterChecks = "\r\n\t//-- PARAMETER CHECKS\r\n\r\n";
                $parametersForSampleCall = "?";
                $parametersList = "";
                $constructorParameters = "";
                $primaryKeyField = null;
                foreach ($allFields as $field) {
                    if (($field->Key == "PRI")) {
                        $primaryKeyField = $field;
                        $parametersMessage .= ucfirst($field->Field) . " (".toJavaType($field->Type)."), ";
                        $parameterChecks .= "\tif (!isset(\$_POST[\"".ucfirst($field->Field)."\"]) || \$_POST[\"".ucfirst($field->Field)."\"] == \"\") die(json_encode(\$JSON_INVALID_PARAMS));\r\n";
                        $parametersForSampleCall .= ucfirst($field->Field) . "...&";
                        $parametersList .= "\r\n\t\t" . ucfirst($field->Field) . " (" . toJavaType($field->Type) . ")";
                        $constructorParameters .= "\$_POST[\"" . ucfirst($field->Field) . "\"], ";
                    }//end if not primary key

                }//end foreach field
                $constructorParameters = substr($constructorParameters, 0, strlen($constructorParameters) - 2);

                //Add session parameter:
                if (!$isPublicEndpoint) {
                    $parametersMessage .= "SessionID (String)";
                    $parameterChecks .= "\tif (!isset(\$_POST[\"SessionID\"]) || \$_POST[\"SessionID\"] == \"\") die(json_encode(\$JSON_INVALID_PARAMS));\r\n";
                    $parametersForSampleCall .= "SessionID=...";
                    $parametersList .= "\r\n\t\tSessionID (String)";
                }
                else {
                    $parametersMessage = substr($parametersMessage, 0, strlen($parametersMessage) - 2);
                    $parametersForSampleCall = substr($parametersForSampleCall, 0, strlen($parametersForSampleCall) - 1);
                    $parametersMessage .= ".";
                }

                $endpointCode_Create = "";

                //OnRequest function:
                $class = ucfirst($table);
                $onRequestFunctionCode_Create = "
    
    //The onRequest() function is called once all security constraints are passed and all parameters have been verified.
    //Important Notice: This function has been automatically generated based on your database.
    //Editing this function is OK, but not recommended.                              
    function onRequest() {
        \$JSON_GET_ERROR = array(STATUS => STATUS_ERROR, TITLE => GET_ERROR_TITLE, MESSAGE => GET_ERROR_MESSAGE);
        
        include_once(\"../../../Scripts/Entity Classes/PHP/".$class.".php\");     
           
        \$object = ".$class."::getByID(".$constructorParameters.");
        if (!\$object) die(json_encode(\$JSON_GET_ERROR));
        \$returnArray = array(STATUS => STATUS_OK, TITLE => GET_SUCCESS_TITLE, MESSAGE => GET_SUCCESS_MESSAGE);
        \$statusJson = json_encode(\$returnArray);
        \$statusJson = substr(\$statusJson, 0, strlen(\$statusJson) - 1);
        \$objectData = \", \\\"Data\\\": \" . \$object->jsonSerialize() . \"}\";
        \$combinedReturn = \$statusJson . \$objectData;
        die (\$combinedReturn);
    }
    
    /*!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!! DO NOT EDIT CODE BELOW THIS POINT !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!*/
    //Editing the code below will compromise the reliability and security of your API.
    
    ";

                //Create the constant fields:
                $strConstants = "
    //Locals:
    const ENDPOINT_NAME = \"API/".ucfirst($table)."/".ucfirst($endpointName)."\";
                
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
    const GET_SUCCESS_TITLE = \"Item retrieved.\";
    const GET_SUCCESS_MESSAGE = \"Item retrieved successfully.\";
    const GET_ERROR_TITLE = \"Item retrieval failed.\";
    const GET_ERROR_MESSAGE = \"Failed to retrieve item.\";
    

    //JSON returns:
    \$JSON_INVALID_PARAMS = array(STATUS => STATUS_ERROR, TITLE => INVALID_PARAMS_TITLE, MESSAGE => INVALID_PARAMS_MESSAGE);
    \$JSON_TECHNICAL_ERROR = array(STATUS => STATUS_ERROR, TITLE => TECHNICAL_ERROR_TITLE, MESSAGE => TECHNICAL_ERROR_MESSAGE);
    \$JSON_AUTHORIZATION_ERROR = array(STATUS => STATUS_ERROR, TITLE => AUTHORIZATION_ERROR_TITLE, MESSAGE => AUTHORIZATION_ERROR_MESSAGE);
    
";


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
    if (\$result->num_rows <= 0) die(json_encode(\$JSON_AUTHORIZATION_ERROR));
    else {
        \$session = \$result->fetch_object();
        \$sqlGetUser = \"SELECT UserLevelID FROM Users WHERE UserID = \" . \$session->UserID;
        \$result = \$conn->query(\$sqlGetUser);
        \$user = \$result->fetch_object();
        \$allowed = false;
        foreach (\$allowedUserLevelIDs as \$id) {
            if (\$user->UserLevelID == \$id) {
                \$allowed = true; break;
            }//end if match
        }//end foreach UserLevelID
        if (!\$allowed) die(json_encode(\$JSON_AUTHORIZATION_ERROR));
    }//end if session found
    \$conn->close();
    ";

                //Endpoint instructions:

                $instructions_public = "This endpoint is public and requires no authorization.";
                $instructions_nonpublic = "This endpoint is not public and requires a Session ID to be provided for authorization.";

                $instructions_additional = "";
                if ($isPublicEndpoint)  $instructions_additional = $instructions_public;
                else $instructions_additional = $instructions_nonpublic;

                $instructions = "
                
/*
        ~~~~~~ API Endpoint Instructions ~~~~~~
        
        " . $instructions_additional . "
        
        Sample call for API/".ucfirst($table)."/".ucfirst($endpointName).":

            API/".ucfirst($table)."/".ucfirst($endpointName). $parametersForSampleCall . "

        /----------------------------------------------------------------/

        User Types/Levels who can access this endpoint:
         ".$allowedUsersInstructions."

        Call Parameters List:
        ".$parametersList."

        /----------------------------------------------------------------/


        This endpoint responds with JSON data in the following ways.

        Response Format:

        1) Response OK

            --> \"Status\": \"OK\"
            --> \"Title\": \"Item retrieved.\"
            --> \"Message\": \"Item retrieved successfully.\"

        2) Response ERROR
        
            --> \"Status\": \"Error\"
            --> \"Title\": \"Item retrieval failed.\"
            --> \"Message\": \"Failed to retrieve item with specified ID.\"

                (Invalid parameters)

            --> \"Status\": \"Error\"
            --> \"Title\": \"Invalid Parameters\"
            --> \"Message\": \"".$parametersMessage."\"

                (Technical error)

            --> \"Status\": \"Error\"
            --> \"Title\": \"Technical Error\"
            --> \"Message\": \"A technical error has occured. Please consult the system's administrator.\"

                (Invalid identification)

            --> \"Status\": \"Error\"
            --> \"Title\": \"Authorization Error\"
            --> \"Message\": \"You are not authorized to access this procedure. If you think you should be able to do so, please consult your system's administrator.\"

    */
                
                
                ";

                if ($isPublicEndpoint) $securityChecks = "";

                $funcCallAndConnClose = "\r\n\r\n\tonRequest(); ";

                $endpointCode_Create =
                    getHeader($endpointName, $table, $dbName) .
                    $instructions .
                    $onRequestFunctionCode_Create .
                    $strConstants .
                    $parameterChecks .
                    $includeOnceDBLogin .
                    $securityChecks .
                    $funcCallAndConnClose
                ;

                writeToPHPFile("../Generated/API/" . ucfirst($table) . "/" . ucfirst($endpointName) . "/", "index.php", $endpointCode_Create);

            }//end if create getByID endpoint

//------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------

            //Endpoint "GET BY INDEXER":

            //Find indexer fields:
            $indexers = array();
            foreach ($allFields as $field) {
                if (($field->Key == "UNI")) {
                    array_push($indexers, $field);
                }//end if unique
            }//end foreach field

            foreach ($indexers as $indexer) {

                if (isset($_POST["generate_" . $table . "_getBy" . ucfirst($indexer->Field)])) {

                    $endpointName = "getBy" . ucfirst($indexer->Field);

                    //Create the allowedUserLevelIDs Array:
                    $export_allowedUserLevelIDsArray = "\$allowedUserLevelIDs = array(1, ";
                    $isPublicEndpoint = false;
                    $allowedUsersInstructions = "Administrator (1)";
                    foreach ($userLevels as $userLevel) {
                        if (isset($_POST["__" . $userLevel->UserLevelName . "_" . $endpointName . "_" . $table . "_" . $userLevel->UserLevelName])) {
                            if ($userLevel->UserLevelID == 4) $isPublicEndpoint = true;
                            $export_allowedUserLevelIDsArray .= $userLevel->UserLevelID . ", ";
                            $allowedUsersInstructions .= ", " . $userLevel->UserLevelName . "(" . $userLevel->UserLevelID . ")";
                        }//end if
                    }//end foreach userlevel
                    $export_allowedUserLevelIDsArray = substr($export_allowedUserLevelIDsArray, 0, strlen($export_allowedUserLevelIDsArray) - 2);
                    $export_allowedUserLevelIDsArray .= ");";

                    //Create the parameters lists:
                    $parametersMessage = "Invalid parameters. Expected Parameters: ";
                    $parameterChecks = "\r\n\t//-- PARAMETER CHECKS\r\n\r\n";
                    $parametersForSampleCall = "?";
                    $parametersList = "";
                    $constructorParameters = "";
                    foreach ($indexers as $field) {
                        $parametersMessage .= ucfirst($field->Field) . " (" . toJavaType($field->Type) . "), ";
                        $parameterChecks .= "\tif (!isset(\$_POST[\"" . ucfirst($field->Field) . "\"]) || \$_POST[\"" . ucfirst($field->Field) . "\"] == \"\") die(json_encode(\$JSON_INVALID_PARAMS));\r\n";
                        $parametersForSampleCall .= ucfirst($field->Field) . "...&";
                        $parametersList .= "\r\n\t\t" . ucfirst($indexer->Field) . " (" . toJavaType($field->Type) . ")";
                        $constructorParameters .= "\$_POST[\"" . ucfirst($field->Field) . "\"], ";
                    }//end foreach field
                    $constructorParameters = substr($constructorParameters, 0, strlen($constructorParameters) - 2);

                    //Add session parameter:
                    if (!$isPublicEndpoint) {
                        $parametersMessage .= "SessionID (String)";
                        $parameterChecks .= "\tif (!isset(\$_POST[\"SessionID\"]) || \$_POST[\"SessionID\"] == \"\") die(json_encode(\$JSON_INVALID_PARAMS));\r\n";
                        $parametersForSampleCall .= "SessionID=...";
                        $parametersList .= "\r\n\t\tSessionID (String)";
                    } else {
                        $parametersMessage = substr($parametersMessage, 0, strlen($parametersMessage) - 2);
                        $parametersForSampleCall = substr($parametersForSampleCall, 0, strlen($parametersForSampleCall) - 1);
                        $parametersMessage .= ".";
                    }

                    $endpointCode_Create = "";

                    //OnRequest function:
                    $class = ucfirst($table);
                    $onRequestFunctionCode_Create = "
        
        //The onRequest() function is called once all security constraints are passed and all parameters have been verified.
        //Important Notice: This function has been automatically generated based on your database.
        //Editing this function is OK, but not recommended.                              
        function onRequest() {
            \$JSON_GET_ERROR = array(STATUS => STATUS_ERROR, TITLE => GET_ERROR_TITLE, MESSAGE => GET_ERROR_MESSAGE);
            
            include_once(\"../../../Scripts/Entity Classes/PHP/" . $class . ".php\");     
               
            \$object = ".$class."::getBy".ucfirst($indexer->Field)."(".$constructorParameters.");
            if (!\$object) die(json_encode(\$JSON_GET_ERROR));
            \$returnArray = array(STATUS => STATUS_OK, TITLE => GET_SUCCESS_TITLE, MESSAGE => GET_SUCCESS_MESSAGE);
            \$statusJson = json_encode(\$returnArray);
            \$statusJson = substr(\$statusJson, 0, strlen(\$statusJson) - 1);
            \$objectData = \", \\\"Data\\\": \" . \$object->jsonSerialize() . \"}\";
            \$combinedReturn = \$statusJson . \$objectData;
            die (\$combinedReturn);
        }
        
        /*!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!! DO NOT EDIT CODE BELOW THIS POINT !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!*/
        //Editing the code below will compromise the reliability and security of your API.
        
        ";

                    //Create the constant fields:
                    $strConstants = "
        //Locals:
        const ENDPOINT_NAME = \"API/" . ucfirst($table) . "/GetBy" . ucfirst($indexer->Field). "\";
                    
        //Statuses:
        const STATUS_ERROR = \"Error\";
        const STATUS_OK = \"OK\";
    
        //Titles/Messages:
        const STATUS = \"Status\";
        const TITLE = \"Title\";
        const MESSAGE = \"Message\";
        const DATA = \"Data\";
        const INVALID_PARAMS_TITLE = \"Invalid Parameters\";
        const INVALID_PARAMS_MESSAGE = \"" . $parametersMessage . "\";
        const TECHNICAL_ERROR_TITLE = \"Technical Error\";
        const TECHNICAL_ERROR_MESSAGE = \"A technical error has occured. Please consult the system's administrator.\";
        const AUTHORIZATION_ERROR_TITLE = \"Authorization Error\";
        const AUTHORIZATION_ERROR_MESSAGE = \"You are not authorized to access this procedure. If you think you should be able to do so, please consult your system's administrator.\";
        const GET_SUCCESS_TITLE = \"Item retrieved.\";
        const GET_SUCCESS_MESSAGE = \"Item retrieved successfully.\";
        const GET_ERROR_TITLE = \"Item retrieval failed.\";
        const GET_ERROR_MESSAGE = \"Failed to retrieve item with specified index value.\";
        
    
        //JSON returns:
        \$JSON_INVALID_PARAMS = array(STATUS => STATUS_ERROR, TITLE => INVALID_PARAMS_TITLE, MESSAGE => INVALID_PARAMS_MESSAGE);
        \$JSON_TECHNICAL_ERROR = array(STATUS => STATUS_ERROR, TITLE => TECHNICAL_ERROR_TITLE, MESSAGE => TECHNICAL_ERROR_MESSAGE);
        \$JSON_AUTHORIZATION_ERROR = array(STATUS => STATUS_ERROR, TITLE => AUTHORIZATION_ERROR_TITLE, MESSAGE => AUTHORIZATION_ERROR_MESSAGE);
        
    ";


                    $includeOnceDBLogin = "\tinclude_once(\"../../../Scripts/DBLogin.php\");";

                    $securityChecks = "
         \r\n\t//-- SECURITY CHECKS
    
        //Allowed user levels:
        " . $export_allowedUserLevelIDsArray . "
    
        //Validate session if a session is required (not public)
        \$sessionID = \$_POST[\"SessionID\"];
        \$conn = dbLogin();
        \$sqlSessions = \"SELECT * FROM Sessions WHERE SessionID = '\" . \$sessionID . \"'\";
        \$result = \$conn->query(\$sqlSessions);
        if (\$result->num_rows <= 0) die(json_encode(\$JSON_AUTHORIZATION_ERROR));
        else {
            \$session = \$result->fetch_object();
            \$sqlGetUser = \"SELECT UserLevelID FROM Users WHERE UserID = \" . \$session->UserID;
            \$result = \$conn->query(\$sqlGetUser);
            \$user = \$result->fetch_object();
            \$allowed = false;
            foreach (\$allowedUserLevelIDs as \$id) {
                if (\$user->UserLevelID == \$id) {
                    \$allowed = true; break;
                }//end if match
            }//end foreach UserLevelID
            if (!\$allowed) die(json_encode(\$JSON_AUTHORIZATION_ERROR));
        }//end if session found
        \$conn->close();
        ";

                    //Endpoint instructions:

                    $instructions_public = "This endpoint is public and requires no authorization.";
                    $instructions_nonpublic = "This endpoint is not public and requires a Session ID to be provided for authorization.";

                    $instructions_additional = "";
                    if ($isPublicEndpoint) $instructions_additional = $instructions_public;
                    else $instructions_additional = $instructions_nonpublic;

                    $instructions = "
                    
/*
        ~~~~~~ API Endpoint Instructions ~~~~~~
        
        " . $instructions_additional . "
        
        Sample call for API/" . ucfirst($table) . "/" . ucfirst($endpointName) . ":

            API/" . ucfirst($table) . "/" . ucfirst($endpointName) . $parametersForSampleCall . "

        /----------------------------------------------------------------/

        User Types/Levels who can access this endpoint:
         " . $allowedUsersInstructions . "

        Call Parameters List:
        " . $parametersList . "

        /----------------------------------------------------------------/


        This endpoint responds with JSON data in the following ways.

        Response Format:

        1) Response OK

            --> \"Status\": \"OK\"
            --> \"Title\": \"Item retrieved.\"
            --> \"Message\": \"Item retrieved successfully.\"

        2) Response ERROR
        
            --> \"Status\": \"Error\"
            --> \"Title\": \"Item retrieval failed.\"
            --> \"Message\": \"Failed to retrieve item with specified index value.\"

                (Invalid parameters)

            --> \"Status\": \"Error\"
            --> \"Title\": \"Invalid Parameters\"
            --> \"Message\": \"" . $parametersMessage . "\"

                (Technical error)

            --> \"Status\": \"Error\"
            --> \"Title\": \"Technical Error\"
            --> \"Message\": \"A technical error has occured. Please consult the system's administrator.\"

                (Invalid identification)

            --> \"Status\": \"Error\"
            --> \"Title\": \"Authorization Error\"
            --> \"Message\": \"You are not authorized to access this procedure. If you think you should be able to do so, please consult your system's administrator.\"

    */
                    
                    
                    ";

                    if ($isPublicEndpoint) $securityChecks = "";

                    $funcCallAndConnClose = "\r\n\r\n\tonRequest(); ";

                    $endpointCode_Create =
                        getHeader($endpointName, $table, $dbName) .
                        $instructions .
                        $onRequestFunctionCode_Create .
                        $strConstants .
                        $parameterChecks .
                        $includeOnceDBLogin .
                        $securityChecks .
                        $funcCallAndConnClose;

                    writeToPHPFile("../Generated/API/" . ucfirst($table) . "/" . ucfirst($endpointName) . "/", "index.php", $endpointCode_Create);

                }//end foreach indexer

            }//end if create getByIndexer endpoint

//------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
            //Endpoint "GET MULTIPLE":
            if (isset($_POST["generate_" . $table . "_getMultiple"])) {

                $endpointName = "getMultiple";

                //Create the allowedUserLevelIDs Array:
                $export_allowedUserLevelIDsArray = "\$allowedUserLevelIDs = array(1, ";
                $isPublicEndpoint = false;
                $allowedUsersInstructions = "Administrator (1)";
                foreach ($userLevels as $userLevel) {
                    if (isset($_POST[$endpointName. "_" . $table . "___" . $userLevel->UserLevelName])) {
                        if ($userLevel->UserLevelID == 4) $isPublicEndpoint = true;
                        $export_allowedUserLevelIDsArray .= $userLevel->UserLevelID . ", ";
                        $allowedUsersInstructions .= ", " . $userLevel->UserLevelName . "(" . $userLevel->UserLevelID . ")";
                    }//end if
                }//end foreach userlevel
                $export_allowedUserLevelIDsArray = substr($export_allowedUserLevelIDsArray, 0, strlen($export_allowedUserLevelIDsArray) - 2);
                $export_allowedUserLevelIDsArray .= ");";

                //Create the parameters lists:
                $parametersMessage = "Invalid parameters. Expected Parameters: ";
                $parameterChecks = "\r\n\t//-- PARAMETER CHECKS\r\n\r\n";
                $parametersForSampleCall = "?";
                $parametersList = "";
                $constructorParameters = "";
                $primaryKeyField = null;

                $parametersMessage .= "Limit" . " (int), ";
                $parameterChecks .= "\t\$limit = 0;\r\n\tif (!isset(\$_POST[\"Limit\"]) || \$_POST[\"Limit\"] == \"\") \$limit = 0; else \$limit = \$_POST[\"Limit\"];\r\n";
                $parametersForSampleCall .= "Limit" . "...&";
                $parametersList .= "\r\n\t\t" . "Limit" . " (int)";
                $constructorParameters .= "\$_POST[\"Limit\"]";

                //Add session parameter:
                if (!$isPublicEndpoint) {
                    $parametersMessage .= "SessionID (String)";
                    $parameterChecks .= "\tif (!isset(\$_POST[\"SessionID\"]) || \$_POST[\"SessionID\"] == \"\") die(json_encode(\$JSON_INVALID_PARAMS));\r\n";
                    $parametersForSampleCall .= "SessionID=...";
                    $parametersList .= "\r\n\t\tSessionID (String)";
                }
                else {
                    $parametersMessage = substr($parametersMessage, 0, strlen($parametersMessage) - 2);
                    $parametersForSampleCall = substr($parametersForSampleCall, 0, strlen($parametersForSampleCall) - 1);
                    $parametersMessage .= ".";
                }

                $endpointCode_Create = "";

                //OnRequest function:
                $class = ucfirst($table);
                $onRequestFunctionCode_Create = "
    
    //The onRequest() function is called once all security constraints are passed and all parameters have been verified.
    //Important Notice: This function has been automatically generated based on your database.
    //Editing this function is OK, but not recommended.                              
    function onRequest(\$limit) {
        \$JSON_GET_ERROR = array(STATUS => STATUS_ERROR, TITLE => GET_ERROR_TITLE, MESSAGE => GET_ERROR_MESSAGE);
        \$JSON_NO_ITEMS = array(STATUS => STATUS_ERROR, TITLE => \"No items found.\", MESSAGE => \"Your call has return 0 results.\");
        
        include_once(\"../../../Scripts/Entity Classes/PHP/".$class.".php\");     
           
        \$objects = ".$class."::getMultiple(\$limit);
        if (sizeof(\$objects) <= 0) die(json_encode(\$JSON_NO_ITEMS));
        \$returnArray = array(STATUS => STATUS_OK, TITLE => GET_SUCCESS_TITLE, MESSAGE => GET_SUCCESS_MESSAGE);
        \$statusJson = json_encode(\$returnArray);
        \$statusJson = substr(\$statusJson, 0, strlen(\$statusJson) - 1);
        \$objectData = \", \\\"Data\\\": \" . ".$class."::toJSONArray(\$objects) . \"}\";
        \$combinedReturn = \$statusJson . \$objectData;
        die (\$combinedReturn);
    }
    
    /*!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!! DO NOT EDIT CODE BELOW THIS POINT !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!*/
    //Editing the code below will compromise the reliability and security of your API.
    
    ";

                //Create the constant fields:
                $strConstants = "
    //Locals:
    const ENDPOINT_NAME = \"API/".ucfirst($table)."/".ucfirst($endpointName)."\";
                
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
    const GET_SUCCESS_TITLE = \"Items retrieved.\";
    const GET_SUCCESS_MESSAGE = \"Items retrieved successfully.\";
    const GET_ERROR_TITLE = \"Item retrieval failed.\";
    const GET_ERROR_MESSAGE = \"Failed to retrieve items.\";
    

    //JSON returns:
    \$JSON_INVALID_PARAMS = array(STATUS => STATUS_ERROR, TITLE => INVALID_PARAMS_TITLE, MESSAGE => INVALID_PARAMS_MESSAGE);
    \$JSON_TECHNICAL_ERROR = array(STATUS => STATUS_ERROR, TITLE => TECHNICAL_ERROR_TITLE, MESSAGE => TECHNICAL_ERROR_MESSAGE);
    \$JSON_AUTHORIZATION_ERROR = array(STATUS => STATUS_ERROR, TITLE => AUTHORIZATION_ERROR_TITLE, MESSAGE => AUTHORIZATION_ERROR_MESSAGE);
    
";


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
    if (\$result->num_rows <= 0) die(json_encode(\$JSON_AUTHORIZATION_ERROR));
    else {
        \$session = \$result->fetch_object();
        \$sqlGetUser = \"SELECT UserLevelID FROM Users WHERE UserID = \" . \$session->UserID;
        \$result = \$conn->query(\$sqlGetUser);
        \$user = \$result->fetch_object();
        \$allowed = false;
        foreach (\$allowedUserLevelIDs as \$id) {
            if (\$user->UserLevelID == \$id) {
                \$allowed = true; break;
            }//end if match
        }//end foreach UserLevelID
        if (!\$allowed) die(json_encode(\$JSON_AUTHORIZATION_ERROR));
    }//end if session found
    \$conn->close();
    ";

                //Endpoint instructions:

                $instructions_public = "This endpoint is public and requires no authorization.";
                $instructions_nonpublic = "This endpoint is not public and requires a Session ID to be provided for authorization.";

                $instructions_additional = "";
                if ($isPublicEndpoint)  $instructions_additional = $instructions_public;
                else $instructions_additional = $instructions_nonpublic;

                $instructions = "
                
/*
        ~~~~~~ API Endpoint Instructions ~~~~~~
        
        " . $instructions_additional . "
        
        Sample call for API/".ucfirst($table)."/".ucfirst($endpointName).":

            API/".ucfirst($table)."/".ucfirst($endpointName). $parametersForSampleCall . "

        /----------------------------------------------------------------/

        User Types/Levels who can access this endpoint:
         ".$allowedUsersInstructions."

        Call Parameters List:
        ".$parametersList."

        /----------------------------------------------------------------/


        This endpoint responds with JSON data in the following ways.

        Response Format:

        1) Response OK

            --> \"Status\": \"OK\"
            --> \"Title\": \"Item retrieved.\"
            --> \"Message\": \"Item retrieved successfully.\"

        2) Response ERROR
        
            --> \"Status\": \"Error\"
            --> \"Title\": \"Item retrieval failed.\"
            --> \"Message\": \"Failed to retrieve items.\"

                (Invalid parameters)

            --> \"Status\": \"Error\"
            --> \"Title\": \"Invalid Parameters\"
            --> \"Message\": \"".$parametersMessage."\"

                (Technical error)

            --> \"Status\": \"Error\"
            --> \"Title\": \"Technical Error\"
            --> \"Message\": \"A technical error has occured. Please consult the system's administrator.\"

                (Invalid identification)

            --> \"Status\": \"Error\"
            --> \"Title\": \"Authorization Error\"
            --> \"Message\": \"You are not authorized to access this procedure. If you think you should be able to do so, please consult your system's administrator.\"

    */
                
                
                ";

                if ($isPublicEndpoint) $securityChecks = "";

                $funcCallAndConnClose = "\r\n\r\n\tonRequest(\$limit); ";

                $endpointCode_Create =
                    getHeader($endpointName, $table, $dbName) .
                    $instructions .
                    $onRequestFunctionCode_Create .
                    $strConstants .
                    $parameterChecks .
                    $includeOnceDBLogin .
                    $securityChecks .
                    $funcCallAndConnClose
                ;

                writeToPHPFile("../Generated/API/" . ucfirst($table) . "/" . ucfirst($endpointName) . "/", "index.php", $endpointCode_Create);

            }//end if create getMultiple endpoint

//------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------

            //Endpoint "UPDATE":
            if (isset($_POST["generate_" . $table . "_update"])) {

                $endpointName = "update";

                //Create the allowedUserLevelIDs Array:
                $export_allowedUserLevelIDsArray = "\$allowedUserLevelIDs = array(1, ";
                $isPublicEndpoint = false;
                $allowedUsersInstructions = "Administrator (1)";
                foreach ($userLevels as $userLevel) {
                    if (isset($_POST[$endpointName. "_" . $table . "___" . $userLevel->UserLevelName])) {
                        if ($userLevel->UserLevelID == 4) $isPublicEndpoint = true;
                        $export_allowedUserLevelIDsArray .= $userLevel->UserLevelID . ", ";
                        $allowedUsersInstructions .= ", " . $userLevel->UserLevelName . "(" . $userLevel->UserLevelID . ")";
                    }//end if
                }//end foreach userlevel
                $export_allowedUserLevelIDsArray = substr($export_allowedUserLevelIDsArray, 0, strlen($export_allowedUserLevelIDsArray) - 2);
                $export_allowedUserLevelIDsArray .= ");";

                //Create the parameters lists:
                $parametersMessage = "Invalid parameters. Expected Parameters: ";
                $parameterChecks = "\r\n\t//-- PARAMETER CHECKS\r\n\r\n";
                $parametersForSampleCall = "?";
                $parametersList = "";
                $constructorParameters = "";
                foreach ($allFields as $field) {
                    $parametersMessage .= ucfirst($field->Field) . " (".toJavaType($field->Type)."), ";
                    $parameterChecks .= "\tif (!isset(\$_POST[\"".ucfirst($field->Field)."\"]) || \$_POST[\"".ucfirst($field->Field)."\"] == \"\") die(json_encode(\$JSON_INVALID_PARAMS));\r\n";
                    $parametersForSampleCall .= ucfirst($field->Field) . "...&";
                    $parametersList .= "\r\n\t\t" . ucfirst($field->Field) . " (" . toJavaType($field->Type) . ")";
                    $constructorParameters .= "\$_POST[\"" . ucfirst($field->Field) . "\"], ";
                }//end foreach field
                $constructorParameters = substr($constructorParameters, 0, strlen($constructorParameters) - 2);

                //Add session parameter:
                if (!$isPublicEndpoint) {
                    $parametersMessage .= "SessionID (String)";
                    $parameterChecks .= "\tif (!isset(\$_POST[\"SessionID\"]) || \$_POST[\"SessionID\"] == \"\") die(json_encode(\$JSON_INVALID_PARAMS));\r\n";
                    $parametersForSampleCall .= "SessionID=...";
                    $parametersList .= "\r\n\t\tSessionID (String)";
                }
                else {
                    $parametersMessage = substr($parametersMessage, 0, strlen($parametersMessage) - 2);
                    $parametersForSampleCall = substr($parametersForSampleCall, 0, strlen($parametersForSampleCall) - 1);
                    $parametersMessage .= ".";
                }

                $endpointCode_Create = "";

                //OnRequest function:
                $class = ucfirst($table);
                $onRequestFunctionCode_Create = "
    
    //The onRequest() function is called once all security constraints are passed and all parameters have been verified.
    //Important Notice: This function has been automatically generated based on your database.
    //Editing this function is OK, but not recommended.                              
    function onRequest() {
        \$JSON_UPDATE_SUCCESS = array(STATUS => STATUS_OK, TITLE => UPDATE_SUCCESS_TITLE, MESSAGE => UPDATE_SUCCESS_MESSAGE);
        \$JSON_UPDATE_ERROR = array(STATUS => STATUS_ERROR, TITLE => UPDATE_ERROR_TITLE, MESSAGE => UPDATE_ERROR_MESSAGE);
        
        include_once(\"../../../Scripts/Entity Classes/PHP/".$class.".php\");
        \$object = new ".$class."(".$constructorParameters.");
        \$result = ".$class."::update(\$object);
        if (\$result) die(json_encode(\$JSON_UPDATE_SUCCESS));
        else die (json_encode(\$JSON_UPDATE_ERROR));
    }
    
    /*!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!! DO NOT EDIT CODE BELOW THIS POINT !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!*/
    //Editing the code below will compromise the reliability and security of your API.
    
    ";

                //Create the constant fields:
                $strConstants = "
    //Locals:
    const ENDPOINT_NAME = \"API/".ucfirst($table)."/".ucfirst($endpointName)."\";
                
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
    const UPDATE_SUCCESS_TITLE = \"Item updated.\";
    const UPDATE_SUCCESS_MESSAGE = \"Item updated successfully.\";
    const UPDATE_ERROR_TITLE = \"Item update failed.\";
    const UPDATE_ERROR_MESSAGE = \"Failed to update item. Check that this item is not already in this state, exists and has the ID provided.\";
    

    //JSON returns:
    \$JSON_INVALID_PARAMS = array(STATUS => STATUS_ERROR, TITLE => INVALID_PARAMS_TITLE, MESSAGE => INVALID_PARAMS_MESSAGE);
    \$JSON_TECHNICAL_ERROR = array(STATUS => STATUS_ERROR, TITLE => TECHNICAL_ERROR_TITLE, MESSAGE => TECHNICAL_ERROR_MESSAGE);
    \$JSON_AUTHORIZATION_ERROR = array(STATUS => STATUS_ERROR, TITLE => AUTHORIZATION_ERROR_TITLE, MESSAGE => AUTHORIZATION_ERROR_MESSAGE);
    
";


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
    if (\$result === FALSE) die(json_encode(\$JSON_AUTHORIZATION_ERROR));
    else {
        \$session = \$result->fetch_object();
        \$sqlGetUser = \"SELECT UserLevelID FROM Users WHERE UserID = \" . \$session->UserID;
        \$result = \$conn->query(\$sqlGetUser);
        \$user = \$result->fetch_object();
        \$allowed = false;
        foreach (\$allowedUserLevelIDs as \$id) {
            if (\$user->UserLevelID == \$id) {
                \$allowed = true; break;
            }//end if match
        }//end foreach UserLevelID
        if (!\$allowed) die(json_encode(\$JSON_AUTHORIZATION_ERROR));
    }//end if session found
    \$conn->close();
    ";

                //Endpoint instructions:

                $instructions_public = "This endpoint is public and requires no authorization.";
                $instructions_nonpublic = "This endpoint is not public and requires a Session ID to be provided for authorization.";

                $instructions_additional = "";
                if ($isPublicEndpoint)  $instructions_additional = $instructions_public;
                else $instructions_additional = $instructions_nonpublic;

                $instructions = "
                
/*
        ~~~~~~ API Endpoint Instructions ~~~~~~
        
        " . $instructions_additional . "
        
        Sample call for API/".ucfirst($table)."/".ucfirst($endpointName).":

            API/".ucfirst($table)."/".ucfirst($endpointName). $parametersForSampleCall . "

        /----------------------------------------------------------------/

        User Types/Levels who can access this endpoint:
         ".$allowedUsersInstructions."

        Call Parameters List:
        ".$parametersList."

        /----------------------------------------------------------------/


        This endpoint responds with JSON data in the following ways.

        Response Format:

        1) Response OK

            --> \"Status\": \"OK\"
            --> \"Title\": \"Item updated.\"
            --> \"Message\": \"Item updated successfully.\"

        2) Response ERROR
        
            --> \"Status\": \"Error\"
            --> \"Title\": \"Item update failed.\"
            --> \"Message\": \"Failed to update item. Check that this item is not already in this state, exists and has the ID provided.\"

                (Invalid parameters)

            --> \"Status\": \"Error\"
            --> \"Title\": \"Invalid Parameters\"
            --> \"Message\": \"".$parametersMessage."\"

                (Technical error)

            --> \"Status\": \"Error\"
            --> \"Title\": \"Technical Error\"
            --> \"Message\": \"A technical error has occured. Please consult the system's administrator.\"

                (Invalid identification)

            --> \"Status\": \"Error\"
            --> \"Title\": \"Authorization Error\"
            --> \"Message\": \"You are not authorized to access this procedure. If you think you should be able to do so, please consult your system's administrator.\"

    */
                
                
                ";

                if ($isPublicEndpoint) $securityChecks = "";

                $funcCallAndConnClose = "\r\n\r\n\tonRequest(); ";

                $endpointCode_Create =
                    getHeader($endpointName, $table, $dbName) .
                    $instructions .
                    $onRequestFunctionCode_Create .
                    $strConstants .
                    $parameterChecks .
                    $includeOnceDBLogin .
                    $securityChecks .
                    $funcCallAndConnClose
                ;

                writeToPHPFile("../Generated/API/" . ucfirst($table) . "/" . ucfirst($endpointName) . "/", "index.php", $endpointCode_Create);
            }//end if update endpoint

//------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------

            //Endpoint "SEARCH BY FIELD":
            if (isset($_POST["generate_" . $table . "_searchByField"])) {

                $endpointName = "searchByField";

                //Create the allowedUserLevelIDs Array:
                $export_allowedUserLevelIDsArray = "\$allowedUserLevelIDs = array(1, ";
                $isPublicEndpoint = false;
                $allowedUsersInstructions = "Administrator (1)";
                foreach ($userLevels as $userLevel) {
                    if (isset($_POST[$endpointName. "_" . $table . "___" . $userLevel->UserLevelName])) {
                        if ($userLevel->UserLevelID == 4) $isPublicEndpoint = true;
                        $export_allowedUserLevelIDsArray .= $userLevel->UserLevelID . ", ";
                        $allowedUsersInstructions .= ", " . $userLevel->UserLevelName . "(" . $userLevel->UserLevelID . ")";
                    }//end if
                }//end foreach userlevel
                $export_allowedUserLevelIDsArray = substr($export_allowedUserLevelIDsArray, 0, strlen($export_allowedUserLevelIDsArray) - 2);
                $export_allowedUserLevelIDsArray .= ");";

                //Create the parameters lists:
                $parametersMessage = "Invalid parameters. Expected Parameters: ";
                $parameterChecks = "\r\n\t//-- PARAMETER CHECKS\r\n\r\n";
                $parametersForSampleCall = "?";
                $parametersList = "";
                $constructorParameters = "";

                $parametersMessage .= "Fields (String list), Values (Mixed type list)";
                $parameterChecks .= "\tif (!isset(\$_POST[\"Fields\"]) || \$_POST[\"Fields\"] == \"\") die(json_encode(\$JSON_INVALID_PARAMS));\r\n";
                $parameterChecks .= "\tif (!isset(\$_POST[\"Values\"]) || \$_POST[\"Values\"] == \"\") die(json_encode(\$JSON_INVALID_PARAMS));\r\n";
                $parametersForSampleCall .= "Fields=...Values=...&";
                $parametersList .= "\r\n\t\t" . "Fields (String list)";
                $parametersList .= "\r\n\t\t" . "Values (Mixed type list)";
                $constructorParameters .= "\$_POST[\"Fields\"], \$_POST[\"Values\"], ";

                //Add session parameter:
                if (!$isPublicEndpoint) {
                    $parametersMessage .= "SessionID (String)";
                    $parameterChecks .= "\tif (!isset(\$_POST[\"SessionID\"]) || \$_POST[\"SessionID\"] == \"\") die(json_encode(\$JSON_INVALID_PARAMS));\r\n";
                    $parametersForSampleCall .= "SessionID=...";
                    $parametersList .= "\r\n\t\tSessionID (String)";
                }
                else {
                    $parametersForSampleCall = substr($parametersForSampleCall, 0, strlen($parametersForSampleCall) - 1);
                    $parametersMessage .= ".";
                }

                $endpointCode_Create = "";

                //OnRequest function:
                $class = ucfirst($table);
                $onRequestFunctionCode_Create = "
    
    //The onRequest() function is called once all security constraints are passed and all parameters have been verified.
    //Important Notice: This function has been automatically generated based on your database.
    //Editing this function is OK, but not recommended.                              
    function onRequest() {
        \$JSON_BYFIELD_SUCCESS = array(STATUS => STATUS_OK, TITLE => BYFIELD_SUCCESS_TITLE, MESSAGE => BYFIELD_SUCCESS_MESSAGE);
        \$JSON_BYFIELD_ERROR = array(STATUS => STATUS_ERROR, TITLE => BYFIELD_ERROR_TITLE, MESSAGE => BYFIELD_ERROR_MESSAGE);
        \$JSON_SIZE_MISMATCH = array(STATUS => STATUS_ERROR, TITLE => BYFIELD_SIZE_MISMATCH_TITLE, MESSAGE => BYFIELD_SIZE_MISMATCH_MESSAGE);
        \$JSON_NO_ITEMS = array(STATUS => STATUS_ERROR, TITLE => \"No items found.\", MESSAGE => \"Your call has return 0 results.\");
        \$JSON_INVALID_FIELDS = array(STATUS => STATUS_ERROR, TITLE => \"Invalid field found.\", MESSAGE => \"Your call contains a field that does not exist for this class.\");
        
        include_once(\"../../../Scripts/Entity Classes/PHP/".$class.".php\");
        
        \$fieldsArray = explode(\",\", \$_POST[\"Fields\"]);
        \$valuesArray = explode(\",\", \$_POST[\"Values\"]);
        
        if (sizeof(\$fieldsArray) != sizeof(\$valuesArray)) die(json_encode(\$JSON_SIZE_MISMATCH));
        
        \$assocArray = array();
        
        foreach (\$fieldsArray as \$field) {
            \$exists = false;
            foreach (".$class."::\$allFields as \$classField) {
                if (strtolower(\$field) == strtolower(\$classField)) {
                    \$exists = true;
                    break;
                }   
            }
            if (!\$exists) die(json_encode(\$JSON_INVALID_FIELDS));
        }
        
        for (\$i = 0; \$i < sizeof(\$fieldsArray); \$i++) {
            \$field = \$fieldsArray[\$i];
            \$value = \$valuesArray[\$i];
            \$assocArray[\$field] = \$value;
        }
        
        \$objects = ".$class."::searchByFields(\$assocArray);
        
        if (!\$objects) die(json_encode(\$JSON_BYFIELD_ERROR));
        if (sizeof(\$objects) <= 0) die(json_encode(\$JSON_NO_ITEMS));
        
        \$returnArray = \$JSON_BYFIELD_SUCCESS;
        \$statusJson = json_encode(\$returnArray);
        \$statusJson = substr(\$statusJson, 0, strlen(\$statusJson) - 1);
        \$objectData = \", \\\"Data\\\": \" . ".$class."::toJSONArray(\$objects) . \"}\";
        \$combinedReturn = \$statusJson . \$objectData;
        die (\$combinedReturn);
    }
    
    /*!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!! DO NOT EDIT CODE BELOW THIS POINT !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!*/
    //Editing the code below will compromise the reliability and security of your API.
    
    ";

                //Create the constant fields:
                $strConstants = "
    //Locals:
    const ENDPOINT_NAME = \"API/".ucfirst($table)."/".ucfirst($endpointName)."\";
                
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
    const BYFIELD_SUCCESS_TITLE = \"Item(s) retrieved.\";
    const BYFIELD_SUCCESS_MESSAGE = \"Item(s) retrieved successfully.\";
    const BYFIELD_ERROR_TITLE = \"Failed to retrieve item(s).\";
    const BYFIELD_ERROR_MESSAGE = \"Failed to retrieve item(s). Make sure that all your fields are correctly formatted and match their corresponding values.\";
    const BYFIELD_SIZE_MISMATCH_TITLE = \"Size mismatch.\";
    const BYFIELD_SIZE_MISMATCH_MESSAGE = \"The size of the fields list and values list is not the same. Please make sure you correctly format your call.\";
    

    //JSON returns:
    \$JSON_INVALID_PARAMS = array(STATUS => STATUS_ERROR, TITLE => INVALID_PARAMS_TITLE, MESSAGE => INVALID_PARAMS_MESSAGE);
    \$JSON_TECHNICAL_ERROR = array(STATUS => STATUS_ERROR, TITLE => TECHNICAL_ERROR_TITLE, MESSAGE => TECHNICAL_ERROR_MESSAGE);
    \$JSON_AUTHORIZATION_ERROR = array(STATUS => STATUS_ERROR, TITLE => AUTHORIZATION_ERROR_TITLE, MESSAGE => AUTHORIZATION_ERROR_MESSAGE);
    
";


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
    if (\$result === FALSE) die(json_encode(\$JSON_AUTHORIZATION_ERROR));
    else {
        \$session = \$result->fetch_object();
        \$sqlGetUser = \"SELECT UserLevelID FROM Users WHERE UserID = \" . \$session->UserID;
        \$result = \$conn->query(\$sqlGetUser);
        \$user = \$result->fetch_object();
        \$allowed = false;
        foreach (\$allowedUserLevelIDs as \$id) {
            if (\$user->UserLevelID == \$id) {
                \$allowed = true; break;
            }//end if match
        }//end foreach UserLevelID
        if (!\$allowed) die(json_encode(\$JSON_AUTHORIZATION_ERROR));
    }//end if session found
    \$conn->close();
    ";

                //Endpoint instructions:

                $instructions_public = "This endpoint is public and requires no authorization.";
                $instructions_nonpublic = "This endpoint is not public and requires a Session ID to be provided for authorization.";

                $instructions_additional = "";
                if ($isPublicEndpoint)  $instructions_additional = $instructions_public;
                else $instructions_additional = $instructions_nonpublic;

                $instructions = "
                
/*
        ~~~~~~ API Endpoint Instructions ~~~~~~
        
        " . $instructions_additional . "
        
        Sample call for API/".ucfirst($table)."/".ucfirst($endpointName).":

            API/".ucfirst($table)."/".ucfirst($endpointName). $parametersForSampleCall . "

        /----------------------------------------------------------------/

        User Types/Levels who can access this endpoint:
         ".$allowedUsersInstructions."

        Call Parameters List:
        ".$parametersList."

        /----------------------------------------------------------------/


        This endpoint responds with JSON data in the following ways.

        Response Format:

        1) Response OK

            --> \"Status\": \"OK\"
            --> \"Title\": \"Item(s) retrieved.\"
            --> \"Message\": \"Item(s) retrieved successfully.\"

        2) Response ERROR
        
                (No items)
        
            --> \"Status\": \"Error\"
            --> \"Title\": \"Failed to retrieve item(s).\"
            --> \"Message\": \"Failed to retrieve item(s). Make sure that all your fields are correctly formatted and match their corresponding values.\"
            
                (Fields and Values size mismatch)
                
            --> \"Status\": \"Error\"
            --> \"Title\": \"Size mismatch.\"
            --> \"Message\": \"The size of the fields list and values list is not the same. Please make sure you correctly format your call.\"
            
                (Invalid field name)
                
            --> \"Status\": \"Error\"
            --> \"Title\": \"Invalid Field found.\"
            --> \"Message\": \"Your call contains a field that does not exist for this class.\"

                (Invalid parameters)

            --> \"Status\": \"Error\"
            --> \"Title\": \"Invalid Parameters\"
            --> \"Message\": \"".$parametersMessage."\"

                (Technical error)

            --> \"Status\": \"Error\"
            --> \"Title\": \"Technical Error\"
            --> \"Message\": \"A technical error has occured. Please consult the system's administrator.\"

                (Invalid identification)

            --> \"Status\": \"Error\"
            --> \"Title\": \"Authorization Error\"
            --> \"Message\": \"You are not authorized to access this procedure. If you think you should be able to do so, please consult your system's administrator.\"

    */
                
                
                ";

                if ($isPublicEndpoint) $securityChecks = "";

                $funcCallAndConnClose = "\r\n\r\n\tonRequest(); ";

                $endpointCode_Create =
                    getHeader($endpointName, $table, $dbName) .
                    $instructions .
                    $onRequestFunctionCode_Create .
                    $strConstants .
                    $parameterChecks .
                    $includeOnceDBLogin .
                    $securityChecks .
                    $funcCallAndConnClose
                ;

                writeToPHPFile("../Generated/API/" . ucfirst($table) . "/" . ucfirst($endpointName) . "/", "index.php", $endpointCode_Create);

            }//end if searchByField endpoint

//------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------

            //Endpoint "DELETE":
            if (isset($_POST["generate_" . $table . "_delete"])) {

                $endpointName = "delete";

                //Create the allowedUserLevelIDs Array:
                $export_allowedUserLevelIDsArray = "\$allowedUserLevelIDs = array(1, ";
                $isPublicEndpoint = false;
                $allowedUsersInstructions = "Administrator (1)";
                foreach ($userLevels as $userLevel) {
                    if (isset($_POST[$endpointName. "_" . $table . "___" . $userLevel->UserLevelName])) {
                        if ($userLevel->UserLevelID == 4) $isPublicEndpoint = true;
                        $export_allowedUserLevelIDsArray .= $userLevel->UserLevelID . ", ";
                        $allowedUsersInstructions .= ", " . $userLevel->UserLevelName . "(" . $userLevel->UserLevelID . ")";
                    }//end if
                }//end foreach userlevel
                $export_allowedUserLevelIDsArray = substr($export_allowedUserLevelIDsArray, 0, strlen($export_allowedUserLevelIDsArray) - 2);
                $export_allowedUserLevelIDsArray .= ");";

                //Create the parameters lists:
                $parametersMessage = "Invalid parameters. Expected Parameters: ";
                $parameterChecks = "\r\n\t//-- PARAMETER CHECKS\r\n\r\n";
                $parametersForSampleCall = "?";
                $parametersList = "";
                $constructorParameters = "";
                $primaryKeyField = null;
                foreach ($allFields as $field) {
                    if ($field->Key == "PRI") {
                        $primaryKeyField = $field;
                        $parametersMessage .= ucfirst($field->Field) . " (".toJavaType($field->Type)."), ";
                        $parameterChecks .= "\tif (!isset(\$_POST[\"".ucfirst($field->Field)."\"]) || \$_POST[\"".ucfirst($field->Field)."\"] == \"\") die(json_encode(\$JSON_INVALID_PARAMS));\r\n";
                        $parametersForSampleCall .= ucfirst($field->Field) . "...&";
                        $parametersList .= "\r\n\t\t" . ucfirst($field->Field) . " (" . toJavaType($field->Type) . ")";
                        $constructorParameters .= "\$_POST[\"" . ucfirst($field->Field) . "\"], ";
                    }
                }//end foreach field
                $constructorParameters = substr($constructorParameters, 0, strlen($constructorParameters) - 2);

                //Add session parameter:
                if (!$isPublicEndpoint) {
                    $parametersMessage .= "SessionID (String)";
                    $parameterChecks .= "\tif (!isset(\$_POST[\"SessionID\"]) || \$_POST[\"SessionID\"] == \"\") die(json_encode(\$JSON_INVALID_PARAMS));\r\n";
                    $parametersForSampleCall .= "SessionID=...";
                    $parametersList .= "\r\n\t\tSessionID (String)";
                }
                else {
                    $parametersMessage = substr($parametersMessage, 0, strlen($parametersMessage) - 2);
                    $parametersForSampleCall = substr($parametersForSampleCall, 0, strlen($parametersForSampleCall) - 1);
                    $parametersMessage .= ".";
                }

                $endpointCode_Create = "";

                //OnRequest function:
                $class = ucfirst($table);
                $onRequestFunctionCode_Create = "
    
    //The onRequest() function is called once all security constraints are passed and all parameters have been verified.
    //Important Notice: This function has been automatically generated based on your database.
    //Editing this function is OK, but not recommended.                              
    function onRequest() {
        \$JSON_DELETE_ERROR = array(STATUS => STATUS_ERROR, TITLE => DELETE_ERROR_TITLE, MESSAGE => DELETE_ERROR_MESSAGE);
        \$JSON_DELETE_SUCCESS = array(STATUS => STATUS_ERROR, TITLE => DELETE_SUCCESS_TITLE, MESSAGE => DELETE_SUCCESS_MESSAGE);    
           
        include_once(\"../../../Scripts/Entity Classes/PHP/".$class.".php\");
        \$objectID = ".$constructorParameters.";
        \$result = ".$class."::delete(\$objectID);
        if (\$result) die(json_encode(\$JSON_DELETE_SUCCESS));
        else die (json_encode(\$JSON_DELETE_ERROR));
       
    }
    
    /*!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!! DO NOT EDIT CODE BELOW THIS POINT !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!*/
    //Editing the code below will compromise the reliability and security of your API.
    
    ";

                //Create the constant fields:
                $strConstants = "
    //Locals:
    const ENDPOINT_NAME = \"API/".ucfirst($table)."/".ucfirst($endpointName)."\";
                
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
    const DELETE_SUCCESS_TITLE = \"Item deleted.\";
    const DELETE_SUCCESS_MESSAGE = \"Item deleted successfully.\";
    const DELETE_ERROR_TITLE = \"Item deletion failed.\";
    const DELETE_ERROR_MESSAGE = \"Failed to delete item.\";
    

    //JSON returns:
    \$JSON_INVALID_PARAMS = array(STATUS => STATUS_ERROR, TITLE => INVALID_PARAMS_TITLE, MESSAGE => INVALID_PARAMS_MESSAGE);
    \$JSON_TECHNICAL_ERROR = array(STATUS => STATUS_ERROR, TITLE => TECHNICAL_ERROR_TITLE, MESSAGE => TECHNICAL_ERROR_MESSAGE);
    \$JSON_AUTHORIZATION_ERROR = array(STATUS => STATUS_ERROR, TITLE => AUTHORIZATION_ERROR_TITLE, MESSAGE => AUTHORIZATION_ERROR_MESSAGE);
    
";


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
    if (\$result->num_rows <= 0) die(json_encode(\$JSON_AUTHORIZATION_ERROR));
    else {
        \$session = \$result->fetch_object();
        \$sqlGetUser = \"SELECT UserLevelID FROM Users WHERE UserID = \" . \$session->UserID;
        \$result = \$conn->query(\$sqlGetUser);
        \$user = \$result->fetch_object();
        \$allowed = false;
        foreach (\$allowedUserLevelIDs as \$id) {
            if (\$user->UserLevelID == \$id) {
                \$allowed = true; break;
            }//end if match
        }//end foreach UserLevelID
        if (!\$allowed) die(json_encode(\$JSON_AUTHORIZATION_ERROR));
    }//end if session found
    \$conn->close();
    ";

                //Endpoint instructions:

                $instructions_public = "This endpoint is public and requires no authorization.";
                $instructions_nonpublic = "This endpoint is not public and requires a Session ID to be provided for authorization.";

                $instructions_additional = "";
                if ($isPublicEndpoint)  $instructions_additional = $instructions_public;
                else $instructions_additional = $instructions_nonpublic;

                $instructions = "
                
/*
        ~~~~~~ API Endpoint Instructions ~~~~~~
        
        " . $instructions_additional . "
        
        Sample call for API/".ucfirst($table)."/".ucfirst($endpointName).":

            API/".ucfirst($table)."/".ucfirst($endpointName). $parametersForSampleCall . "

        /----------------------------------------------------------------/

        User Types/Levels who can access this endpoint:
         ".$allowedUsersInstructions."

        Call Parameters List:
        ".$parametersList."

        /----------------------------------------------------------------/


        This endpoint responds with JSON data in the following ways.

        Response Format:

        1) Response OK

            --> \"Status\": \"OK\"
            --> \"Title\": \"Item deleted successfully.\"
            --> \"Message\": \"Item retrieved successfully.\"

        2) Response ERROR
        
            --> \"Status\": \"Error\"
            --> \"Title\": \"Item deletion failed.\"
            --> \"Message\": \"Failed to delete item.\"

                (Invalid parameters)

            --> \"Status\": \"Error\"
            --> \"Title\": \"Invalid Parameters\"
            --> \"Message\": \"".$parametersMessage."\"

                (Technical error)

            --> \"Status\": \"Error\"
            --> \"Title\": \"Technical Error\"
            --> \"Message\": \"A technical error has occured. Please consult the system's administrator.\"

                (Invalid identification)

            --> \"Status\": \"Error\"
            --> \"Title\": \"Authorization Error\"
            --> \"Message\": \"You are not authorized to access this procedure. If you think you should be able to do so, please consult your system's administrator.\"

    */
                
                
                ";

                if ($isPublicEndpoint) $securityChecks = "";

                $funcCallAndConnClose = "\r\n\r\n\tonRequest(); ";

                $endpointCode_Create =
                    getHeader($endpointName, $table, $dbName) .
                    $instructions .
                    $onRequestFunctionCode_Create .
                    $strConstants .
                    $parameterChecks .
                    $includeOnceDBLogin .
                    $securityChecks .
                    $funcCallAndConnClose
                ;

                writeToPHPFile("../Generated/API/" . ucfirst($table) . "/" . ucfirst($endpointName) . "/", "index.php", $endpointCode_Create);

            }//end if delete endpoint

//------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------

            //Endpoint "SIZE OF":
            if (isset($_POST["generate_" . $table . "_getSize"])) {

                $endpointName = "getSize";

                //Create the allowedUserLevelIDs Array:
                $export_allowedUserLevelIDsArray = "\$allowedUserLevelIDs = array(1, ";
                $isPublicEndpoint = false;
                $allowedUsersInstructions = "Administrator (1)";
                foreach ($userLevels as $userLevel) {
                    if (isset($_POST[$endpointName. "_" . $table . "___" . $userLevel->UserLevelName])) {
                        if ($userLevel->UserLevelID == 4) $isPublicEndpoint = true;
                        $export_allowedUserLevelIDsArray .= $userLevel->UserLevelID . ", ";
                        $allowedUsersInstructions .= ", " . $userLevel->UserLevelName . "(" . $userLevel->UserLevelID . ")";
                    }//end if
                }//end foreach userlevel
                $export_allowedUserLevelIDsArray = substr($export_allowedUserLevelIDsArray, 0, strlen($export_allowedUserLevelIDsArray) - 2);
                $export_allowedUserLevelIDsArray .= ");";

                //Create the parameters lists:
                $parametersMessage = "Invalid parameters. Expected Parameters: ";
                $parameterChecks = "\r\n\t//-- PARAMETER CHECKS\r\n\r\n";
                $parametersForSampleCall = "";
                $parametersList = "";
                $constructorParameters = "";
                /*$primaryKeyField = null;
                foreach ($allFields as $field) {
                    if ($field->Key == "PRI") {
                        $primaryKeyField = $field;
                        $parametersMessage .= ucfirst($field->Field) . " (".toJavaType($field->Type)."), ";
                        $parameterChecks .= "\tif (!isset(\$_POST[\"".ucfirst($field->Field)."\"]) || \$_POST[\"".ucfirst($field->Field)."\"] == \"\") die(json_encode(\$JSON_INVALID_PARAMS));\r\n";
                        $parametersForSampleCall .= ucfirst($field->Field) . "...&";
                        $parametersList .= "\r\n\t\t" . ucfirst($field->Field) . " (" . toJavaType($field->Type) . ")";
                        $constructorParameters .= "\$_POST[\"" . ucfirst($field->Field) . "\"], ";
                    }
                }//end foreach field*/
                $constructorParameters = substr($constructorParameters, 0, strlen($constructorParameters) - 2);

                //Add session parameter:
                if (!$isPublicEndpoint) {
                    $parametersMessage .= "SessionID (String)";
                    $parameterChecks .= "\tif (!isset(\$_POST[\"SessionID\"]) || \$_POST[\"SessionID\"] == \"\") die(json_encode(\$JSON_INVALID_PARAMS));\r\n";
                    $parametersForSampleCall .= "?SessionID=...";
                    $parametersList .= "\r\n\t\tSessionID (String)";
                }
                else {
                    $parametersMessage .= ".";
                }

                $endpointCode_Create = "";

                //OnRequest function:
                $class = ucfirst($table);
                $onRequestFunctionCode_Create = "
    
    //The onRequest() function is called once all security constraints are passed and all parameters have been verified.
    //Important Notice: This function has been automatically generated based on your database.
    //Editing this function is OK, but not recommended.                              
    function onRequest() {
        \$JSON_GETSIZE_ERROR = array(STATUS => STATUS_ERROR, TITLE => GETSIZE_ERROR_TITLE, MESSAGE => GETSIZE_ERROR_MESSAGE);
        \$JSON_GETSIZE_SUCCESS = array(STATUS => STATUS_OK, TITLE => GETSIZE_SUCCESS_TITLE, MESSAGE => GETSIZE_SUCCESS_MESSAGE);    
           
        include_once(\"../../../Scripts/Entity Classes/PHP/".$class.".php\");
;
        \$result = ".$class."::getSize();
        if (\$result !== false) {
        
            \$returnArray = \$JSON_GETSIZE_SUCCESS;
            \$statusJson = json_encode(\$returnArray);
            \$statusJson = substr(\$statusJson, 0, strlen(\$statusJson) - 1);
            \$objectData = \", \\\"Size\\\": \" .\$result. \"}\";
            \$combinedReturn = \$statusJson . \$objectData;
            die (\$combinedReturn);
        }
        else die (json_encode(\$JSON_GETSIZE_ERROR));
    }
    
    /*!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!! DO NOT EDIT CODE BELOW THIS POINT !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!*/
    //Editing the code below will compromise the reliability and security of your API.
    
    ";

                //Create the constant fields:
                $strConstants = "
    //Locals:
    const ENDPOINT_NAME = \"API/".ucfirst($table)."/".ucfirst($endpointName)."\";
                
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
    const GETSIZE_SUCCESS_TITLE = \"Size retrieved.\";
    const GETSIZE_SUCCESS_MESSAGE = \"Size of specified table-type retrieved successfully.\";
    const GETSIZE_ERROR_TITLE = \"Size retrieval failed.\";
    const GETSIZE_ERROR_MESSAGE = \"Failed to retrieve table-type size.\";
    

    //JSON returns:
    \$JSON_INVALID_PARAMS = array(STATUS => STATUS_ERROR, TITLE => INVALID_PARAMS_TITLE, MESSAGE => INVALID_PARAMS_MESSAGE);
    \$JSON_TECHNICAL_ERROR = array(STATUS => STATUS_ERROR, TITLE => TECHNICAL_ERROR_TITLE, MESSAGE => TECHNICAL_ERROR_MESSAGE);
    \$JSON_AUTHORIZATION_ERROR = array(STATUS => STATUS_ERROR, TITLE => AUTHORIZATION_ERROR_TITLE, MESSAGE => AUTHORIZATION_ERROR_MESSAGE);
    
";


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
    if (\$result->num_rows <= 0) die(json_encode(\$JSON_AUTHORIZATION_ERROR));
    else {
        \$session = \$result->fetch_object();
        \$sqlGetUser = \"SELECT UserLevelID FROM Users WHERE UserID = \" . \$session->UserID;
        \$result = \$conn->query(\$sqlGetUser);
        \$user = \$result->fetch_object();
        \$allowed = false;
        foreach (\$allowedUserLevelIDs as \$id) {
            if (\$user->UserLevelID == \$id) {
                \$allowed = true; break;
            }//end if match
        }//end foreach UserLevelID
        if (!\$allowed) die(json_encode(\$JSON_AUTHORIZATION_ERROR));
    }//end if session found
    \$conn->close();
    ";

                //Endpoint instructions:

                $instructions_public = "This endpoint is public and requires no authorization.";
                $instructions_nonpublic = "This endpoint is not public and requires a Session ID to be provided for authorization.";

                $instructions_additional = "";
                if ($isPublicEndpoint)  $instructions_additional = $instructions_public;
                else $instructions_additional = $instructions_nonpublic;

                $instructions = "
                
/*
        ~~~~~~ API Endpoint Instructions ~~~~~~
        
        " . $instructions_additional . "
        
        Sample call for API/".ucfirst($table)."/".ucfirst($endpointName).":

            API/".ucfirst($table)."/".ucfirst($endpointName). $parametersForSampleCall . "

        /----------------------------------------------------------------/

        User Types/Levels who can access this endpoint:
         ".$allowedUsersInstructions."

        Call Parameters List:
        ".$parametersList."

        /----------------------------------------------------------------/


        This endpoint responds with JSON data in the following ways.

        Response Format:

        1) Response OK

            --> \"Status\": \"OK\"
            --> \"Title\": \"Size retrieved.\";
            --> \"Message\": \"Size of specified table-type retrieved successfully.\";

        2) Response ERROR
        
            --> \"Status\": \"Error\"
            --> \"Title\": \"Size retrieval failed.\";
            --> \"Message\": \"Failed to retrieve table-type size.\";

                (Invalid parameters)

            --> \"Status\": \"Error\"
            --> \"Title\": \"Invalid Parameters\"
            --> \"Message\": \"".$parametersMessage."\"

                (Technical error)

            --> \"Status\": \"Error\"
            --> \"Title\": \"Technical Error\"
            --> \"Message\": \"A technical error has occured. Please consult the system's administrator.\"

                (Invalid identification)

            --> \"Status\": \"Error\"
            --> \"Title\": \"Authorization Error\"
            --> \"Message\": \"You are not authorized to access this procedure. If you think you should be able to do so, please consult your system's administrator.\"

    */
                
                
                ";

                if ($isPublicEndpoint) $securityChecks = "";

                $funcCallAndConnClose = "\r\n\r\n\tonRequest(); ";

                $endpointCode_Create =
                    getHeader($endpointName, $table, $dbName) .
                    $instructions .
                    $onRequestFunctionCode_Create .
                    $strConstants .
                    $parameterChecks .
                    $includeOnceDBLogin .
                    $securityChecks .
                    $funcCallAndConnClose
                ;

                writeToPHPFile("../Generated/API/" . ucfirst($table) . "/" . ucfirst($endpointName) . "/", "index.php", $endpointCode_Create);

            }//end if sizeof endpoint

//------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------


            //Endpoint "IS EMPTY":
            if (isset($_POST["generate_" . $table . "_isEmpty"])) {

                $endpointName = "isEmpty";

                //Create the allowedUserLevelIDs Array:
                $export_allowedUserLevelIDsArray = "\$allowedUserLevelIDs = array(1, ";
                $isPublicEndpoint = false;
                $allowedUsersInstructions = "Administrator (1)";
                foreach ($userLevels as $userLevel) {
                    if (isset($_POST[$endpointName. "_" . $table . "___" . $userLevel->UserLevelName])) {
                        if ($userLevel->UserLevelID == 4) $isPublicEndpoint = true;
                        $export_allowedUserLevelIDsArray .= $userLevel->UserLevelID . ", ";
                        $allowedUsersInstructions .= ", " . $userLevel->UserLevelName . "(" . $userLevel->UserLevelID . ")";
                    }//end if
                }//end foreach userlevel
                $export_allowedUserLevelIDsArray = substr($export_allowedUserLevelIDsArray, 0, strlen($export_allowedUserLevelIDsArray) - 2);
                $export_allowedUserLevelIDsArray .= ");";

                //Create the parameters lists:
                $parametersMessage = "Invalid parameters. Expected Parameters: ";
                $parameterChecks = "\r\n\t//-- PARAMETER CHECKS\r\n\r\n";
                $parametersForSampleCall = "";
                $parametersList = "";
                $constructorParameters = "";
                /*$primaryKeyField = null;
                foreach ($allFields as $field) {
                    if ($field->Key == "PRI") {
                        $primaryKeyField = $field;
                        $parametersMessage .= ucfirst($field->Field) . " (".toJavaType($field->Type)."), ";
                        $parameterChecks .= "\tif (!isset(\$_POST[\"".ucfirst($field->Field)."\"]) || \$_POST[\"".ucfirst($field->Field)."\"] == \"\") die(json_encode(\$JSON_INVALID_PARAMS));\r\n";
                        $parametersForSampleCall .= ucfirst($field->Field) . "...&";
                        $parametersList .= "\r\n\t\t" . ucfirst($field->Field) . " (" . toJavaType($field->Type) . ")";
                        $constructorParameters .= "\$_POST[\"" . ucfirst($field->Field) . "\"], ";
                    }
                }//end foreach field*/
                $constructorParameters = substr($constructorParameters, 0, strlen($constructorParameters) - 2);

                //Add session parameter:
                if (!$isPublicEndpoint) {
                    $parametersMessage .= "SessionID (String)";
                    $parameterChecks .= "\tif (!isset(\$_POST[\"SessionID\"]) || \$_POST[\"SessionID\"] == \"\") die(json_encode(\$JSON_INVALID_PARAMS));\r\n";
                    $parametersForSampleCall .= "?SessionID=...";
                    $parametersList .= "\r\n\t\tSessionID (String)";
                }
                else {
                    $parametersMessage .= ".";
                }

                $endpointCode_Create = "";

                //OnRequest function:
                $class = ucfirst($table);
                $onRequestFunctionCode_Create = "
    
    //The onRequest() function is called once all security constraints are passed and all parameters have been verified.
    //Important Notice: This function has been automatically generated based on your database.
    //Editing this function is OK, but not recommended.                              
    function onRequest() {
        \$JSON_ISEMPTY_ERROR = array(STATUS => STATUS_ERROR, TITLE => ISEMPTY_ERROR_TITLE, MESSAGE => ISEMPTY_ERROR_MESSAGE);
        \$JSON_ISEMPTY_SUCCESS = array(STATUS => STATUS_OK, TITLE => ISEMPTY_SUCCESS_TITLE, MESSAGE => ISEMPTY_SUCCESS_MESSAGE);    
           
        include_once(\"../../../Scripts/Entity Classes/PHP/".$class.".php\");
;
        \$result = ".$class."::getSize();
       
        if (\$result === false) die(json_encode(\$JSON_ISEMPTY_ERROR));
        
        if (\$result == 0) {
            \$returnArray = \$JSON_ISEMPTY_SUCCESS;
            \$statusJson = json_encode(\$returnArray);
            \$statusJson = substr(\$statusJson, 0, strlen(\$statusJson) - 1);
            \$objectData = \", \\\"IsEmpty\\\": true }\";
            \$combinedReturn = \$statusJson . \$objectData;
            die (\$combinedReturn);
        }
        else {
            \$returnArray = \$JSON_ISEMPTY_SUCCESS;
            \$statusJson = json_encode(\$returnArray);
            \$statusJson = substr(\$statusJson, 0, strlen(\$statusJson) - 1);
            \$objectData = \", \\\"IsEmpty\\\": false }\";
            \$combinedReturn = \$statusJson . \$objectData;
            die (\$combinedReturn);
        }
    }
    
    /*!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!! DO NOT EDIT CODE BELOW THIS POINT !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!*/
    //Editing the code below will compromise the reliability and security of your API.
    
    ";

                //Create the constant fields:
                $strConstants = "
    //Locals:
    const ENDPOINT_NAME = \"API/".ucfirst($table)."/".ucfirst($endpointName)."\";
                
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
    const ISEMPTY_SUCCESS_TITLE = \"Table-Type empty.\";
    const ISEMPTY_SUCCESS_MESSAGE = \"The table-type is empty.\";
    const ISEMPTY_ERROR_TITLE = \"Failed.\";
    const ISEMPTY_ERROR_MESSAGE = \"Failed to retrieve if table-type is empty.\";
    

    //JSON returns:
    \$JSON_INVALID_PARAMS = array(STATUS => STATUS_ERROR, TITLE => INVALID_PARAMS_TITLE, MESSAGE => INVALID_PARAMS_MESSAGE);
    \$JSON_TECHNICAL_ERROR = array(STATUS => STATUS_ERROR, TITLE => TECHNICAL_ERROR_TITLE, MESSAGE => TECHNICAL_ERROR_MESSAGE);
    \$JSON_AUTHORIZATION_ERROR = array(STATUS => STATUS_ERROR, TITLE => AUTHORIZATION_ERROR_TITLE, MESSAGE => AUTHORIZATION_ERROR_MESSAGE);
    
";


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
    if (\$result->num_rows <= 0) die(json_encode(\$JSON_AUTHORIZATION_ERROR));
    else {
        \$session = \$result->fetch_object();
        \$sqlGetUser = \"SELECT UserLevelID FROM Users WHERE UserID = \" . \$session->UserID;
        \$result = \$conn->query(\$sqlGetUser);
        \$user = \$result->fetch_object();
        \$allowed = false;
        foreach (\$allowedUserLevelIDs as \$id) {
            if (\$user->UserLevelID == \$id) {
                \$allowed = true; break;
            }//end if match
        }//end foreach UserLevelID
        if (!\$allowed) die(json_encode(\$JSON_AUTHORIZATION_ERROR));
    }//end if session found
    \$conn->close();
    ";

                //Endpoint instructions:

                $instructions_public = "This endpoint is public and requires no authorization.";
                $instructions_nonpublic = "This endpoint is not public and requires a Session ID to be provided for authorization.";

                $instructions_additional = "";
                if ($isPublicEndpoint)  $instructions_additional = $instructions_public;
                else $instructions_additional = $instructions_nonpublic;

                $instructions = "
                
/*
        ~~~~~~ API Endpoint Instructions ~~~~~~
        
        " . $instructions_additional . "
        
        Sample call for API/".ucfirst($table)."/".ucfirst($endpointName).":

            API/".ucfirst($table)."/".ucfirst($endpointName). $parametersForSampleCall . "

        /----------------------------------------------------------------/

        User Types/Levels who can access this endpoint:
         ".$allowedUsersInstructions."

        Call Parameters List:
        ".$parametersList."

        /----------------------------------------------------------------/


        This endpoint responds with JSON data in the following ways.

        Response Format:

        1) Response OK

            --> \"Status\": \"OK\"
            --> \"Title\": \"Table-Type empty.\";
            --> \"Message\": \"The table-type is empty.\";

        2) Response ERROR
        
            --> \"Status\": \"Error\"
            --> \"Title\": \"Failed.\";
            --> \"Message\": \"Failed to retrieve if table-type is empty.\";

                (Invalid parameters)

            --> \"Status\": \"Error\"
            --> \"Title\": \"Invalid Parameters\"
            --> \"Message\": \"".$parametersMessage."\"

                (Technical error)

            --> \"Status\": \"Error\"
            --> \"Title\": \"Technical Error\"
            --> \"Message\": \"A technical error has occured. Please consult the system's administrator.\"

                (Invalid identification)

            --> \"Status\": \"Error\"
            --> \"Title\": \"Authorization Error\"
            --> \"Message\": \"You are not authorized to access this procedure. If you think you should be able to do so, please consult your system's administrator.\"

    */
                
                
                ";

                if ($isPublicEndpoint) $securityChecks = "";

                $funcCallAndConnClose = "\r\n\r\n\tonRequest(); ";

                $endpointCode_Create =
                    getHeader($endpointName, $table, $dbName) .
                    $instructions .
                    $onRequestFunctionCode_Create .
                    $strConstants .
                    $parameterChecks .
                    $includeOnceDBLogin .
                    $securityChecks .
                    $funcCallAndConnClose
                ;

                writeToPHPFile("../Generated/API/" . ucfirst($table) . "/" . ucfirst($endpointName) . "/", "index.php", $endpointCode_Create);

            }//end if sizeof endpoint

//------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------

        }//end if generate table is set


    }//end foreach table


    header("Location: ../generateAPIStep3.php?status=Generated"); exit();


?>