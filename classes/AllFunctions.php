<?php 
    class AllFunctions {
        //Verifies an object is an array before counting it. Done for PHP 8.0+ Typing requirements.
        public static function countArray($array) : int {
            $output = (is_array($array)) ? count($array) : 0;
            return $output;
        }

        //Error checking function. Provide a test and error message, it will display the message and stop the program if it's broken.
        public static function errorCheck($booleanTest, $errorMsg){
            if($booleanTest){
                echo "<strong>ERROR MESSAGE</strong>: {$errorMsg}";
                exit; //Completely stop the program.
            }
        }

        //Take in two values and check their types, returning true or false whether they're the same.
        public static function typeCheck($value1, $value2){
            $type1 = getType($value1);
            $type2 = getType($value2);
            $sameType = ($type1 === $type2);
            return $sameType;
        }
    }
?>