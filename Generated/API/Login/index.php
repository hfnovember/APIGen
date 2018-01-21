<?php


        
        
            function generateRandomString($length = 5) {
                $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
                $charactersLength = strlen($characters);
                $randomString = '';
                for ($i = 0; $i < $length; $i++) $randomString .= $characters[rand(0, $charactersLength - 1)];
                return $randomString;
            }
            
            function getRealIPAddress() {
                if (!empty($_SERVER['HTTP_CLIENT_IP'])) $ip=$_SERVER['HTTP_CLIENT_IP'];
                elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) $ip=$_SERVER['HTTP_X_FORWARDED_FOR'];
                else $ip=$_SERVER['REMOTE_ADDR'];
                return $ip;
            }//end getRealIPAddress()
                        
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
            const LOGIN_SUCCESS_TITLE = "Login Success.";
            const LOGIN_SUCCESS_MESSAGE = "You have successfully logged in.";
            const LOGIN_ERROR_TITLE = "Login Failed.";
            const LOGIN_ERROR_MESSAGE = "Failed to log in.";
        
            //JSON returns:
            $JSON_INVALID_PARAMS = array(STATUS => STATUS_ERROR, TITLE => INVALID_PARAMS_TITLE, MESSAGE => INVALID_PARAMS_MESSAGE);
            $JSON_TECHNICAL_ERROR = array(STATUS => STATUS_ERROR, TITLE => TECHNICAL_ERROR_TITLE, MESSAGE => TECHNICAL_ERROR_MESSAGE);
            $JSON_AUTHORIZATION_ERROR = array(STATUS => STATUS_ERROR, TITLE => AUTHORIZATION_ERROR_TITLE, MESSAGE => AUTHORIZATION_ERROR_MESSAGE);
            $JSON_LOGIN_SUCCESS = array(STATUS => STATUS_OK, TITLE => LOGIN_SUCCESS_TITLE, MESSAGE => LOGIN_SUCCESS_MESSAGE);
            $JSON_LOGIN_ERROR = array(STATUS => STATUS_OK, TITLE => LOGIN_ERROR_TITLE, MESSAGE => LOGIN_ERROR_MESSAGE);
            
            if (!isset($_POST["Username"]) || $_POST["Username"] == "") die(json_encode($JSON_INVALID_PARAMS));
            if (!isset($_POST["Password"]) || $_POST["Password"] == "") die(json_encode($JSON_INVALID_PARAMS));
            
            include_once("../../Scripts/DBLogin.php");
            $conn = dbLogin();
            $username = $_POST["Username"];
            $password = $_POST["Password"];
            
            $sql = "SELECT * FROM Users WHERE Username = \"".$username."\" AND Password = \"".$password."\"";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                $currentUser = $result->fetch_object();
                $newSessionID = md5(time() . $currentUser->Username . generateRandomString(5)); //MD5 Hash will be: Timestamp + Username + generateRandomString(5)
                $sql = "INSERT INTO Sessions (SessionID, UserID, InitiatedOn, ClientIPAddress) VALUES (\"".$newSessionID."\", ".$currentUser->UserID.", ".time().", \"".getRealIPAddress()."\")";
                $result = $conn->query($sql);
                if ($result === TRUE) {
                    $successResponse = array(STATUS => STATUS_ERROR, TITLE => LOGIN_SUCCESS_TITLE, MESSAGE => LOGIN_SUCCESS_MESSAGE, SESSIONID => ".$newSessionID.");
                    echo json_encode($successResponse);
                }
                else echo json_encode($JSON_TECHNICAL_ERROR); exit();
            }
            else echo json_encode($JSON_LOGIN_ERROR); exit();
            
        

?>