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

        $fieldsArray = array();
        $fieldsSQL = "DESCRIBE " . $table;
        $result = $conn->query($fieldsSQL);

        if ($result->num_rows > 0) while($row = $result->fetch_assoc()) array_push($fieldsArray, $row);

        //Get Primary Keys:
        $primaryKeyField = null;
        foreach ($fieldsArray as $field) {
            if ($field["Key"] == "PRI") {
                $primaryKeyField = $field;
                break;
            }//end if PRI
        }//end foreach

        //Get Indexables:
        $indexableFields = array();
        foreach ($fieldsArray as $field) {
            if ($field["Key"] == "UNI") array_push($indexableFields, $field);
        }//end foreach

        $classOutput = FunctionGenerator::generate($table, $fieldsArray, $primaryKeyField, $indexableFields);

        writeToJavaFile("../Generated/Scripts/Entity Classes/Java/", ucfirst($table). ".java", $classOutput);

        header("Location: ../index.php?status=JavaClassesGenerated&dbName=" . $_GET["dbName"]);

    }//end foreach table

    class FunctionGenerator {

        public static function wrapClass($tableName, $classContent) {
            include_once("GeneratorUtils.php");
            $str = "";
            $str .= getGeneratorHeader($_GET["dbName"], $tableName . ".java", $tableName, "");
            //$str .= "include_once(\"DBLogin.php\");\r\n\r\n";
            $str .= self::generateImports();
            $str .= "class " . ucfirst($tableName) . " implements Serializable {\r\n";
            $str .= $classContent;
            $str .= "\r\n}";
            return $str;
        }//end wrapClass()

        public static function generateImports() {
            $str = "\r\n" .
                "import java.io.Serializable;\r\n" .
                "import java.sql.Time;\r\n" .
                "import java.text.ParseException;\r\n" .
                "import java.text.SimpleDateFormat;\r\n" .
                "import java.util.Date;\r\n"
            . "\r\n\r\n";
            return $str;
        }//end generateImports()

        public static function generateFinals() {
            $str = "\r\n\r\n\t//-------------------- Supporting Finals --------------------\r\n\r\n" .
                "\tfinal SimpleDateFormat DATE_FORMAT = new SimpleDateFormat(\"yyyy-MM-dd\");\r\n" .
                "\tfinal SimpleDateFormat TIME_FORMAT = new SimpleDateFormat(\"HH:mm:ss\");\r\n" .
                "\tfinal SimpleDateFormat TIMESTAMP_FORMAT = new SimpleDateFormat(\"yyyy-MM-dd HH:mm:ss\");\r\n"
                . "\r\n";
            return $str;
        }//end generateFinals()

        public static function getObjectConstructorBindings($allFields) {
            $objectBindings = "";
            foreach ($allFields as $field) {
                $objectBindings .= "this." . $field["Field"] . " = " . $field["Field"] . ";\n\t\t";
            }//end foreach field
            $objectBindings = substr($objectBindings, 0, strlen($objectBindings) - 3);
            return $objectBindings;
        }//end getObjectConstructorBindings()


        public static function getConstructorParameters($allFields) {
            $constructorParams = "";
            foreach ($allFields as $field) {
                $constructorParams .= "\r\n\t\t" . getFieldJavaType($field["Type"]) . " " . $field["Field"] . ", ";
            }//end foreach field
            $constructorParams = substr($constructorParams, 0, strlen($constructorParams) - 2);
            return $constructorParams . "\r\n\t\t";
        }//end getConstructorParameters()


        public static function getObjectConstructorParameterizer($allFields, $inputVariableName) {
            $objectConstructionParameterizer = "";
            foreach ($allFields as $field) {
                $objectConstructionParameterizer .= "\r\n\t\t\t\t" . $inputVariableName . "[\"" . $field["Field"] . "\"], ";
            }//end foreach field
            $objectConstructionParameterizer = substr($objectConstructionParameterizer, 0, strlen($objectConstructionParameterizer) - 2);
            return $objectConstructionParameterizer;
        }//end getObjectConstructorParameterizer()


        public static function generate($tableName, $allFields, $primaryKeyField, $indexFields) {
            $combinedGenerationString =
                self::generateFinals() .
                self::generatePrivateFields($allFields) .
                self::generateConstructors($tableName, $allFields) .
                self::generateGetters($allFields) .
                self::generateSetters($allFields) .
                self::generateToJSON($tableName, $allFields)
            ;

            return self::wrapClass($tableName, $combinedGenerationString);
        }//end generate()

        public static function generatePrivateFields($allFields) {
            $str = "\r\n\t//-------------------- Attributes --------------------\r\n";
            foreach ($allFields as $field) {
                $str .= "
    private " . getFieldJavaType($field["Type"]) . " " . $field["Field"] . ";";
            }//end foreach field
            return $str;
        }//end generatePrivateFields()


        public static function generateConstructors($tableName, $allFields) {
            $strConstructor = "\r\n\r\n\t//-------------------- Constructor --------------------\r\n
    public " . ucfirst($tableName) . "(" . self::getConstructorParameters($allFields) . ") {
        " . self::getObjectConstructorBindings($allFields) . "
    }\r\n";
            return $strConstructor;
        }//end generateConstructors()


        public static function generateGetters($allFields) {
            $str = "\r\n\t//-------------------- Getter Methods --------------------\r\n\r\n";
            foreach ($allFields as $field) {
                $str .= "\t/**
     * @return " . getFieldJavaType($field["Type"]) . "
     */
     public " . getFieldJavaType($field["Type"]). " get" . ucfirst($field["Field"]) . "() { return this." . $field["Field"] . "; }\r\n\r\n";
            }//end foreach field
            return $str;
        }//end generateGetters()


        public static function generateSetters($allFields) {
            $str = "\r\n\t//-------------------- Setter Methods --------------------\r\n\r\n";
            foreach ($allFields as $field) {
                if ($field["Key"] != "PRI")
                    $str .= "\t/**
     * @param value " . $field["Type"] . "
     */
     public void set" . ucfirst($field["Field"]) . "(" . getFieldJavaType($field["Type"]) . " value) { this." . $field["Field"] . " = value; }\r\n\r\n";
            }//end foreach field
            return $str;
        }//end generateGetters()


        public static function generateToJSON($tableName, $allFields) {

            $jsonString = "\"\\r\\n{\\r\\n";

            foreach ($allFields as $field) {
                $thisFieldString = "\\t\\\"" . $field["Field"] . "\\\": ";
                if (isQuotableType($field["Type"]))
                    $thisFieldString .= "\\\"\" + " . "this." . $field["Field"] . "+ \"\\\"";
                else
                    $thisFieldString .= "\" + this." . $field["Field"] . " + \"";
                $jsonString .= $thisFieldString . ",\\r\\n";
            }//end foreach field

            $jsonString = substr($jsonString, 0, strlen($jsonString) - 5);
            $jsonString .= "\\r\\n}\"";

            $str = "\r\n\t//-------------------- JSON Generation Methods --------------------\r\n
    /**
     * Specifies how objects of this class should be converted to JSON format.
     * @return String
     */
    public String toJSON() {
        return " . $jsonString . ";
    }
    
    /**
     * Converts an array of " . ucfirst($tableName) . " objects to a JSON Array.
     * @param " . ucfirst($tableName) . "[] " . ucfirst($tableName) . " array
     * @return String
     */
    public static String toJSONArray(" . ucfirst($tableName) . " [] " . $tableName . "_array) {
        StringBuilder strArray = new StringBuilder(\"[ \");
        for (final " . ucfirst($tableName) . " i : " . $tableName . "_array) {
            strArray.append(i.toJSON());
            strArray.append(\", \");
        }
        strArray = new StringBuilder(strArray.substring(0, strArray.length() - 3));
        strArray.append(\"} ] \");
        return strArray.toString();
    }\r\n";
            return $str;
        }//end generateToJSON()

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

    function getFieldJavaType($fieldType) {
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

?>