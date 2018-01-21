<?php

    //Header

    /*

        This endpoint is not public and requires a Session ID to be provided for authorization.

            API/#entityName/#endpointName/?SessionID=...

                ~~~~~~~~~~~~~~~~~~~~~~~~~

        This endpoint is public and requires no authorization.

        /----------------------------------------------------------------

        Valid UserLevels are: ### (#userLevelName), ### (#userLevelName), ### (#userLevelName)

        Call Parameters List:
        1) #Param1 (#paramType) : #description
        2) #Param2 (#paramType) : #description
        3) #Param3 (#paramType) : #description

        ------------------------------------------------------------------


        This endpoint responds with JSON data in the following ways.

        Response Format:

        1) Response OK

            --> "Status": "OK"
            --> "Data": ...             (Returned dataset)

        2) Response ERROR

                (Invalid parameters)

            --> "Status": "Error"
            --> "Title": "Invalid Parameters"
            --> "Message": "..."

                (Technical error)

            --> "Status": "Error"
            --> "Title": "Technical Error"
            --> "Message": "..."

                (Invalid identification)

            --> "Status": "Error"
            --> "Title": "Authorization Error"
            --> "Message": "..."


        Sample call for #apiEndpointName:

            API/#entityName/#endpointName/###

        ------------------------------------------------------------------

    */

    //---------------------------------- STANDARD API ENDPOINT CHECKS ---------------------------------//

    // It is strongly recommended not to edit this part of the code. Doing so may compromise the security of your API calls.

    //Locals:
    const ENDPOINT_NAME = ""; //TODO Endpoint name.
    const ENDPOINT_SAMPLE_CALL = "API/"; //TODO Endpoint Sample call.

    //Statuses:
    const STATUS_ERROR = "Error";
    const STATUS_OK = "OK";

    //Titles/Messages:
    const STATUS = "Status";
    const TITLE = "Title";
    const MESSAGE = "Message";
    const DATA = "Data";
    const INVALID_PARAMS_TITLE = "Invalid Parameters";
    const INVALID_PARAMS_MESSAGE = ""; //TODO Expected params.
    const TECHNICAL_ERROR_TITLE = "Technical Error";
    const TECHNICAL_ERROR_MESSAGE = "A technical error has occured. Please consult the system's administrator.";
    const AUTHORIZATION_ERROR_TITLE = "Authorization Error";
    const AUTHORIZATION_ERROR_MESSAGE = "You are not authorized to access this procedure. If you think you should be able to do so, please consult your system's administrator.";

    //JSON returns:
    const JSON_INVALID_PARAMS = array(STATUS => STATUS_ERROR, TITLE => INVALID_PARAMS_TITLE, MESSAGE => INVALID_PARAMS_MESSAGE);
    const JSON_TECHNICAL_ERROR = array(STATUS => STATUS_ERROR, TITLE => TECHNICAL_ERROR_TITLE, MESSAGE => TECHNICAL_ERROR_MESSAGE);
    const JSON_AUTHORIZATION_ERROR = array(STATUS => STATUS_ERROR, TITLE => AUTHORIZATION_ERROR_TITLE, MESSAGE => AUTHORIZATION_ERROR_MESSAGE);

    //-- PARAMETER CHECKS

    //TODO: Check for parameters. If missing or empty give Invalid Parameters errors. All parameters are received using POST.

    if (!isset($_POST["sampleParam"]) || $_POST["sampleParam"] == "") die(json_encode(JSON_INVALID_PARAMS));
    if (!isset($_POST["sampleParam2"]) || $_POST["sampleParam2"] == "" || !is_int($_POST["sampleParam2"])) die(json_encode(JSON_INVALID_PARAMS));
    //....more  params

    include_once("../../../Scripts/DBLogin.php");

    //-- SECURITY CHECKS

    //Allowed user levels:
    const allowedUserLevelIDs = array(1, 2, 3); //TODO Set valid UserLevelIDs.

    //Validate session if a session is required (not public)
    $sessionID = $_POST["SessionID"];
    $conn = dbLogin();
    $sqlSessions = "SELECT * FROM Sessions WHERE SessionID = '" . $sessionID . "'";
    $result = $conn->query($sqlSessions);
    if ($result === FALSE) die(json_encode(JSON_AUTHORIZATION_ERROR));
    else {
        $session = $result->fetch_object();
        $sqlGetUser = "SELECT UserLevelID FROM Users WHERE UserID = " . $session->UserID;
        $result = $conn->query($sqlGetUser);
        $user = $result->fetch_object();
        $allowed = false;
        foreach (allowedUserLevelIDs as $id) {
            if ($user->UserLevelID == $id) {
                $allowed = true; break;
            }//end if match
        }//end foreach UserLevelID
        if (!$allowed) die(json_encode(JSON_AUTHORIZATION_ERROR));
    }//end if session found

    //--------------------------- PAGE-SPECIFIC API ENDPOINT FUNCTIONALITY --------------------------//

    // It is OK to edit this part of the code.

    //TODO: Begin processing the request according to parameters. Bring the necessary data forward. If failed, give technical errors.
                //If data not found, return OK with null data object.



    // Make sure you echo a JSON string and nothing more.


    //----------------------------------------------------------------------------------------------//

    $conn->close();

?>