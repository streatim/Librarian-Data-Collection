<?php
    class ActiveUser {
        //This class takes in a name and identifier 
        private string $name;
        private string $identifier;

        function __construct($idValue = NULL, $nameValue = NULL) {
            //Set the values.
            $this->setIdentifier($idValue);
            $this->setName($nameValue);
        }

        public function get($prop) {
            AllFunctions::errorCheck(!property_exists($this, $prop), "Property '{$prop}' does not exist in class Active User.");
            return $this->$prop;
        }

        private function setIdentifier($identifierInput = NULL) {
            //Validate that an identifier has been provided.
            AllFunctions::errorCheck($identifierInput == NULL, "No Identifier Provided.");
            $this->identifier = $identifierInput;
        }

        private function setName($nameInput = NULL) {
            //If there isn't a name value, just use the identifier.
            $nameInput = ($nameInput !== NULL) ? $nameInput : $this->identifier;
            $this->name = $nameInput;
        }
    }
?>