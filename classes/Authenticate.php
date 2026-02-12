<?php
    class Authenticate {
        //This class takes in a name and identifier 
        private array $returnArray = [];
        private ?string $name = NULL;
        private ?string $identifier = NULL;

        function __construct() {
            $this->authenticate();
            $this->setReturnArray();
        }

        public function returnOutput(){
            $arraySize = AllFunctions::countArray($this->returnArray);
            AllFunctions::errorCheck(($arraySize == 0), "Return Array Not Set");
            return $this->returnArray;
        }

        private function authenticate(){
            /* 
                However you are deciding to authenticate, you'll want to write the code here.
                An identifier is required, at the very least, as it is used to track which courses go with which librarian (and build out lists of librarians in the forms)

                At UM-Dearborn we stick these files behind a directory where the user logs in and their identifier is set to the $_SERVER['REMOTE_USER'] value. An example of what that would look like (without any validation, which you will likely want to do, is below.
                $this->setIdentifier($_SERVER['REMOTE_USER']);

                Then at Dearborn we would use the identifier to query our staff table and get first and last names, concatenating them together into a single name value. If a name value isn't provided the identifier will be used instead. It's worth noting that the name is only used to indicate who is logged in at the top of the screen.
                $this->setName($nameVariable);
            */
            //Testing values while I'm coding.
            $this->setIdentifier('streatim');
            $this->setName('Tim S');
        }

        private function setIdentifier($identifierInput = NULL){
            //Put any validation here that you want.
            $this->identifier = $identifierInput;
        }

        private function setName($nameInput = NULL){
            //Put any validation here that you want.
            $this->name = $nameInput;
        }

        private function setReturnArray(){
            $identifierInput = $this->identifier;
            $nameInput = $this->name;
            AllFunctions::errorCheck($this->identifier == NULL, "No Identifier Set.");
            $nameInput = ($nameInput === NULL) ? $identifierInput : $nameInput;
            $this->returnArray = [$identifierInput, $nameInput];
        }
    }
?>