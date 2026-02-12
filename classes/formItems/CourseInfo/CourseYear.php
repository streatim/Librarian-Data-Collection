<?php 
    class CourseYear extends Course {
        protected string $value = '';
        protected string $htmlOutput = '';
        protected string $mySQLField = 'Year';
        private int $yearRange = 2;

        public function __construct(){
            //Set the HTML for the value based on whether this is a preselected course or not.
            if(isset(parent::$courseID)){
                //This is a provided course ID. We can get the values.
                $id = parent::$courseID;
                //Get the Title.
                $year = (string) $this->getValue($id);
                $this->set("value", $year);
            }
            $this->set('htmlOutput', $this->buildHTML());
        }

        public function buildHTML() {
            $yearList = $this->getYearList();
            $outputHTML = [
                "<div>",
                    "<label for ='year'>Year</label>",
                    "<select id='year' name='year'>",
            ];
            foreach($yearList as $year){
                //For new course, default to this year. If there is a course, select the year selected for that course.
                //No Value and matches current year.
                $noValueCurrentYear = (($this->value == '') && ($year == date("Y")));
                //Matches set value.
                $matchesValue = ($year == $this->value);
                $selected = ($noValueCurrentYear || $matchesValue) ? 'selected' : '';
                $outputHTML[] = "<option id='{$year}' value='{$year}' name='{$year}' {$selected}>{$year}</option>";
            }
            $outputHTML[] = "</select></div>";
            $outputString = implode("", $outputHTML);
            return $outputString;
        }

        private function getYearList() :array {
            //Build an array of years within a particular range of the current year. Default is set to 1 (get last year, this year, and next year)
            $range = $this->yearRange;
            $outputArray = [];
            $i = 0-$range;
            for($i = 0-$range; $i<=$range; $i++){
                $dateString = "{$i} year";
                $timeString = strtotime($dateString, time());
                $date = date("Y", $timeString);
                $outputArray[] = $date;
            }
            return $outputArray;
        }
    }
?>