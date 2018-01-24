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

        writeToPHPFile("../Generated/Scripts/Entity Classes/PHP/", ucfirst($table). ".php", $classOutput);

        header("Location: ../index.php?status=PHPClassesGenerated&dbName=" . $_GET["dbName"]);

    }//end foreach table

    class FunctionGenerator {

        public static function wrapClass($tableName, $classContent) {
            include_once("GeneratorUtils.php");
            $str = "";
            $str .= getGeneratorHeader($_GET["dbName"], $tableName . ".php", $tableName, "");
            //$str .= "include_once(\"../../DBLogin.php\");\r\n\r\n";
            $str .= "class " . ucfirst($tableName) . " implements JsonSerializable {\r\n";
            $str .= $classContent;
            $str .= "\r\n}";
            return $str;
        }//end wrapClass()


        public static function getObjectConstructorBindings($allFields) {
            $objectBindings = "";
            foreach ($allFields as $field) {
                $objectBindings .= "\$this->" . $field["Field"] . " = \$" . $field["Field"] . ";\n\t\t";
            }//end foreach field
            $objectBindings = substr($objectBindings, 0, strlen($objectBindings) - 3);
            return $objectBindings;
        }//end getObjectConstructorBindings()


        public static function getConstructorParameters($allFields) {
            $constructorParams = "";
            foreach ($allFields as $field) {
                $constructorParams .= "\r\n\t\t\$" . $field["Field"] . ", ";
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
                self::generateFieldsArray($allFields) .
                self::generateHasUniqueFields($indexFields) .
                self::generateGetters($allFields, $primaryKeyField) .
                self::generateSetters($allFields) .
                self::generateCreateFunction($tableName, $allFields) .
                self::generateGetByID($tableName, $primaryKeyField, $allFields) .
                self::generateGetByIndex($tableName, $indexFields, $allFields) .
                self::generateGetMultiple($tableName, $allFields) .
                self::generateUpdate($tableName, $allFields, $primaryKeyField) .
                self::generateDelete($tableName, $primaryKeyField) .
                self::generateGetSize($tableName, $primaryKeyField) .
                self::generateIsEmpty($tableName, $primaryKeyField) .
                self::generateSearchByField($tableName, $allFields) .
                self::generateJsonSerialize($tableName, $allFields)
            ;

            return self::wrapClass($tableName, $combinedGenerationString);
        }//end generate()

        public static function generatePrivateFields($allFields) {
            $str = "\r\n\t//-------------------- Attributes --------------------\r\n";
            foreach ($allFields as $field) {
                $str .= "
    private \$" . $field["Field"] . ";";
            }//end foreach field
            return $str;
        }//end generatePrivateFields()


        public static function generateConstructors($allFields) {
            $strConstructor = "\r\n\r\n\t//-------------------- Constructor --------------------\r\n
    public function __construct(" . self::getConstructorParameters($allFields) . ") {
        " . self::getObjectConstructorBindings($allFields) . "
    }\r\n";
            return $strConstructor;
        }//end generateConstructors()

        public static function generateHasUniqueFields($indexFields) {
            $str = "";
            if (sizeof($indexFields) > 0) {
                $str = "\r\n\r\n\tpublic static \$hasUniqueFields = true;\r\n";
            }
            else {
                $str = "\r\n\r\n\tpublic static \$hasUniqueFields = false;\r\n";
            }
            return $str;
        }//end generateHasUniqueFields()


        public static function generateFieldsArray($allFields) {
            $str = "\r\n\t//-------------------- Reflectors --------------------\r\n";
            $str .= "\r\n\tpublic static \$allFields = array(";
            foreach ($allFields as $field) $str .= "\r\n\t\t\"".$field["Field"]."\", ";
            $str = substr($str, 0, strlen($str) - 2);
            $str .= "\r\n\t);";
            return $str;
        }//end generateFieldsArray()


        public static function generateGetters($allFields, $primaryKeyField) {
            $str = "\r\n\t//-------------------- Getter Methods --------------------\r\n\r\n";
            foreach ($allFields as $field) {
                $str .= "\t/**
     * @return " . $field["Type"] . "
     */
     public function get" . ucfirst($field["Field"]) . "() { return \$this->" . $field["Field"] . "; }\r\n\r\n";
            }//end foreach field

            $str .= "
    /**
     * @return " . $primaryKeyField["Type"] . "
     */
     public function getID() { return \$this->" . $primaryKeyField["Field"] . "; }\r\n\r\n";

            return $str;
        }//end generateGetters()


        public static function generateSetters($allFields) {
            $str = "\r\n\t//-------------------- Setter Methods --------------------\r\n\r\n";
            foreach ($allFields as $field) {
                if ($field["Key"] != "PRI")
                    $str .= "\t/**
     * @param \$value " . $field["Type"] . "
     */
     public function set" . ucfirst($field["Field"]) . "(\$value) { \$this->" . $field["Field"] . " = \$value; }\r\n\r\n";
            }//end foreach field
            return $str;
        }//end generateGetters()


        public static function generateCreateFunction($tableName, $allFields) {
            $fieldParameters = null;
            $parameters = "\$" . $tableName . "_object";
            $fieldValues = null;
            foreach ($allFields as $field) {
                if (($field["Key"] != "PRI") || ($field["Key"] == "PRI" && isQuotableType($field["Type"]))) {
                    $fieldParameters .= $field["Field"] . ", ";
                    if (!isQuotableType($field["Type"])) {
                        if ($field["Type"] == "tinyint(1)")
                            $fieldValues .= "\" . booleanFix($" . $tableName . "_object->" . $field["Field"] . ") . \", ";
                        else $fieldValues .= "$" . $tableName . "_object->" . $field["Field"] . ", ";
                    }//end if quotable
                    else $fieldValues .= quote("$" . $tableName . "_object->" . $field["Field"]) . ", ";
                }//end if not primary key
            }//end foreach field
            $fieldParameters = substr($fieldParameters, 0, strlen($fieldParameters) - 2);
            $fieldValues = substr($fieldValues, 0, strlen($fieldValues) - 2);

            $str = "\r\n\t//-------------------- Static (Database) Methods --------------------\r\n
            
            
    /**
     * Creates a database entry with the given object's data.
     * @param \$t1_object " . ucfirst($tableName) . "
     * @return bool
     */
    public static function create(" . $parameters . ") {
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
     /**
     * Retrieves a database entry matching the ID value provided.
     * @param \$id " . $primaryKeyField["Type"] . "
     * @return bool|" . ucfirst($tableName) . "
     */
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


        public static function generateGetByIndex($tableName, $indexFields, $allFields) {
            $str = "";
            foreach ($indexFields as $indexField) {

                if (isQuotableType($indexField["Type"]))
                    $query = "\$sql = \"SELECT * FROM " . $tableName . " WHERE " . $indexField["Field"] . " = '\" . \$indexValue . \"'\";";
                else
                    $query = "\$sql = \"SELECT * FROM " . $tableName . " WHERE " . $indexField["Field"] . " = \" . \$indexValue;";

                $str .= "\r\n
    /**
     * Returns a database entry matching the unique field value provided.
     * @param \$indexValue " . $indexField["Type"] . "
     * @return bool|" . ucfirst($tableName) . "
     */
    public static function getBy" . ucfirst($indexField["Field"]) . "(\$indexValue) {
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
            }//end foreach indexField
            return $str;
        }//end generateGetByIndex()


        public static function generateGetMultiple($tableName, $allFields) {
            $str = "\r\n
    /**
     * Retrieves all entries or up to a specified limit from the database. Use 0 or negative values as limit to retrieve all entries.
     * @param \$limit int
     * @return array|bool
     */
    public static function getMultiple(\$limit) {
        \$conn = dbLogin();
        \$sql = \"SELECT * FROM " . $tableName . "\";
        if (\$limit > 0) \$sql .= \" LIMIT \" . \$limit;
        \$result = \$conn->query(\$sql);
        \$itemsArray = array();
        if (\$result->num_rows > 0) {
            if (\$result->num_rows > 0) {
                while(\$row = \$result->fetch_assoc()) {
                    \$object = new " . ucfirst($tableName) . "(" . self::getObjectConstructorParameterizer($allFields, "row") . ");
                    array_push(\$itemsArray, \$object);
                }
            }
            return \$itemsArray;
        }
        return false;
    }\r\n";
            return $str;
        }//end generatorGetMultiple()


        public static function generateUpdate($tableName, $allFields, $primaryKeyField) {
            $parameters = "\$" . $tableName . "_object";
            $fieldValues = null;
            foreach ($allFields as $field) {
                if ($field["Key"] != "PRI") {
                    if (!isQuotableType($field["Type"])) {
                        if ($field["Type"] == "tinyint(1)")
                            $fieldValues .= $field["Field"] . " = \" . booleanFix($" . $tableName . "_object->get" . ucfirst($field["Field"]) . "()) . \", ";
                        else
                            $fieldValues .= $field["Field"] . " = \" . $" . $tableName . "_object->get" . ucfirst($field["Field"]) . "() . \", ";
                    }//end if type is quotable
                    else $fieldValues .= $field["Field"] . " = " . quote("$" . $tableName . "_object->get" . ucfirst($field["Field"]) . "()") . ", ";
                }//end if not primary key
            }//end foreach field
            $fieldValues = substr($fieldValues, 0, strlen($fieldValues) - 2);

            if (!isQuotableType($primaryKeyField["Type"]))
                $sql = "\$sql = \"UPDATE " . $tableName . " SET " . $fieldValues . " WHERE " . $primaryKeyField["Field"] . " = \" . \$" . $tableName . "_object->get" . ucfirst($primaryKeyField["Field"]) . "();";
            else
                $sql = "\$sql = \"UPDATE " . $tableName . " SET " . $fieldValues . " WHERE " . $primaryKeyField["Field"] . " = \\\"\" . " . "\$" . $tableName . "_object->get" . ucfirst($primaryKeyField["Field"]) . "()" . " . \"\\\"\";";

            $str = "\r\n
    /**
     * Updates a database entry with the given object's data.
     * @param \$t1_object " . ucfirst($tableName) . "
     * @return bool
     */
    public static function update(" . $parameters . ") {
        \$conn = dbLogin();
        " . $sql . "
        \$conn->query(\$sql);
        if (\$conn->affected_rows > 0) return true;
        else return false;
    }\r\n";
            return $str;
        }//end generateUpdateByID()


        public static function generateDelete($tableName, $primaryKeyField) {
            $parameters = "\$" . $tableName . "_object";

            $sql = "\$sql = \"DELETE FROM " . $tableName . " WHERE " . $primaryKeyField["Field"] . " = \\\"\" . " . "\$id . \"\\\"\";";

            $str = "\r\n
    /**
     * Deletes an entry from the database given the object's data.
     * @param \$id " . ucfirst($primaryKeyField["Type"]) . "
     * @return bool
     */
    public static function delete(\$id) {
        \$conn = dbLogin();
        " . $sql . "
        \$result = \$conn->query(\$sql);
        if (\$conn->affected_rows > 0) return true;
        else return false;
    }\r\n";
            return $str;
        }//end generateDeleteByID()


        public static function generateGetSize($tableName, $primaryKeyField) {
            $sql = "\$sql = \"SHOW TABLE STATUS WHERE Name = \\\"".$tableName."\\\"\";";
            $str = "\r\n
    /**
     * Returns the number of entries in the database.
     * @return int
     */
    public static function getSize() {
        \$conn = dbLogin();
        " . $sql . "
        \$result = \$conn->query(\$sql);
        if (\$result) return \$result->fetch_object()->Rows;
        else return false;
    }\r\n";
            return $str;
        }//end generateGetSize()


        public static function generateIsEmpty($tableName, $primaryKeyField) {
            $sql = "\$sql = \"SELECT COUNT(" . $primaryKeyField["Field"] . ") FROM " . $tableName . "\";";
            $str = "\r\n
    /**
     * Returns true if the database is empty or false otherwise.
     * @return bool
     */
    public static function isEmpty() {
        \$size = self::getSize();
        return (\$size == 0);
    }\r\n";
            return $str;
        }//end generateIsEmpty()

        public static function generateSearchByField($tableName, $allFields) {
            $str = "\r\n
    /**
     * Searches the database for matches in the given field-value pairs in the given associative array.
     * The array should be in the format FieldName -> ValueToSearch where both values are strings.
     * @return array|bool
     */
    public static function searchByFields(\$fieldsAndValuesAssocArray) {
        \$conn = dbLogin();
        
        \$combinedWhereClause = \"\";
        
        foreach (\$fieldsAndValuesAssocArray as \$field => \$value) {
            \$combinedWhereClause .= \$field . \" = \\\"\" . \$value . \"\\\" AND \";
        }
        \$combinedWhereClause = substr(\$combinedWhereClause, 0, strlen(\$combinedWhereClause) - 4);
        
        \$sql = \"SELECT * FROM " . $tableName . " WHERE \" . \$combinedWhereClause;
        \$result = \$conn->query(\$sql);
        if (!\$result) return false;
        \$itemsArray = array();
        while (\$row = \$result->fetch_assoc()) {
            \$object = new " . ucfirst($tableName) . "(" . self::getObjectConstructorParameterizer($allFields, "row") . ");
            array_push(\$itemsArray, \$object);
        }
        return \$itemsArray;
    }\r\n";
            return $str;
        }//end generateSearchByField()


        public static function generateJsonSerialize($tableName, $allFields) {

            $jsonString = "\"{";

            foreach ($allFields as $field) {
                $thisFieldString = "\\\"" . $field["Field"] . "\\\": ";
                if (isQuotableType($field["Type"]))
                    $thisFieldString .= "\\\"\" . " . "\$this->" . $field["Field"] . ". \"\\\"";
                else
                    $thisFieldString .= "\$this->" . $field["Field"];
                $jsonString .= $thisFieldString . ", ";
            }//end foreach field

            $jsonString = substr($jsonString, 0, strlen($jsonString) - 2);
            $jsonString .= " }\"";

            $str = "\r\n\t//-------------------- JSON Generation Methods --------------------\r\n
    /**
     * Specifies how objects of this class should be converted to JSON format.
     * @return string
     */
    public function jsonSerialize() {
        \$jsonStr = " . $jsonString . ";
        return \$jsonStr;
    }
    
    /**
     * Converts an array of " . ucfirst($tableName) . " objects to a JSON Array.
     * @param \$" . ucfirst($tableName) . "_array " . ucfirst($tableName) . " Array
     * @return bool|string
     */
    public static function toJSONArray(\$" . ucfirst($tableName) . "_array) {
        \$strArray = \"[ \";
        foreach (\$" . ucfirst($tableName) . "_array as \$i) {
            \$strArray .= \$i->jsonSerialize() . \", \";
        }
        \$strArray = substr(\$strArray, 0, strlen(\$strArray) - 3);
        \$strArray .= \"} ] \";
        return \$strArray;
    }\r\n";
            return $str;
        }//end generateJsonSerialize()

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