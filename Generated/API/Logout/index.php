<?php


/**********************************************************************************/
/*** THIS FILE HAS BEEN AUTOMATICALLY GENERATED BY THE PANICKAPPS API GENERATOR ***/

/*                It is HIGHLY suggested that you do not edit this file.          */

//  DATABASE:     Nicos
//  FILE:         Logout
//  DATETIME:     2018-01-24 01:05:47am
//  DESCRIPTION:  Logs the user out of the system.

/**********************************************************************************/
            


        
    //Locals:
    const ENDPOINT_NAME = "";
    const ENDPOINT_SAMPLE_CALL = "API/"; //TODO Endpoint Sample call
                
    //Statuses:
    const STATUS_ERROR = "Error";
    const STATUS_OK = "OK";

    //Titles/Messages:
    const STATUS = "Status";
    const TITLE = "Title";
    const MESSAGE = "Message";
    const DATA = "Data";
    const SESSIONID = "SessionID";
    const INVALID_PARAMS_TITLE = "Invalid Parameters";
    const INVALID_PARAMS_MESSAGE = "Invalid parameters. Expected Parameters: Username (String), Password (String).";
    const TECHNICAL_ERROR_TITLE = "Technical Error";
    const TECHNICAL_ERROR_MESSAGE = "A technical error has occured. Please consult the system's administrator.";
    const AUTHORIZATION_ERROR_TITLE = "Authorization Error";
    const AUTHORIZATION_ERROR_MESSAGE = "You are not authorized to access this procedure. If you think you should be able to do so, please consult your system's administrator.";
    const LOGOUT_SUCCESS_TITLE = "Logout Success.";
    const LOGOUT_SUCCESS_MESSAGE = "You have successfully logged out.";
    const LOGOUT_ERROR_TITLE = "Logout Failed.";
    const LOGOUT_ERROR_MESSAGE = "Failed to log out.";

    //JSON returns:
    $JSON_INVALID_PARAMS = array(STATUS => STATUS_ERROR, TITLE => INVALID_PARAMS_TITLE, MESSAGE => INVALID_PARAMS_MESSAGE);
    $JSON_TECHNICAL_ERROR = array(STATUS => STATUS_ERROR, TITLE => TECHNICAL_ERROR_TITLE, MESSAGE => TECHNICAL_ERROR_MESSAGE);
    $JSON_AUTHORIZATION_ERROR = array(STATUS => STATUS_ERROR, TITLE => AUTHORIZATION_ERROR_TITLE, MESSAGE => AUTHORIZATION_ERROR_MESSAGE);
    $JSON_LOGOUT_SUCCESS = array(STATUS => STATUS_OK, TITLE => LOGOUT_SUCCESS_TITLE, MESSAGE => LOGOUT_SUCCESS_MESSAGE);
    $JSON_LOGOUT_ERROR = array(STATUS => STATUS_ERROR, TITLE => LOGOUT_ERROR_TITLE, MESSAGE => LOGOUT_ERROR_MESSAGE);
    
    if (!isset($_POST["SessionID"]) || $_POST["SessionID"] == "") die(json_encode($JSON_INVALID_PARAMS));
    $sessionID = $_POST["SessionID"];

    include_once("../../Scripts/DBLogin.php");
    $conn = dbLogin();
    
    $sql = "SELECT * FROM Sessions WHERE SessionID = \"" . $sessionID . "\"";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        $sql = "DELETE FROM Sessions WHERE SessionID = \"" . $sessionID . "\"";
        $result = $conn->query($sql);
        if ($result === TRUE) die(json_encode($JSON_LOGOUT_SUCCESS));
        else die (json_encode($JSON_LOGOUT_ERROR));
    }
    else die (json_encode($JSON_LOGOUT_ERROR));
        
        

?>