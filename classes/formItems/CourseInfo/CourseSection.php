<?php 
    class CourseSection extends Course {
        protected string $value = '';
        protected string $htmlOutput = '';
        protected string $mySQLField = 'Section';

        public function __construct(){
            //Set the HTML for the value based on whether this is a preselected course or not.
            if(isset(parent::$courseID)){
                //This is a provided course ID. We can get the values.
                $id = parent::$courseID;
                //Get the Number.
                $section = $this->getValue($id);
                $this->set("value", $section);
            }
            $this->set('htmlOutput', $this->buildHTML());
        }

        public function buildHTML() : string {
            $outputHTML = [
                "<div>",
                    "<label for='sectionNumber'>Section Number (if applicable)</label>",
                    "<input type='text' id='sectionNumber' name='sectionNumber' value='{$this->value}'>",
                "</div>",
            ];
            $outputString = implode("", $outputHTML);
            return $outputString;
        }
    }
?>