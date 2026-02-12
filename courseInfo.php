<?php
// include($_SERVER['DOCUMENT_ROOT'] .'/{Path to your website header page}');
?>
<?php require("topScript.php"); ?>
<?php /*
<?php //Get Distinct Course Names
    $courseQuery = "SELECT CourseID FROM ML_LRC.BridgeLibrarianCourse WHERE Librarian LIKE '".$whoami."';";
    foreach($libraryDB->query($courseQuery) as $courseName){
        $courseList[] = $courseName['CourseID'];
    }

    //Check to see if an existing course is selected. If so, gather that information.
    if($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['courseID']) && in_array($_GET['courseID'], $courseList)){
        $courseID = $_GET['courseID'];
        $searchCourse = $libraryDB->prepare("SET @searchCourse = :searchCourse;");
        $searchCourse->bindValue(":searchCourse", $courseID, PDO::PARAM_STR);
        $searchCourse->execute();

        $updateQuery = [
            "SELECT A.Name, A.Number, A.Section, A.Year, A.Semester, A.Students, A.Delivery,",
            "GROUP_CONCAT(DISTINCT B.LevelID) AS Levels,",
            "GROUP_CONCAT(DISTINCT C.AssessID) AS Assessments,",
            "GROUP_CONCAT(DISTINCT D.ProgramID) AS Programs,",
            "GROUP_CONCAT(DISTINCT E.ActivityID) AS Activities,",
            "GROUP_CONCAT(DISTINCT F.Librarian) AS Librarians,",
            "GROUP_CONCAT(DISTINCT G.LibGuideID) AS LibGuides",
            "FROM ML_LRC.CourseInfo A",
            "LEFT JOIN ML_LRC.BridgeCourseLevel B ON A.CourseID = B.CourseID",
            "LEFT JOIN ML_LRC.BridgeCourseAssessment C ON A.CourseID = C.CourseID",
            "LEFT JOIN ML_LRC.BridgeCourseProgram D ON A.CourseID = D.CourseID",
            "LEFT JOIN ML_LRC.BridgeActivitiesCourses E ON A.CourseID = E.CourseID",
            "LEFT JOIN ML_LRC.BridgeLibrarianCourse F ON A.CourseID = F.CourseID",
            "LEFT JOIN ML_LRC.BridgeCourseLibGuides G ON A.CourseID = G.CourseID",
            "WHERE A.CourseID = @searchCourse GROUP BY A.CourseID"
        ];

        $courseInfQuery = $libraryDB->prepare(implode(" ", $updateQuery));
        $courseInfQuery->execute();
        $courseInfoList = $courseInfQuery->fetchAll(); 

        //Set the default form Values with the results gathered.
        $formValue = array(
            'Name' => $courseInfoList[0]['Name'],
            'Number' => $courseInfoList[0]['Number'],
            'Section' => $courseInfoList[0]['Section'],
            'Year' => $courseInfoList[0]['Year'],
            'Semester' => $courseInfoList[0]['Semester'],
            'Students' => $courseInfoList[0]['Students'],
            'Delivery' => $courseInfoList[0]['Delivery'],
            'Levels' => explode(',', $courseInfoList[0]['Levels']),
            'Assessments' => explode(',', $courseInfoList[0]['Assessments']),
            'Programs' => explode(',', $courseInfoList[0]['Programs']),
            'Activities' => explode(',', $courseInfoList[0]['Activities']),
            'LibGuides' => $courseInfoList[0]['LibGuides'],
            'Librarians' => explode(',', $courseInfoList[0]['Librarians']),
            );
    } else {
        //Set a default formValue because PHP must be sated.
        $formValue = [
            'Name' => '',
            'Number' => '',
            'Section' => '',
            'Year' => '',
            'Semester' => '',
            'Students' => '',
            'Delivery' => '',
            'Levels' => [],
            'Assessments' => [],
            'Programs' => [],
            'Activities' => [],
            'LibGuides' => '',
            'Librarians' =>[],
        ];
    }
?>
<?php 
if($_SERVER['REQUEST_METHOD'] == 'POST'){
    //The form has been submitted.
	//Course Information Array.
	$courseInfo = array(
		$_POST['courseTitle'],
		$_POST['courseNumber'],
		$_POST['sectionNumber'],
		$_POST['year'],
		$_POST['semester'],
		$_POST['studentNum'],
		$_POST['deliveryMethod'],
	);

	//Check to see if this is an update or a new one. If an update, update the CourseInfo and delete all bridge entries.
	if($_POST['update']==='FALSE'){
		//This is a new entry.
		//Insert Content Info. Needs to be done first because we need the CourseID.
		$contentInfoQuery = 'INSERT INTO ML_LRC.CourseInfo (Name, Number, Section, Year, Semester, Students, Delivery) VALUES (?, ?, ?, ?, ?, ?, ?);';
		$submitQuery = $libraryDB->prepare($contentInfoQuery);
		$submitQuery->execute($courseInfo);
		$courseID = $libraryDB->lastInsertId();
	} else {
		//This is an update. Update all the course Info then reset the bridge tables.
		$courseID = $_POST['update'];
		$searchCourse = $libraryDB->prepare("SET @courseID = :courseID;");
		$searchCourse->bindValue(":courseID", $courseID, PDO::PARAM_STR);
		$searchCourse->execute();

		//Update ContentInfo, delete Bridges.
		$contentInfoQuery = 'UPDATE ML_LRC.CourseInfo A SET A.Name = ?, A.Number = ?, A.Section = ?, A.Year = ?, A.Semester = ?, A.Students = ?, A.Delivery = ? WHERE A.CourseID = @courseID;';
		$submitQuery = $libraryDB->prepare($contentInfoQuery);
		$submitQuery->execute($courseInfo);

		//Delete Bridges for this Course
		$delActivity = "DELETE FROM ML_LRC.BridgeActivitiesCourses WHERE CourseID = @courseID";
		$submitQuery = $libraryDB->prepare($delActivity);
		$submitQuery->execute()	;

		$delLevel = "DELETE FROM ML_LRC.BridgeCourseLevel WHERE CourseID = @courseID";
		$submitQuery = $libraryDB->prepare($delLevel);
		$submitQuery->execute();

		$delAssess = "DELETE FROM ML_LRC.BridgeCourseAssessment WHERE CourseID = @courseID";
		$submitQuery = $libraryDB->prepare($delAssess);
		$submitQuery->execute();

		$delCP = "DELETE FROM ML_LRC.BridgeCourseProgram WHERE CourseID = @courseID";
		$submitQuery = $libraryDB->prepare($delCP);
		$submitQuery->execute();

		$delLG = "DELETE FROM ML_LRC.BridgeCourseLibGuides WHERE CourseID = @courseID";
		$submitQuery = $libraryDB->prepare($delLG);
		$submitQuery->execute();	

		$delLib = "DELETE FROM ML_LRC.BridgeLibrarianCourse WHERE CourseID = @courseID";
		$submitQuery = $libraryDB->prepare($delLib);
		$submitQuery->execute();	
	}

	//Assessment
	if(isset($_POST['assessments'])){
		foreach($_POST['assessments'] as $assessments){$assessValues[] = array($assessments, $courseID);}
	}

	//Course Values
	if(isset($_POST['courseLevel'])){
		foreach($_POST['courseLevel'] as $levelInput){
			$courseValues[] = array($courseID, $levelInput);
		}
	}

	//Program Values
	if(isset($_POST['schoolCollege'])){
		foreach($_POST['schoolCollege'] as $schoolCollege){
			$cpInsert[] = array($schoolCollege, $courseID);
		}
	}

	//Activity Values
	if(isset($_POST['activity'])){
		foreach($_POST['activity'] as $activityID){$activityInsert[] = array($activityID, $courseID);}
	}
	
	//LibGuides
	if(isset($_POST['libGuideUse'])){
		$libGuides = preg_replace( "/\r|\n/", "", $_POST['libGuideUse']);
		$libGuideList = explode(",", $libGuides);
		foreach($libGuideList as $guide){$libGuideInsert[] = array($courseID, $guide);}	
	}

	//Librarians
	if(isset($_POST['attachedLibrarians'])){
		foreach($_POST['attachedLibrarians'] as $librarian){$librarianInsert[] = array($courseID, $librarian);}
	}

	//Insert Assessments to the Bridge.
	if(isset($assessValues)){
		$bridgeAssessmentQuery = 'INSERT INTO ML_LRC.BridgeCourseAssessment (AssessID, CourseID) VALUES (?, ?);';
		$submitQuery = $libraryDB->prepare($bridgeAssessmentQuery);
		foreach($assessValues as $assessInsert){$submitQuery->execute($assessInsert);}
	}

	//Insert Course Level Values
	if(isset($courseValues)){
		$bridgeCourseQuery = 'INSERT INTO ML_LRC.BridgeCourseLevel (CourseID, LevelID) VALUES (?, ?);';
		$submitQuery = $libraryDB->prepare($bridgeCourseQuery);
		foreach($courseValues as $valueInsert){$submitQuery->execute($valueInsert);}
	}

	//Insert Bridge Course/Program values.
	if(isset($cpInsert)){
		$bridgeCPQuery = 'INSERT INTO ML_LRC.BridgeCourseProgram (ProgramID, CourseID) VALUES (?, ?);';
		$submitQuery = $libraryDB->prepare($bridgeCPQuery);
		foreach($cpInsert as $cpValue){$submitQuery->execute($cpValue);}
	}

	//Insert Activity Bridge values.
	if(isset($activityInsert)){
		$bridgeActivityQuery = 'INSERT INTO ML_LRC.BridgeActivitiesCourses (ActivityID, CourseID) VALUES (?, ?);';
		$submitQuery = $libraryDB->prepare($bridgeActivityQuery);
		foreach($activityInsert as $activityValues){$submitQuery->execute($activityValues);}
	}

	//Insert Listed LibGuides
	if(isset($libGuideInsert)){
		$bridgeLibGuideQuery = 'INSERT INTO ML_LRC.BridgeCourseLibGuides (CourseID, LibGuideID) VALUES (?, ?);';
		$submitQuery = $libraryDB->prepare($bridgeLibGuideQuery);
		foreach($libGuideInsert as $libGuideValues){$submitQuery->execute($libGuideValues);}
	}

	//Insert Librarians
	if(isset($librarianInsert)){
		$bridgeLibrarianQuery = 'INSERT INTO ML_LRC.BridgeLibrarianCourse (CourseID, Librarian) VALUES (?, ?);';
		$submitQuery = $libraryDB->prepare($bridgeLibrarianQuery);
		foreach($librarianInsert as $librarianValues){$submitQuery->execute($librarianValues);}
	}
}
?>
<?php
    //Get a Distinct list of Dateperiods used in CourseInfo.
    //An assumption made is that you only want to capture the upcoming academic year (if it starts this year) and the existing one...UNTIL two weeks after the start of the new Academic Year. Basically, at the start of the calendar year the upcoming academic year becomes available for selection, and the old academic year stops being accessible two weeks after the start of the new one.
    //We are also going to get the Academic Years that are being counted here - those will be used to limit all the other lists.
    $semesterYearQuery = [
        "SELECT DISTINCT A.Semester, A.Year, B.AcademicYear",
        "FROM ML_LRC.CourseInfo A",
        "LEFT JOIN ML_LRC.BridgeLibrarianCourse A1 ON A.CourseID = A1.CourseID",
        "LEFT JOIN ML_Public_Website.SemesterInfo B ON",
        "(A.Semester = B.Semester AND A.Year = YEAR(B.StartDate))",
        "WHERE A1.Librarian LIKE '".$whoami."'",
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

    $academicArrays = [];
    foreach($libraryDB->query(implode(" ", $semesterYearQuery)) as $semesterInfo){
        $datePeriod[$semesterInfo['Year'].$semesterInfo['Semester']] = $semesterInfo['Year'] . ' - ' .$semesterInfo['Semester'];
        if(!in_array($semesterInfo['AcademicYear'], $academicArrays)){
            $academicArrays[] = $semesterInfo['AcademicYear'];
        }
    }

    if(count2($academicArrays) == 0){
        $newAcademicQuery = [
            "SELECT DISTINCT A.AcademicYear",
            "FROM ML_Public_Website.SemesterInfo A",
            "WHERE A.AcademicYear LIKE CONCAT((",
                "IF(CURRENT_DATE < DATE_ADD((",
                    "SELECT A1.StartDate",
                    "FROM ML_Public_Website.SemesterInfo A1",
                    "WHERE A1.Semester = 'Summer I'",
                    "AND YEAR(A1.StartDate) = YEAR(CURRENT_DATE)", 
                    "LIMIT 1)",
                ", INTERVAL 2 WEEK),", 
            "YEAR(DATE_SUB(CURRENT_DATE, INTERVAL 1 YEAR)), YEAR(CURRENT_DATE))), '%')",
            "OR A.AcademicYear LIKE CONCAT(YEAR(CURRENT_DATE), '%')"
        ];

        foreach($libraryDB->query(implode(" ", $newAcademicQuery)) as $acaYear){
            $academicArrays[] = $acaYear['AcademicYear'];
        }
    }
    //Make a list of academic years gathered in the above query.
    $academicList = "'".implode("','", $academicArrays)."'";

    //Get Distinct Course Names. Only use courses from the academic years listed above.
    $courseQuery = [
        "SELECT A.CourseID, CONCAT(A.Name, IF(A.Section='', '', CONCAT(' - ', A.Section))) AS Name, CONCAT(A.Year, A.Semester) AS DatePeriod",
        "FROM ML_LRC.CourseInfo A",
        "LEFT JOIN ML_LRC.BridgeLibrarianCourse A1 ON A.CourseID = A1.CourseID",
        "LEFT JOIN ML_Public_Website.SemesterInfo B ON",
        "(A.Semester = B.Semester AND A.Year = YEAR(B.StartDate))",
        "WHERE A1.Librarian LIKE '".$whoami."'",
        "AND B.AcademicYear IN (".$academicList.");"
    ];

    $courseList = [];
    foreach($libraryDB->query(implode(" ",$courseQuery)) as $courseName){
        $courseList[$courseName['CourseID']] = array('Name'=>$courseName['Name'], 'DatePeriod'=>$courseName['DatePeriod']);
    }

    $yearlist = [];
    //Get a Distinct List of years that have been put into ML_Public_Website.SemesterInfo
    foreach($libraryDB->query("SELECT DISTINCT Year(StartDate) AS Year FROM ML_Public_Website.SemesterInfo WHERE AcademicYear IN (".$academicList.")") as $yearInfo){
        $yearList[] = $yearInfo['Year'];
    }

    $librarianList = [];
    //Get a list of all LRC Librarians. 
    $librarianQuery = [
        "SELECT A.UniqName, CONCAT(A.StaffFName, ' ', A.StaffLName) AS 'Name'",
        "FROM ML_Public_Website.Staff A",
        "WHERE A.DeptList LIKE '%LRC%'",
        "ORDER BY A.StaffLName"
    ];
    foreach($libraryDB->query(implode(" ", $librarianQuery)) as $librarian){$librarianList[$librarian['UniqName']] = $librarian['Name'];}
    $librarianDivCount = count2($librarianList)/3;

    //Select Course Level and IDs
    foreach($libraryDB->query("SELECT * FROM ML_LRC.CourseLevel") as $courseLevel){$courseLevels[$courseLevel['LevelID']] = $courseLevel['Name'];}

    //Select all Assessment Types.
    foreach($libraryDB->query("SELECT * FROM ML_LRC.CourseAssessment WHERE AssessName NOT LIKE 'Other';") as $assessType){$assessTypes[$assessType['AssessID']] = $assessType['AssessName'];}

    //Get a list of all Semesters
    $semesterQuery = "SELECT DISTINCT Semester FROM ML_Public_Website.SemesterInfo ORDER BY Semester;";
    foreach($libraryDB->query("SELECT DISTINCT Semester FROM ML_Public_Website.SemesterInfo ORDER BY Semester;") as $semesterName){
        $semesterList[] = $semesterName['Semester'];
    }

    //Get a list of all programs and colleges.
    $programCount = 0;
    foreach($libraryDB->query("SELECT A.ProgramID, A.Name, B.Name AS College FROM ML_Public_Website.Programs A LEFT JOIN ML_Public_Website.Colleges B ON A.CollegeID = B.ID ORDER BY A.Name;") AS $cp){
        $collegeProgram[$cp['College']][$cp['ProgramID']] = $cp['Name'];
        $programCount++;
    }

    $programDivCount = $programCount/3;
    $programInfo = $collegeProgram['Campus Program'];
    unset($collegeProgram['Campus Program']);
    ksort($collegeProgram);
    $collegeProgram['Campus Program'] = $programInfo;


    //Get a list of relevant Activities
    foreach($libraryDB->query("SELECT A.ActivityID, A.Name FROM ML_LRC.Activities A WHERE A.Countable = 0 ORDER BY A.Name;") as $activity){
        $activities[$activity['ActivityID']] = $activity['Name'];
    }
?>
<!-- A lot of this CSS relies on libraries that are beyond the scope and knowledge of this programmer. FontAwesome, I think Foundation, as well as local CSS -->
*/ ?>
<?php //Do any work necessary for building the form.
    //Check to see if there's a CourseID in the URL and, if so, set up the course Information.
    $updatedCourseID = ($_GET['courseID']) ?? "FALSE";
    if($updatedCourseID !== "FALSE"){
        //May want to validate that the user is on the listed course.
        COURSE::$courseID = (int) $updatedCourseID;
    }
?>
<script type="text/javascript">
	function courseUpdate(){
		const selectList = document.getElementById('courseID');
		for (i=1; i<selectList.options.length; i++){selectList.options[i].hidden = true;}
		const dateClass = document.getElementById('datePeriod');
		const courses = document.querySelectorAll('option[data-courseDate="'+dateClass[dateClass.selectedIndex].value+'"]');
		for (i=0; i<courses.length; i++){courses[i].hidden = false;}
	}
</script>
<h3><?php echo "Data Input for {$userName}"; ?></h3>
<form id="courseUpdateForm" name="courseUpdateForm" method="GET" target="_self">
    <select id="datePeriod" onchange="courseUpdate()">
        <option value="Unselected">Select Year - Semester</option>
        <?php 
            $courseInfo = new CourseInfo;
            $datePeriod = $courseInfo->getDatePeriods($userID);
            foreach($datePeriod as $key=>$semester){echo "<option value='{$key}'>{$semester}</option>";} 
        ?>
    </select>
    <!-- Second Column Information - List of Courses if a Course Information Form? --> 
    <select name="courseID" id="courseID">
        <option value="select">Select a Course</option>
        <?php 
            $academicYears = $courseInfo->getAcademicYears();
            $courseList = $courseInfo->getCourseList($userID, $academicYears);
            foreach($courseList as $id=>$course){
                $datePeriod = $course['DatePeriod'];
                $courseName = $course['Name'];
                echo "<option name='courseOptions' data-courseDate='{$datePeriod}' value='{$id}' hidden>{$courseName}</option>";
            } 
        ?>
    </select>
    <input type="submit" value="Select Course">	
</form>
<hr>
<!--This is where the report form pop up. Hello, reports!-->
<form name="courseInfoForm" method="POST" target="_self">
    <input type="hidden" id="librarian" name="librarian" value="<?php echo $userID; ?>">
    <input type="hidden" id="update" name="update" value="<?php echo $updatedCourseID; ?>">
    <div class="grid-3-columns">
        <?php 
            $formSection1Items = [
                new CourseTitle,
                new CourseNumber,
                //new CourseSection,
                new CourseYear,
                //new CourseSemester,
            ];
            foreach($formSection1Items as $item){echo $item->display();}
        ?>

        <div>
            <label for="studentNum"># of Students</label>
            <input type="number" id="studentNum" name="studentNum" min="0" value="<?php echo $courseInfo->get('students'); ?>">
        </div>
        <div>
			<label for="deliveryMethod">Delivery Method</label>
			<select id="deliveryMethod" name="deliveryMethod">
			<?php	$deliveryMethods = array('In-Person', 'Online', 'Hybrid');
					foreach($deliveryMethods as $method){
                        $selected = ($semester == $courseInfo->get('delivery')) ? 'selected' : '';
                        echo "<option id='{$method}' value='{$method}' name='{$method}' {$selected}>{$method}</option>";
					}
			?>
            </select>			
        </div>
        <div>
            <label for="courseLevel">Course Level</label>
            <?php 	
                $courseLevels = $courseLevels ?? [];
                foreach($courseLevels as $id=>$level){
                    $checked = in_array($id, $courseInfo->get('levels')) ? 'checked' : '';
                    $optionArray = [
                        "<div>",
                            "<input type='checkbox' value='{$id}' id='{$level}' name='courseLevel[]' {$checked}>",
                            "<label for='{$level}'>{$level}</label>",
                        "</div>",
                    ];
                    echo implode("", $optionArray);
                }
            ?>			
        </div>
        <div>
            <label for="assessmentLevel">Assessment(s) used in this course/program</label>
            <?php 	
                $assessTypes = $courseLevels ?? [];
                foreach($assessTypes as $key=>$assess){
                    $checked = in_array($key, $courseInfo->get('assessments')) ? 'checked' : '';
                    $optionArray = [
                        "<div>",
                            "<input type='checkbox' value='{$key}' id='{$assess}' name='assessmentLevel[]' {$checked}>",
                            "<label for='{$assess}'>{$assess}</label>",
                        "</div>",
                    ]; 
                    echo implode("", $optionArray);
                } 
            ?>
        </div>
    </div>
    <hr>
    <h4>College and Program Information</h4>
    <div id="formSection2">				
        <!-- College/Program Information -->
        <div>
            <?php
                $i=0;
                $collegeProgram = ($collegeProgram) ?? [];
                foreach($collegeProgram as $college=>$programs){		
                    if($i>($programDivCount-1)){echo '</div><div>'; $i = 0;}			
                    echo "<strong>{$college}</strong>";
                    foreach($programs as $id=>$program){	
                        if($i>($programDivCount-1)){echo '</div><div>'; $i = 0;}
                        $checked = in_array($key, $courseInfo->get('programs')) ? 'checked' : '';
                        $optionArray = [
                            "<div>",
                                "<input type='checkbox' value='{$id}' id='{$program}' name='schoolCollege[]' {$checked}>",
                                "<label for='{$program}'>{$program}</label>",
                            "</div>",
                        ]; 
                        echo implode("", $optionArray);
                        $i++;
                    }				
                }
			?>
        </div>
    </div>
    <hr>
    <h4>Faculty/Staff Collaboration: Course/Program Support</h4>
    <div id="formSection3">
        <div>
            <?php
				$i=0;
                $divSplit = FALSE;
                $activities = [];
				$activityDivCheck = (AllFunctions::countArray($activities)/2)-1;
				foreach($activities as $key=>$activity){
					if($i>$activityDivCheck && !$divSplit){echo '</div><div>'; $divSplit = TRUE;}
					echo '<div><input type="checkbox" value="'.$key.'" id="'.$activity.'" name=activity[]"';
					if(in_array($key, $courseInfo->get('activities'))){echo ' checked';}
					echo '><label for="'.$activity.'">'.$activity.'</label></div>';	
					$i++;
				}
			?>
        </div>
    </div>
    <div id="formSection4">
        <img src="img/guideID.PNG" id="guideImage" alt="An image showing the location of the LibGuide ID in the guide list">
        <label for="libGuideUse">IDs for LibGuides Used (Please use the numerical LibGuide only. This is found in the far-left column of the guide list (under Content&#8594;Guides)).<br>If multiple LibGuides were used, separate IDs with a comma.</label>
	    <input type="text" title="Please use the numerical LibGuide ID only, separated by a comma." pattern="^[0-9]*[,0-9]*$" value="<?php echo implode(", ", $courseInfo->get('libGuides')); ?>" name="libGuideUse" id="libGuideUse">
    </div>
    <hr>
    <div id="formSection5">
        <label for="attachedLibrarians">Librarian(s) who can edit this course and add activites</label>
        <div>
            <?php 
                $i=1;
                $librarianList = [];
                $divSplit = FALSE;
                $librarianDivCount = count2($librarianList)/3;
                foreach($librarianList as $uniqname=>$librarian){
                    if($i>$librarianDivCount && !$divSplit){echo '</div><div>'; $divSplit = TRUE;}
                    $checkAttributes = (in_array($uniqname, $courseInfo->get('librarians'))||$uniqname == $userID) ? ' disabled checked' : '';
                    $optionArray = [
                        "<div>",
                            "<input type='checkbox' value='{$uniqname}' id='{$uniqname}' name='attachedLibrarians[]' {$checked}>",
                            "<label for='{$uniqname}'>{$librarian}</label>",
                        "</div>",
                    ]; 
                    echo implode("", $optionArray);
                    //If this person was already checked, we've disabled them on the public form which doesn't send the values through POST. Add a hidden, enabled field to do that.
                    if($checkAttributes !== ''){echo "<input name='attachedLibrarians[]' type='hidden' value='{$uniqname}'/>";}
                    $i++;
                }
            ?>
        </div>
    </div>
    <hr>
</form>
<?php /*
		<!-- Pull potential options from Database /write the "Other" script-->
		<div class="large-12 columns">
			<div class="large-12 columns">
				<div class="large-6 columns">			
					<input type="Submit" value="<?php if(isset($_GET['courseID'])){echo 'Update Course Information';}else{echo 'Submit New Course';} ?>">
				</div>			
				<div class="large-6 columns">
					<?php
						if(isset($formValue)){echo '<a href="courseInfo.php" target="_SELF"><input type="button" value="Start New Course"></a>';}
					?>				
				</div>
			</div>
		</div>			
<?php include($_SERVER['DOCUMENT_ROOT'] .'/staff/footer.php');?> 
*/ ?>