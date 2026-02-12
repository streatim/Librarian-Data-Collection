<?php 
    class CourseSemester extends Course {
        protected string $value = '';
        protected string $htmlOutput = '';

        public function __construct(){
            if(isset(parent::$courseID)){
                //This is a provided course ID. We can get the values.
                $id = parent::$courseID;
            }
            $this->set('htmlOutput', $this->buildHTML());
        }

        public function buildHTML() : string {
            $semesterList = [];
            $outputHTML = [
                "<div>",
                    "<label for ='semester'>Semester</label>",
                    "<select id='semester' name='semester'>",
            ];
            foreach($semesterList as $semester){
                $selected = ($semester == $this->value) ? 'selected' : '';
                $outputHTML[] = "<option id='{$semester}' value='{$semester}' name='{$semester}' {$selected}>{$semester}</option>";
            }
            $outputHTML[] = "</select></div>";
            $outputString = implode("", $outputHTML);
            return $outputString;
        }
    }
?>