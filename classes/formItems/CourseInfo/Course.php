<?php
    abstract class Course {
        //This parent class helps handle course information and sets up the standard set of functions needed for Course Information Form questions. It also holds the Course ID used by all child classes.
        public static int $courseID;
        abstract public function __construct(); //This is the function that will fire whenever a form item is loaded.
        abstract protected function buildHTML(); //This is the function that will build the form HTML.

        protected string $sqlTable = 'CourseInfo'; //This is the primary table where most of the inputs for items will be stored.
        //Classes to be made
        private int $students = 0;
        private string $delivery = '';
        private array $levels = [];
        private array $assessments = [];
        private array $programs = [];
        private array $activities = [];
        private array $libGuides = [];
        private array $librarians = [];

        public function display() {
            return $this->htmlOutput;
        }

        public function get($prop){
            AllFunctions::errorCheck(!property_exists($this, $prop), "Property {$prop} does not exist in class Course.");
            return $this->$prop;
        }

        public function updateValue($id, $value) {
            //Validate $value and $id
            $db = new MySQL();
            $query = [
                "UPDATE CourseInfo A",
                "SET A.{$this->mySQLField} = ?",
                "WHERE A.CourseID = ?",
            ];
            $updateQuery = $db->query($query, [$value, $id]);
            //Double-check the response here somehow.
        }

        protected function getValue($id) {
            $sqlField = $this->mySQLField;
            $table = $this->sqlTable;
            $db = new MySQL();
            $query = [
                "SELECT A.{$sqlField}",
                "FROM {$table} A",
                "WHERE A.CourseID = ?",
            ];
            $courseData = $db->query($query, [$id]);
            $output = ($courseData[0][$sqlField]) ?? "";
            return $output;
        }

        protected function set($prop, $value = NULL){
            $className = get_class($this);
            AllFunctions::errorCheck(!property_exists($this, $prop), "Property '{$prop}' does not exist in class {$className}.");
            $differentTypes = (!AllFunctions::typeCheck($this->$prop, $value));
            AllFunctions::errorCheck($differentTypes, "Incorrect Type provided for Property '{$prop}'.");
            $this->$prop = $value;
        }
    }
?>