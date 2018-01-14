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
            if ($field["Key"] == "MUL") array_push($indexableFields, $field);
        }//end foreach

        $classOutput = FunctionGenerator::generate($table, $fieldsArray, $primaryKeyField, $indexableFields);

        writeToFile("../Generated/Scripts/", ucfirst($table). ".php", $classOutput);

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
        }//end wrapClass()


        public static function getObjectConstructorBindings($allFields) {
            $objectBindings = "";
            foreach ($allFields as $field) {
                if ($field["Key"] != "PRI") {
                    $objectBindings .= "\$this->" . $field["Field"] . " = \$" . $field["Field"] . ";\n\t\t";
                }//end if not primary key
            }//end foreach field
            $objectBindings = substr($objectBindings, 0, strlen($objectBindings) - 3);
            return $objectBindings;
        }//end getObjectConstructorBindings()


        public static function getConstructorParameters($allFields) {
            $constructorParams = "";
            foreach ($allFields as $field) {
                if ($field["Key"] != "PRI") {
                    $constructorParams .= "\r\n\t\t\$" . $field["Field"] . ", ";
                }//end if not primary key
            }//end foreach field
            $constructorParams = substr($constructorParams, 0, strlen($constructorParams) - 2);
            return $constructorParams . "\r\n\t\t";
        }//end getConstructorParameters()


        public static function getObjectConstructorParameterizer($allFields, $inputVariableName) {
            $objectConstructionParameterizer = "";
            foreach ($allFields as $field) {
                $objectConstructionParameterizer .= "\r\n\t\t\t\t\$" . $inputVariableName . "[\"" . $field["Field"] . "\"], ";
            }//end foreach field
            $objectConstructionParameterizer = substr($objectConstructionParameterizer, 0, strlen($objectConstructionParameterizer) - 2);
            return $objectConstructionParameterizer;
        }//end getObjectConstructorParameterizer()


        public static function generate($tableName, $allFields, $primaryKeyField, $indexFields) {
            $combinedGenerationString =
                self::generatePrivateFields($allFields) .
                self::generateConstructors($allFields) .
                self::generateGetters($allFields) .
                self::generateSetters($allFields) .
                self::generateCreateFunction($tableName, $allFields) .
                self::generateGetByID($tableName, $primaryKeyField, $allFields) .
                self::generateGetByIndex($tableName, $indexFields) .
                self::generateGetMultiple($tableName)
            ;

            return self::wrapClass($tableName, $combinedGenerationString);
        }//end generate()

        public static function generatePrivateFields($allFields) {
            $str = "\r\n\t//--- Attributes\r\n";
            foreach ($allFields as $field) {
                $str .= "
    private \$" . $field["Field"] . ";";
            }//end foreach field
            return $str;
        }//end generatePrivateFields()


        public static function generateConstructors($allFields) {
            $strConstructor = "\r\n\r\n\t//--- Constructor\r\n
    public function __construct(" . self::getConstructorParameters($allFields) . ") {
        " . self::getObjectConstructorBindings($allFields) . "
    }\r\n";
            return $strConstructor;
        }//end generateConstructors()


        public static function generateGetters($allFields) {
            $str = "\r\n\t//--- Getter Methods\r\n\r\n";
            foreach ($allFields as $field) {
                $str .= "\tpublic function get" . ucfirst($field["Field"]) . "() { return \$this->" . $field["Field"] . "; }\r\n";
            }//end foreach field
            return $str;
        }//end generateGetters()


        public static function generateSetters($allFields) {
            $str = "\r\n\t//--- Setter Methods\r\n\r\n";
            foreach ($allFields as $field) {
                $str .= "\tpublic function set" . ucfirst($field["Field"]) . "(\$value) { \$this->" . $field["Field"] . " = \$value; }\r\n";
            }//end foreach field
            return $str;
        }//end generateGetters()


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

            $str = "\r\n\t//--- Static (Database) Methods\r\n
    public static function create(" . $fieldParametersWithVarSign . ") {
        \$conn = dbLogin();
        \$sql = \"INSERT INTO " . $tableName . " (" . $fieldParameters . ") VALUES (" . $fieldValues . ")\";
        if (\$conn->query(\$sql) === TRUE) return true;
        else return false;
    }\r\n";
            return $str;
        }//end generateCreateFunction()


        //TODO: generateCreateMultipleFunction...


        public static function generateGetByID($tableName, $primaryKeyField, $allFields) {

            if (isQuotableType($primaryKeyField["Type"]))
                $query = "\$sql = \"SELECT * FROM " . $tableName . " WHERE " . $primaryKeyField["Field"] . " = '\" . \$id . \"'\";";
            else
                $query = "\$sql = \"SELECT * FROM " . $tableName . " WHERE " . $primaryKeyField["Field"] . " = \" . \$id;";

            $str = "\r\n
    public static function getByID(\$id) {
        \$conn = dbLogin();
        " . $query . "
        \$result = \$conn->query(\$sql);
        \$sqlRowItemAsAssocArray = null;
        if (\$result->num_rows > 0) {
            \$sqlRowItemAsAssocArray = \$result->fetch_assoc();
            \$object = new " . ucfirst($tableName) . "(" . self::getObjectConstructorParameterizer($allFields, "sqlRowItemAsAssocArray") . ");
            return \$object;
        }
        else return false;
    }\r\n";
            return $str;
        }//end generateGetByID()


        public static function generateGetByIndex($tableName, $indexFields) {
            $str = "";
            foreach ($indexFields as $indexField) {
                $str .= "\r\n
    public static function getBy" . ucfirst($indexField["Field"]) . "(\$indexValue) {
        \$conn = dbLogin();
        \$sql = \"SELECT * FROM " . $tableName . " WHERE " . $indexField["Field"] . " = \" . \$indexValue;
        \$result = \$conn->query(\$sql);
        if (\$result->num_rows > 0) return \$result->fetch_object();
        else return false;
    }\r\n";
            }//end foreach indexField
            return $str;
        }//end generateGetByIndex()


        public static function generateGetMultiple($tableName) {
            $str = "\r\n
    public static function getMultiple(\$limit) {
        \$conn = dbLogin();
        \$sql = \"SELECT * FROM " . $tableName . "\";
        if (\$limit > 0) \$sql .= \" LIMIT \" . \$limit;
        \$result = \$conn->query(\$sql);
        \$itemsArray = array();
        if (\$result->num_rows > 0) {
            if (\$result->num_rows > 0) while(\$row = \$result->fetch_object()) array_push(\$itemsArray, \$row);
            return \$itemsArray;
        }
        return false;
    }\r\n";
            return $str;
        }//end generatorGetMultiple()


        public static function generateUpdateByID($tableName, $allFields) {
            //TODO
        }


        public static function generateUpdateByIndex($tableName, $allFields, $indexFields) {
            //TODO
        }


        public static function generateDeleteByID($tableName) {
            //TODO
        }


        public static function generateDeleteByIndex($tableName) {
            //TODO
        }


        public static function generateGetLastRow($tableName) {
            //TODO
        }


        public static function generateGetLastRowID($tableName) {
            //TODO
        }


        public static function generateGetSize($tableName) {
            //TODO
        }

        public static function generateIsEmpty($tableName) {
            //TODO
        }


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