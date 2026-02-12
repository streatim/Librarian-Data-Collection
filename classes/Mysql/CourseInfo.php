<?php 
    class CourseInfo extends MySQL {
                    //Get a Distinct list of Dateperiods used in CourseInfo.
    //An assumption made is that you only want to capture the upcoming academic year (if it starts this year) and the existing one...UNTIL two weeks after the start of the new Academic Year. Basically, at the start of the calendar year the upcoming academic year becomes available for selection, and the old academic year stops being accessible two weeks after the start of the new one.
    //We are also going to get the Academic Years that are being counted here - those will be used to limit all the other lists.
        
        private function installSQL(){
            //Install the bare minimum of CourseInfo (which doesn't include modular form items or initial setup)
            $sqlQuery = [
                "CREATE TABLE IF NOT EXISTS `CourseInfo` (",
                    "`CourseID` int(10) NOT NULL auto_increment,",
                    "`CreatedDate` DATE NOT NULL DEFAULT CURRENT_TIMESTAMP,",
                    "`Creator`",
                    "PRIMARY KEY USING BTREE (`CourseID`)",
                    "CONSTRAINT FK_Creator FOREIGN KEY (`Creator`)",
                    "REFERENCES Users(UserID)",
                ") ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT DEFAULT CHARSET=utf8;",
            ];
        }
    
    
        public function getDatePeriods($userID){
            $semesterYearQuery = [
                "SELECT DISTINCT A.Semester, A.Year",
                "FROM CourseInfo A",
                "LEFT JOIN BridgeLibrarianCourse A1 ON A.CourseID = A1.CourseID",
                "LEFT JOIN ML_Public_Website.SemesterInfo B ON",
                "(A.Semester = B.Semester AND A.Year = YEAR(B.StartDate))",
                "WHERE A1.Librarian LIKE ?",
                "AND (B.AcademicYear LIKE CONCAT((",
                    "IF(CURRENT_DATE < DATE_ADD((",
                        "SELECT A1.StartDate",
                        "FROM ML_Public_Website.SemesterInfo A1",
                        "WHERE A1.Semester = 'Summer I'",
                        "AND YEAR(A1.StartDate) = YEAR(CURRENT_DATE)", 
                        "LIMIT 1)",
                    ", INTERVAL 2 WEEK),", 
                "YEAR(DATE_SUB(CURRENT_DATE, INTERVAL 1 YEAR)), YEAR(CURRENT_DATE))), '%')",
                "OR B.AcademicYear LIKE CONCAT(YEAR(CURRENT_DATE), '%'))",
                "ORDER BY B.StartDate, B.EndDate"
            ];

            $queryResult = MySQL::query($semesterYearQuery, [$userID]);
            $output = [];
            foreach($queryResult as $result){
                $output["{$result['Year']}{$result['Semester']}"] = "{$result['Year']}-{$result['Semester']}";
            }
            return $output;
        }

        public function getAcademicYears(){
            $academicYearQuery = [
                "SELECT DISTINCT A.AcademicYear",
                "FROM ML_Public_Website.SemesterInfo A",
                "WHERE (A.AcademicYear LIKE CONCAT((",
                    "IF(CURRENT_DATE < DATE_ADD((",
                        "SELECT A1.StartDate",
                        "FROM ML_Public_Website.SemesterInfo A1",
                        "WHERE A1.Semester = 'Summer I'",
                        "AND YEAR(A1.StartDate) = YEAR(CURRENT_DATE)", 
                        "LIMIT 1)",
                    ", INTERVAL 2 WEEK),", 
                "YEAR(DATE_SUB(CURRENT_DATE, INTERVAL 1 YEAR)), YEAR(CURRENT_DATE))), '%')",
                "OR A.AcademicYear LIKE CONCAT(YEAR(CURRENT_DATE), '%'))",
                "ORDER BY A.StartDate, A.EndDate"
            ];

            $queryResult = MySQL::query($academicYearQuery);
            $output = [];
            foreach($queryResult as $result){
                $output[] = $result['AcademicYear'];
            }
            return $output;
        }

        public function getCourseList($userID, $academicArray){
            $fillPlaceholders = implode(',', array_fill(0, AllFunctions::countArray($academicArray), '?'));
            //Get Distinct Course Names. Only use courses from the academic years listed above.
            $courseQuery = [
                "SELECT A.CourseID, CONCAT(A.Name, IF(A.Section='', '', CONCAT(' - ', A.Section))) AS Name, CONCAT(A.Year, A.Semester) AS DatePeriod",
                "FROM CourseInfo A",
                "LEFT JOIN BridgeLibrarianCourse A1 ON A.CourseID = A1.CourseID",
                "LEFT JOIN ML_Public_Website.SemesterInfo B ON",
                "(A.Semester = B.Semester AND A.Year = YEAR(B.StartDate))",
                "WHERE A1.Librarian LIKE ?",
                "AND B.AcademicYear IN ({$fillPlaceholders});"
            ];
            $inputArray = $academicArray;
            array_unshift($inputArray, $userID);
            $queryResult = MySQL::query($courseQuery, $inputArray);
            $output = [];
            foreach($queryResult as $result){
                $output[$result['CourseID']] = [
                    'Name'=>$result['Name'], 
                    'DatePeriod'=>$result['DatePeriod'],
                ];
            }

            return $output;
        }
    }
?>