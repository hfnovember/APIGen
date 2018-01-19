<?php

    //Header

    /*

        This endpoint is not public and requires a Session ID to be provided for authentication.

            API/#entityName/#endpointName/?SessionID=...

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
            --> "Title": "Authentication Error"
            --> "Message": "..."


        Sample call for #apiEndpointName:

            API/#entityName/#endpointName/###

    */

    //---------------------------------- STANDARD API ENDPOINT CHECKS ---------------------------------//

    // It is strongly recommended not to edit this part of the code.

    //TODO: Check for parameters. If missing or empty give Invalid Parameters errors. All parameters are received using POST.

    include_once("../../../Scripts/DBLogin.php");

    //Allowed user levels:
    const allowedUserLevelIDs = array(1, 2, 3);

    //Validate session if a session is required (not public)
    $sessionID = $_POST["SessionID"];
    $conn = dbLogin();
    $sqlSessions = "SELECT * FROM Sessions WHERE SessionID = '" . $sessionID . "'";
    $result = $conn->query($sqlSessions);
    if ($result === FALSE) {
        //TODO Give Authentication error response.
    }
    else {
        //TODO: Determine if this session ID has a user who has a level which is valid for this page.
    }

    //--------------------------- PAGE-SPECIFIC API ENDPOINT FUNCTIONALITY --------------------------//

    // It is OK to edit this part of the code.

    //TODO: Begin processing the request according to parameters. Bring the necessary data forward. If failed, give technical errors.
                //If data not found, return OK with null data object.



    // Make sure you echo a JSON string and nothing more.




?>