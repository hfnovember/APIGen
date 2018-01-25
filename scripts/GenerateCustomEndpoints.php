<?php

    function getHeader($endpointName, $tableName, $dbName) {
        return getGeneratorHeader($dbName, "API/" . $endpointName . "/index.php", $tableName, "");
    }

    session_start();

    $dbName = $_SESSION["tempDBName"];
    $dbHostIP = $_SESSION["tempHostIP"];
    $dbUsername = $_SESSION["tempDBUser"];
    $dbPassword = $_SESSION["tempDBPassword"];

    $endpointNames = explode("|", $_SESSION["step3_endpointNames"]);
    $endpointAccess = explode("|", $_SESSION["step3_endpointAccess"]);
    $endpointParameters = explode("|", $_SESSION["step3_endpointParameters"]);

    if (sizeof($endpointNames) != sizeof($endpointAccess) && sizeof($endpointAccess) != sizeof($endpointParameters)) {
        header("Location: ../generateAPIStep3.php?status=InconsistentSizes"); exit();
    }

    $_SESSION["step3_endpointNames"] = "";
    $_SESSION["step3_endpointAccess"] = "";
    $_SESSION["step3_endpointParameters"] = "";

    $tempEndpointNames = array();
    $tempEndpointAccess = array();
    $tempEndpointParameters = array();

    //Eliminate first blank entries:
    for($i = 0; $i < sizeof($endpointNames); $i++) {
        if ($i != 0 && $i < (sizeof($endpointNames) - 1)) {
            $tempEndpointNames[$i-1] = $endpointNames[$i];
            $tempEndpointAccess[$i-1] = $endpointAccess[$i];
            $tempEndpointParameters[$i-1] = $endpointParameters[$i];
        }

    }

    $endpointNames = $tempEndpointNames;
    $endpointAccess = $tempEndpointAccess;
    $endpointParameters = $tempEndpointParameters;

    //Verify that all names are valid:
    foreach($endpointNames as $name) {
        if (!ctype_alpha($name)) {
            header("Location: ../generateAPIStep3.php?status=NameInvalid"); exit();
        }
    }

    //Verify that all user levels are valid:
    $conn = new mysqli($dbHostIP, $dbUsername, $dbPassword, $dbName);
    if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);
    $sql_getUserLevels = "SELECT * FROM UserLevels";
    $result = $conn->query($sql_getUserLevels);
    $userLevels = array();
    while ($row = $result->fetch_object()) array_push($userLevels, $row);

    foreach ($endpointAccess as $access) {

        $passedLevels = explode(",", $access);
        foreach ($passedLevels as $level) {
            $valid = false;
            foreach ($userLevels as $userLevel) {
                if ($userLevel->UserLevelID == $level) {
                    $valid = true;
                    break;
                }
            }
            if (!$valid) {
                header("Location: ../generateAPIStep3.php?status=LevelInvalid");
                exit();
            }
        }
    }

    //Verify that all parameters are valid:
    foreach ($endpointParameters as $parameter) {
        $parameters = explode(",", $parameter);
        foreach ($parameters as $param) {
            $param = trim($param);
            if (!ctype_alnum($param) || !ctype_alpha(substr($param, 0, 1))) {
                header("Location: ../generateAPIStep3.php?status=ParametersInvalid");
                exit();
            }
        }
    }

//------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
//CUSTOM ENDPOINT GENERATION:

for ($i = 0; $i < sizeof($endpointNames); $i++) {

    $endpointName = ucfirst($endpointNames[$i]);

    //Create the allowedUserLevelIDs Array:
    $export_allowedUserLevelIDsArray = "\$allowedUserLevelIDs = array(1, ";
    $isPublicEndpoint = false;
    $allowedUsersInstructions = "Administrator (1)";
    $currentLevels = explode(",", $endpointAccess[$i]);
    foreach ($currentLevels as $userLevel) {
        if ($userLevel != 1) {
            if ($userLevel == 4) $isPublicEndpoint = true;
            $export_allowedUserLevelIDsArray .= $userLevel . ", ";
            $userLevelName = "";
            foreach ($userLevels as $userLevelX) {
                if ($userLevel == $userLevelX->UserLevelID) {
                    $userLevelName .= $userLevelX->UserLevelName;
                    break;
                }
            }
            $allowedUsersInstructions .= ", " . $userLevelName . "(" . $userLevel . ")";
        }
    }//end foreach userlevel
    $export_allowedUserLevelIDsArray = substr($export_allowedUserLevelIDsArray, 0, strlen($export_allowedUserLevelIDsArray) - 2);
    $export_allowedUserLevelIDsArray .= ");";

    //Create the parameters lists:
    $parametersMessage = "Invalid parameters. Expected Parameters: ";
    $parameterChecks = "\r\n\t//-- PARAMETER CHECKS\r\n\r\n";
    $parametersForSampleCall = "?";
    $parametersList = "";
    $currentParams = explode(",", $endpointParameters[$i]);
    foreach ($currentParams as $px) {
        $px = ucfirst($px);
        $parametersMessage .= $px . " (Type), ";
        $parameterChecks .= "\tif (!isset(\$_POST[\"".$px."\"]) || \$_POST[\"".$px."\"] == \"\") die(json_encode(\$JSON_INVALID_PARAMS));\r\n";
        $parametersForSampleCall .= $px . "...&";
        $parametersList .= "\r\n\t\t" . $px . " (Type)";
    }//end foreach field

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
    $onRequestFunctionCode_Create = "
    
    //The onRequest() function is called once all security constraints are passed and all parameters have been verified.
    //You may edit this function to implement your custom endpoint's logic.                            
    function onRequest() {
        
        //TODO: Implement your custom endpoint.
        
    }
    
    /*!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!! DO NOT EDIT CODE BELOW THIS POINT !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!*/
    //Editing the code below will compromise the reliability and security of your API.
    
    ";

    //Create the constant fields:
    $strConstants = "
    //Locals:
    const ENDPOINT_NAME = \"API/Custom/".$endpointName."\";
                
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

    //TODO: Add any custom titles and messages here.

    

    //JSON returns:
    \$JSON_INVALID_PARAMS = array(STATUS => STATUS_ERROR, TITLE => INVALID_PARAMS_TITLE, MESSAGE => INVALID_PARAMS_MESSAGE);
    \$JSON_TECHNICAL_ERROR = array(STATUS => STATUS_ERROR, TITLE => TECHNICAL_ERROR_TITLE, MESSAGE => TECHNICAL_ERROR_MESSAGE);
    \$JSON_AUTHORIZATION_ERROR = array(STATUS => STATUS_ERROR, TITLE => AUTHORIZATION_ERROR_TITLE, MESSAGE => AUTHORIZATION_ERROR_MESSAGE);
    
    //TODO: Add any custom responses here.
    
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
        
        Sample call for API/Custom/".$endpointName."

            API/Custom/".$endpointName. "/" . $parametersForSampleCall . "

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

    include_once("GeneratorUtils.php");

    $endpointCode_Create =
        getHeader($endpointName, "Custom", $dbName) .
        $instructions .
        $onRequestFunctionCode_Create .
        $strConstants .
        $parameterChecks .
        $includeOnceDBLogin .
        $securityChecks .
        $funcCallAndConnClose
    ;

    writeToPHPFile("../Generated/API/Custom/" . $endpointName . "/", "index.php", $endpointCode_Create);
    echo "<p style='font-family: consolas; text-align: center;'><b>Generated:</b> " . "Custom/" . $endpointName . "</p>";

}//end foreach endpoint

//------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------

header("Location: ../index.php?status=APIGenerated"); exit();



?>