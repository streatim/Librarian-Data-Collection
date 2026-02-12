<?php 
    class CourseTitle extends Course {
        protected string $value = '';
        protected string $htmlOutput = '';
        protected string $mySQLField = 'Name';

        public function __construct(){
            //Set the HTML for the value based on whether this is a preselected course or not.
            if(isset(parent::$courseID)){
                //This is a provided course ID. We can get the values.
                $id = parent::$courseID;
                //Get the Title.
                $title = $this->getValue($id);
                $this->set("value", $title);
            }
            $this->set('htmlOutput', $this->buildHTML());
        }

        protected function buildHTML() : string {
            $outputHTML = [
                "<div>",
                    "<label for ='courseTitle'>Course/Program Title</label>",
                    "<input type='text' id='courseTitle' name='courseTitle' value='{$this->value}' required/>",
                "</div>",
            ];
            $outputString = implode("", $outputHTML);
            return $outputString;
        }
    }
?>