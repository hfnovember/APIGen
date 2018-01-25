<?php


/**********************************************************************************/
/*** THIS FILE HAS BEEN AUTOMATICALLY GENERATED BY THE PANICKAPPS API GENERATOR ***/

/*                It is HIGHLY suggested that you do not edit this file.          */

//  DATABASE:     TestDB
//  FILE:         API/getMultiple/index.php
//  TABLE:        students
//  DATETIME:     2018-01-25 02:02:24am
//  DESCRIPTION:  N/A

/**********************************************************************************/
			


                
/*
        ~~~~~~ API Endpoint Instructions ~~~~~~
        
        This endpoint is public and requires no authorization.
        
        Sample call for API/Students/GetMultiple:

            API/Students/GetMultiple?Limit...

        /----------------------------------------------------------------/

        User Types/Levels who can access this endpoint:
         Administrator (1), Manager(2), Public(4), User(3)

        Call Parameters List:
        
		Limit (int)

        /----------------------------------------------------------------/


        This endpoint responds with JSON data in the following ways.

        Response Format:

        1) Response OK

            --> "Status": "OK"
            --> "Title": "Item retrieved."
            --> "Message": "Item retrieved successfully."

        2) Response ERROR
        
            --> "Status": "Error"
            --> "Title": "Item retrieval failed."
            --> "Message": "Failed to retrieve items."

                (Invalid parameters)

            --> "Status": "Error"
            --> "Title": "Invalid Parameters"
            --> "Message": "Invalid parameters. Expected Parameters: Limit (int)."

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
    function onRequest($limit) {
        $JSON_GET_ERROR = array(STATUS => STATUS_ERROR, TITLE => GET_ERROR_TITLE, MESSAGE => GET_ERROR_MESSAGE);
        $JSON_NO_ITEMS = array(STATUS => STATUS_ERROR, TITLE => "No items found.", MESSAGE => "Your call has return 0 results.");
        
        include_once("../../../Scripts/Entity Classes/PHP/Students.php");     
           
        $objects = Students::getMultiple($limit);
        if (sizeof($objects) <= 0) die(json_encode($JSON_NO_ITEMS));
        $returnArray = array(STATUS => STATUS_OK, TITLE => GET_SUCCESS_TITLE, MESSAGE => GET_SUCCESS_MESSAGE);
        $statusJson = json_encode($returnArray);
        $statusJson = substr($statusJson, 0, strlen($statusJson) - 1);
        $objectData = ", \"Data\": " . Students::toJSONArray($objects) . "}";
        $combinedReturn = $statusJson . $objectData;
        die ($combinedReturn);
    }
    
    /*!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!! DO NOT EDIT CODE BELOW THIS POINT !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!*/
    //Editing the code below will compromise the reliability and security of your API.
    
    
    //Locals:
    const ENDPOINT_NAME = "API/Students/GetMultiple";
                
    //Statuses:
    const STATUS_ERROR = "Error";
    const STATUS_OK = "OK";

    //Titles/Messages:
    const STATUS = "Status";
    const TITLE = "Title";
    const MESSAGE = "Message";
    const DATA = "Data";
    const INVALID_PARAMS_TITLE = "Invalid Parameters";
    const INVALID_PARAMS_MESSAGE = "Invalid parameters. Expected Parameters: Limit (int).";
    const TECHNICAL_ERROR_TITLE = "Technical Error";
    const TECHNICAL_ERROR_MESSAGE = "A technical error has occured. Please consult the system's administrator.";
    const AUTHORIZATION_ERROR_TITLE = "Authorization Error";
    const AUTHORIZATION_ERROR_MESSAGE = "You are not authorized to access this procedure. If you think you should be able to do so, please consult your system's administrator.";
    const GET_SUCCESS_TITLE = "Items retrieved.";
    const GET_SUCCESS_MESSAGE = "Items retrieved successfully.";
    const GET_ERROR_TITLE = "Item retrieval failed.";
    const GET_ERROR_MESSAGE = "Failed to retrieve items.";
    

    //JSON returns:
    $JSON_INVALID_PARAMS = array(STATUS => STATUS_ERROR, TITLE => INVALID_PARAMS_TITLE, MESSAGE => INVALID_PARAMS_MESSAGE);
    $JSON_TECHNICAL_ERROR = array(STATUS => STATUS_ERROR, TITLE => TECHNICAL_ERROR_TITLE, MESSAGE => TECHNICAL_ERROR_MESSAGE);
    $JSON_AUTHORIZATION_ERROR = array(STATUS => STATUS_ERROR, TITLE => AUTHORIZATION_ERROR_TITLE, MESSAGE => AUTHORIZATION_ERROR_MESSAGE);
    

	//-- PARAMETER CHECKS

	$limit = 0;
	if (!isset($_POST["Limit"]) || $_POST["Limit"] == "") $limit = 0; else $limit = $_POST["Limit"];
	include_once("../../../Scripts/DBLogin.php");

	onRequest($limit); 

?>