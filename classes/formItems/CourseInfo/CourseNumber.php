<?php 
    class CourseNumber extends Course {
        protected string $value = '';
        protected string $htmlOutput = '';
        protected string $mySQLField = 'Number';

        public function __construct(){
            //Set the HTML for the value based on whether this is a preselected course or not.
            if(isset(parent::$courseID)){
                //This is a provided course ID. We can get the values.
                $id = parent::$courseID;
                //Get the Number.
                $number = $this->getValue($id);
                $this->set("value", $number);
            }
            $this->set('htmlOutput', $this->buildHTML());
        }

        protected function buildHTML() : string {
            $outputHTML = [
                "<div>",
                    "<label for='courseNumber'>Course Number</label>",
                    "<input type='text' id='courseNumber' name='courseNumber' value='{$this->value}'>",
                "</div>",
            ];
            $outputString = implode("", $outputHTML);
            return $outputString;
        }
    }
?>