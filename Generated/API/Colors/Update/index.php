<?php


/**********************************************************************************/
/*** THIS FILE HAS BEEN AUTOMATICALLY GENERATED BY THE PANICKAPPS API GENERATOR ***/

/*                It is HIGHLY suggested that you do not edit this file.          */

//  DATABASE:     TestDB
//  FILE:         API/update/index.php
//  TABLE:        colors
//  DATETIME:     2018-01-25 02:02:24am
//  DESCRIPTION:  N/A

/**********************************************************************************/
			


                
/*
        ~~~~~~ API Endpoint Instructions ~~~~~~
        
        This endpoint is public and requires no authorization.
        
        Sample call for API/Colors/Update:

            API/Colors/Update?Id...&Name...&Hex...

        /----------------------------------------------------------------/

        User Types/Levels who can access this endpoint:
         Administrator (1), Manager(2), Public(4), User(3)

        Call Parameters List:
        
		Id (int)
		Name (String)
		Hex (String)

        /----------------------------------------------------------------/


        This endpoint responds with JSON data in the following ways.

        Response Format:

        1) Response OK

            --> "Status": "OK"
            --> "Title": "Item updated."
            --> "Message": "Item updated successfully."

        2) Response ERROR
        
            --> "Status": "Error"
            --> "Title": "Item update failed."
            --> "Message": "Failed to update item. Check that this item is not already in this state, exists and has the ID provided."

                (Invalid parameters)

            --> "Status": "Error"
            --> "Title": "Invalid Parameters"
            --> "Message": "Invalid parameters. Expected Parameters: Id (int), Name (String), Hex (String)."

                (Technical error)

            --> "Status": "Error"
            --> "Title": "Technical Error"
            --> "Message": "A technical error has occured. Please consult the system's administrator."

                (Invalid identification)

            --> "Status": "Error"
            --> "Title": "Authorization Error"
            --> "Message": "You are not authorized to access this procedure. If you think you should be able to do so, please consult your system's administrator."

    */
                
                
                
    
    //The onRequest() function is called once all security constraints are passed and all parameters have been verified.
    //Important Notice: This function has been automatically generated based on your database.
    //Editing this function is OK, but not recommended.                              
    function onRequest() {
        $JSON_UPDATE_SUCCESS = array(STATUS => STATUS_OK, TITLE => UPDATE_SUCCESS_TITLE, MESSAGE => UPDATE_SUCCESS_MESSAGE);
        $JSON_UPDATE_ERROR = array(STATUS => STATUS_ERROR, TITLE => UPDATE_ERROR_TITLE, MESSAGE => UPDATE_ERROR_MESSAGE);
        
        include_once("../../../Scripts/Entity Classes/PHP/Colors.php");
        $object = new Colors($_POST["Id"], $_POST["Name"], $_POST["Hex"]);
        $result = Colors::update($object);
        if ($result) die(json_encode($JSON_UPDATE_SUCCESS));
        else die (json_encode($JSON_UPDATE_ERROR));
    }
    
    /*!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!! DO NOT EDIT CODE BELOW THIS POINT !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!*/
    //Editing the code below will compromise the reliability and security of your API.
    
    
    //Locals:
    const ENDPOINT_NAME = "API/Colors/Update";
                
    //Statuses:
    const STATUS_ERROR = "Error";
    const STATUS_OK = "OK";

    //Titles/Messages:
    const STATUS = "Status";
    const TITLE = "Title";
    const MESSAGE = "Message";
    const DATA = "Data";
    const INVALID_PARAMS_TITLE = "Invalid Parameters";
    const INVALID_PARAMS_MESSAGE = "Invalid parameters. Expected Parameters: Id (int), Name (String), Hex (String).";
    const TECHNICAL_ERROR_TITLE = "Technical Error";
    const TECHNICAL_ERROR_MESSAGE = "A technical error has occured. Please consult the system's administrator.";
    const AUTHORIZATION_ERROR_TITLE = "Authorization Error";
    const AUTHORIZATION_ERROR_MESSAGE = "You are not authorized to access this procedure. If you think you should be able to do so, please consult your system's administrator.";
    const UPDATE_SUCCESS_TITLE = "Item updated.";
    const UPDATE_SUCCESS_MESSAGE = "Item updated successfully.";
    const UPDATE_ERROR_TITLE = "Item update failed.";
    const UPDATE_ERROR_MESSAGE = "Failed to update item. Check that this item is not already in this state, exists and has the ID provided.";
    

    //JSON returns:
    $JSON_INVALID_PARAMS = array(STATUS => STATUS_ERROR, TITLE => INVALID_PARAMS_TITLE, MESSAGE => INVALID_PARAMS_MESSAGE);
    $JSON_TECHNICAL_ERROR = array(STATUS => STATUS_ERROR, TITLE => TECHNICAL_ERROR_TITLE, MESSAGE => TECHNICAL_ERROR_MESSAGE);
    $JSON_AUTHORIZATION_ERROR = array(STATUS => STATUS_ERROR, TITLE => AUTHORIZATION_ERROR_TITLE, MESSAGE => AUTHORIZATION_ERROR_MESSAGE);
    

	//-- PARAMETER CHECKS

	if (!isset($_POST["Id"]) || $_POST["Id"] == "") die(json_encode($JSON_INVALID_PARAMS));
	if (!isset($_POST["Name"]) || $_POST["Name"] == "") die(json_encode($JSON_INVALID_PARAMS));
	if (!isset($_POST["Hex"]) || $_POST["Hex"] == "") die(json_encode($JSON_INVALID_PARAMS));
	include_once("../../../Scripts/DBLogin.php");

	onRequest(); 

?>