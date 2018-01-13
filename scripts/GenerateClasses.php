<?php

    if (!isset($_GET["dbName"]) || $_GET["dbName"] == "") {
        header("Location: ../createDB.php?status=EmptyDBName"); exit();
    }
    else if (!isset($_GET["dbHostIP"]) || $_GET["dbHostIP"] == "") {
        header("Location: ../createDB.php?status=EmptyDBHostIP"); exit();
    }
    else if (!isset($_GET["dbUser"]) || $_GET["dbUser"] == "") {
        header("Location: ../createDB.php?status=EmptyDBUser"); exit();
    }
    else if (!isset($_GET["dbPassword"]) || $_GET["dbPassword"] == "") {
        header("Location: ../createDB.php?status=EmptyDBPassword"); exit();
    }

    $conn = new mysqli($_GET["dbHostIP"], $_GET["dbUser"], $_GET["dbPassword"], $_GET["dbName"]);
    if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

    $tablesArray = array();
    $tablesSQL = "SHOW TABLES";
    $result = $conn->query($tablesSQL);

    if ($result->num_rows > 0) while($row = $result->fetch_array()) array_push($tablesArray, $row[0]);

    foreach ($tablesArray as $table) {

        echo $table . "<br>";

        $fieldsArray = array();
        $fieldsSQL = "DESCRIBE " . $table;
        $result = $conn->query($fieldsSQL);

        if ($result->num_rows > 0) while($row = $result->fetch_assoc()) array_push($fieldsArray, $row);

        //Get Primary Keys:
        $primaryKeyFields = array();
        foreach ($fieldsArray as $field) {
            if ($field["Key"] == "PRI") array_push($primaryKeyFields, $field);
        }//end foreach

        //Get Indexables:
        $indexableFields = array();
        foreach ($fieldsArray as $field) {
            if ($field["Key"] == "MUL") array_push($indexableFields, $field);
        }//end foreach

        $classOutput = FunctionGenerator::generate($table, $fieldsArray);

        writeToFile("../Generated/Scripts/", $table . ".php", $classOutput);

    }//end foreach table

    class FunctionGenerator {

        public static function wrapClass($tableName, $classContent) {
            include_once("GeneratorUtils.php");
            $str = "";
            $str .= getGeneratorHeader($_GET["dbName"], $tableName . ".php", $tableName, "");
            $str .= "include_once(\"DBLogin.php\");\r\n\r\n";
            $str .= "class " . ucfirst($tableName) . " {\r\n";
            $str .= $classContent;
            $str .= "\r\n}";
            return $str;
        }

        public static function generate($tableName, $allFields) {
            return self::wrapClass($tableName, self::generateCreateFunction($tableName, $allFields));
            //TODO MORE>>
        }

        public static function generateCreateFunction($tableName, $allFields) {
            $fieldParameters = null;
            $fieldParametersWithVarSign = null;
            $fieldValues = null;
            foreach ($allFields as $field) {
                if ($field["Key"] != "PRI") {
                    $fieldParameters .= $field["Field"] . ", ";
                    $fieldParametersWithVarSign .= "$" . $field["Field"] . ", ";
                    if (!isQuotableType($field["Type"])) $fieldValues .= "$" . $field["Field"] . ", ";
                    else $fieldValues .= quote("$" . $field["Field"]) . ", ";
                }//end if not primary key
            }//end foreach field
            $fieldParameters = substr($fieldParameters, 0, strlen($fieldParameters) - 2);
            $fieldParametersWithVarSign = substr($fieldParametersWithVarSign, 0, strlen($fieldParametersWithVarSign) - 2);
            $fieldValues = substr($fieldValues, 0, strlen($fieldValues) - 2);

            $str = "\r\n
    public static function create(" . $fieldParametersWithVarSign . ") {
        \$conn = dbLogin();
        \$sql = \"INSERT INTO " . $tableName . " (" . $fieldParameters . ") VALUES (" . $fieldValues . ")\";
        if (\$conn->query(\$sql) === TRUE) return true;
        else return false;
    }\r\n";
            return $str;
        }//end generateCreateFunction()

        public static function generate

    }//end class FunctionGenerator

    function quote($text) {
        return "\\\"\" . " . $text . " . \"\\\"";
    }//end quote()

    function isQuotableType($typeName) {
        if (strpos($typeName, 'text') !== false ||
            strpos($typeName, 'char') !== false ||
            strpos($typeName, 'date') !== false ||
            strpos($typeName, 'time') !== false
        ) return true;
        return false;
    }//end isQuotableType()

?>