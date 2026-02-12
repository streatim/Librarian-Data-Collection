<?php //INCLUDE Scripts and $whoami value.
include('functions.php'); //Functions script included in the repo.
//Uncomment lines 3-6 after revising them to gather the information you want using your systems.
//include($_SERVER['DOCUMENT_ROOT'] . '/{PATH TO PDO MySQL DATABASE CONNECTION}');
//$whoami = {function/variable that will have the user's unique id of some kind. For UM that's the uniqname}
//$my_nameis = {function/variable that will have the user's human-readable name (for me, that's "Tim Streasick")}
?>
<?php
// include($_SERVER['DOCUMENT_ROOT'] .'/{Path to website header page}');
?>
<?php
if($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['uinteractID'])){
	//Get a whitelist of Interactions by the librarian.
	$whiteListQuery = [
		'SELECT A.InteractionID AS ID',
		'FROM ML_LRC.BridgeLibrarianInteraction A',
		'WHERE A.Librarian LIKE "'.$whoami.'";',
	];
	foreach($libraryDB->query(implode(" ", $whiteListQuery)) as $interaction){$WhiteListInteractions[] = $interaction['ID'];}
	if(in_array($_GET['uinteractID'], $WhiteListInteractions)){
		//Get information about the interaction (Date Period, Course, Type of Activity, Date of Activity, list of Activities)
		$interactionID = $_GET['uinteractID'];
		$update = $interactionID;

		$searchInteract = $libraryDB->prepare("SET @interactID = :interactID;");
		$searchInteract->bindValue(":interactID", $interactionID, PDO::PARAM_STR);
		$searchInteract->execute();	
		
		$interactQuery = [
			'SELECT A.CourseID, A.Type, A.InteractionDate AS DATE, CONCAT(B.Year, B.Semester) AS DatePeriod,',
			'GROUP_CONCAT(C.ActivityID) AS Activities, GROUP_CONCAT(D.Librarian) AS Librarians',
			'FROM ML_LRC.Interaction A',
			'LEFT JOIN ML_LRC.CourseInfo B ON A.CourseID = B.CourseID',
			'RIGHT JOIN ML_LRC.BridgeActivitiesInteraction C ON A.InteractionID = C.InteractionID',
			'LEFT JOIN ML_LRC.BridgeLibrarianInteraction D ON A.InteractionID = D.InteractionID',
			'WHERE A.InteractionID = @interactID;'
		];

		$courseInfQuery = $libraryDB->prepare(implode(' ',$interactQuery));
		$courseInfQuery->execute();
		$courseInfoList = $courseInfQuery->fetchAll(); 
	
		$formValue = [
				'Semester' => $courseInfoList[0]['DatePeriod'],
				'CourseID'	 => $courseInfoList[0]['CourseID'],
				'ActivityDate'=> $courseInfoList[0]['DATE'],
				'ActivityType'=> $courseInfoList[0]['Type'],
				'Activities' => explode(',',$courseInfoList[0]['Activities']),
				'Librarians' => explode(',',$courseInfoList[0]['Librarians']),
		];
	} else { //Someone threw an interaction ID into the script that doesn't exist. Redirect to the form without any GET variables.
        header("Location: ".$_SERVER['DOCUMENT_ROOT']."/secure/library/rec/courseInteraction.php");
    }
} else {
	$formValue = [
		'Semester' => '',
		'CourseID'	 => '',
		'ActivityDate'=> '',
		'ActivityType'=> '',
		'Librarians' => [],
		'Activities' => [],
	];
}
?>
<?php //Process Form Insertions

if($_SERVER['REQUEST_METHOD'] === 'POST'){
	
	//ML_LRC.Interaction
	$courseID = $_POST['realCourseList'];
	$interactionType = $_POST['interaction'];
	$interactionDate = $_POST['interactDate'];

	$formInfo = [
		$interactionType,
		$courseID,
		$interactionDate
	];

	//$_POST['update'] = (if FALSE, it's a new record. If not false, it should be an interact ID.)
	if($_POST['update'] === 'FALSE'){
		//Insert Interaction Info. Needs to be done first because we need the InteractionID.
		$interactionQuery = 'INSERT INTO ML_LRC.Interaction (Type, CourseID, InteractionDate) VALUES (?, ?, ?);';
		$submitQuery = $libraryDB->prepare($interactionQuery);		
		$submitQuery->execute($formInfo);
		$interactID = $libraryDB->lastInsertId();	
	} else {
		//We already have the ID - update ML_LRC.Interaction and delete the Bridge Entries.
		$interactID = $_POST['update'];

		$searchCourse = $libraryDB->prepare("SET @interactID = :interactID;");
		$searchCourse->bindValue(":interactID", $interactID, PDO::PARAM_STR);
		$searchCourse->execute();
		
		//Update Interaction, delete Bridges.
		$contentInfoQuery = 'UPDATE ML_LRC.Interaction A SET A.Type = ?, A.CourseID = ?, A.InteractionDate = ? WHERE A.InteractionID = @interactID;';
		$submitQuery = $libraryDB->prepare($contentInfoQuery);
		$submitQuery->execute($formInfo);

		//Delete Bridges for this Course
		$delActivity = "DELETE FROM ML_LRC.BridgeActivitiesInteraction WHERE InteractionID = @interactID";
		$submitQuery = $libraryDB->prepare($delActivity);
		$submitQuery->execute()	;	

		$delLibrarian = "DELETE FROM ML_LRC.BridgeLibrarianInteraction WHERE InteractionID = @interactID";
		$submitQuery = $libraryDB->prepare($delLibrarian);
		$submitQuery->execute()	;	
	}

	//Set Bridge Information for Activity Values
	$activityInsert = [];
	if(isset($_POST['activity'])){
		foreach($_POST['activity'] as $activityID){$activityInsert[] = array($interactID, $activityID);}
	}

	//Insert Activity Bridge values.
	if(count($activityInsert)>0){
		$bridgeActivityQuery = 'INSERT INTO ML_LRC.BridgeActivitiesInteraction (InteractionID, ActivityID) VALUES (?, ?);';
		$submitQuery = $libraryDB->prepare($bridgeActivityQuery);
		foreach($activityInsert as $activityValues){$submitQuery->execute($activityValues);}
	}

	//Set Bridge Information for Librarians.
	$librarianInsert = [];
	if(isset($_POST['attachedLibrarians'])){
		foreach($_POST['attachedLibrarians'] as $librarian){$librarianInsert[] = array($interactID, $librarian);}
	}

	//Insert Librarian Bridge values.
	if(count($librarianInsert)>0){
		$bridgeLibrarianQuery = 'INSERT INTO ML_LRC.BridgeLibrarianInteraction (InteractionID, Librarian) VALUES (?, ?);';
		$submitQuery = $libraryDB->prepare($bridgeLibrarianQuery);
		foreach($librarianInsert as $librarianValues){$submitQuery->execute($librarianValues);}
	}

}
?>
<?php //Build the form.
//Get a Distinct list of Dateperiods used in CourseInfo.
//An assumption made is that you only want to capture the upcoming academic year (if it starts this year) and the existing one...UNTIL two weeks after the start of the new Academic Year. Basically, at the start of the calendar year the upcoming academic year becomes available for selection, and the old academic year stops being accessible two weeks after the start of the new one.
//We are also going to get the Academic Years that are being counted here - those will be used to limit all the other lists.
//This will ABSOLUTELY need to be rewritten for the specific campus using this. 
$semesterYearQuery = [
	"SELECT DISTINCT A.Semester, A.Year, B.AcademicYear",
	"FROM ML_LRC.CourseInfo A",
	"LEFT JOIN ML_LRC.BridgeLibrarianCourse A1 ON A.CourseID = A1.CourseID",
	"LEFT JOIN ML_Public_Website.SemesterInfo B ON",
	"(A.Semester = B.Semester AND A.Year = YEAR(B.StartDate))",
	"WHERE (B.AcademicYear LIKE CONCAT((",
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
//Make a list of academic years gathered in the above query.
$academicList = "'".implode("','", $academicArrays)."'";

//Get Distinct Course Names for Librarian
$courseQuery = [
	"SELECT A.CourseID, CONCAT(A.Name, IF(A.Section='', '', CONCAT(' - ', A.Section))) AS Name, CONCAT(A.Year, A.Semester) AS DatePeriod, GROUP_CONCAT(A1.Librarian) AS Librarians",
	"FROM ML_LRC.CourseInfo A",
	"LEFT JOIN ML_LRC.BridgeLibrarianCourse A1 ON A.CourseID = A1.CourseID",
	"LEFT JOIN ML_Public_Website.SemesterInfo B ON",
	"(A.Semester = B.Semester AND A.Year = YEAR(B.StartDate))",
	"WHERE A1.Librarian LIKE '".$whoami."'",
	"AND B.AcademicYear IN (".$academicList.")",
	"GROUP BY A.CourseID",
];
	$courseList = [];
foreach($libraryDB->query(implode(" ",$courseQuery)) as $courseName){
	$courseList[$courseName['CourseID']] = [
		'Name'=>$courseName['Name'], 
		'DatePeriod'=>$courseName['DatePeriod'],
		'Librarians'=>explode(',', $courseName['Librarians']),
	];
}	

//Get a list of Interactions by the librarian. This may include courses that they cannot insert new activites in (see above)
$interactQuery = [
	"SELECT A.CourseID, CONCAT(A.Name, IF(A.Section='', '', CONCAT(' - ', A.Section))) AS CourseName,", 
	"CONCAT(DATE_FORMAT(B.InteractionDate, '%Y/%m/%d'), ' - ', C.Type) AS InteractName, B.InteractionID AS ID, B.CourseID AS CourseID,",
	"CONCAT(A.Year, A.Semester) AS DatePeriod",
	"FROM ML_LRC.CourseInfo A",
	"LEFT JOIN ML_Public_Website.SemesterInfo D ON",
	"(A.Semester = D.Semester AND A.Year = YEAR(D.StartDate))",
	"RIGHT JOIN ML_LRC.Interaction B ON A.CourseID = B.CourseID",
	"LEFT JOIN ML_LRC.InteractionType C ON B.Type = C.TypeID", 
	"RIGHT JOIN ML_LRC.BridgeLibrarianInteraction E ON B.InteractionID = E.InteractionID",
	"WHERE E.Librarian LIKE '".$whoami."'",
	"AND D.AcademicYear IN (".$academicList.")",
	"GROUP BY B.InteractionID"
];

$interactCourses = [];
foreach($libraryDB->query(implode(" ",$interactQuery)) as $interaction){
	$interactions[$interaction['ID']] = array('Name'=>$interaction['InteractName'], 'Course'=>$interaction['CourseID']);
	$interactCourses[$interaction['CourseID']] = [
		'Name' => $interaction['CourseName'],
		'DatePeriod' => $interaction['DatePeriod'],
	];
}

//Get a list of potential interaction types.
foreach($libraryDB->query('SELECT * FROM ML_LRC.InteractionType;') as $intType){
	$intList[$intType['TypeID']] = $intType['Type'];
}
asort($intList);

//Get a list of activities.
foreach($libraryDB->query("SELECT A.ActivityID, A.Name FROM ML_LRC.Activities A WHERE A.Countable = 1 ORDER BY A.Name") as $activity){
	$activities[$activity['ActivityID']] = $activity['Name'];
}

//Get a list of all Staff. 
$librarianList = [];
$librarianQuery = [
	"SELECT A.UniqName, CONCAT(A.StaffFName, ' ', A.StaffLName) AS 'Name'",
	"FROM ML_Public_Website.Staff A",
	"ORDER BY A.StaffLName"
];
foreach($libraryDB->query(implode(" ", $librarianQuery)) as $librarian){$librarianList[$librarian['UniqName']] = $librarian['Name'];}
$librarianDivCount = count2($librarianList)/3;

?>
<div class="imageBanner toolsBanner">
	<div class="row large-12 columns">
		<div class="float-right">
			<h2>Countable Course Activities</h2>
		</div>
	</div>
</div>

<div class="row mainSection">	
	<div class="large-12 columns ">	<!--Navigation Section-->
		<nav aria-label="You are here:" role="navigation">
			<!--Breadcrumb-->
			<ul class="breadcrumbs">
				<li><a href="../../../staff"><i class="fa fa-home" aria-hidden="true"></i>&nbsp;Home</a></li>
				<li class="disabled">Tools</li>
				<li class="disabled">REC Tracking Form</li>
				<li>
					<span class="show-for-sr">Current: </span><strong>Countable Course Activities</strong><br><a href="courseInfo.php" target="_SELF">Course Information</a>
				</li>
			</ul>
		</nav>
    </div>

	<script type="text/javascript">
	const interactions = <?php echo json_encode($interactions); ?>;
	const courses = <?php echo json_encode($courseList); ?>;
	function updateList(redoList, checkList, update=''){
		const startPoint = (update!=='') ? 1 : 0;
		const selectList = document.getElementById(update+redoList);
		for (i=startPoint; i<selectList.options.length; i++){selectList.options[i].hidden = true;}
		
		const dateClass = document.getElementById(checkList);
		const courses = document.getElementsByClassName(update+dateClass[dateClass.selectedIndex].value);
		for (i=0; i<courses.length; i++){courses[i].hidden = false;}
	}

	function updateLibrarian(){
		const course = document.getElementById('realCourseList');
		const cid = course.value;
		const courseInfo = courses[cid];
		const libList = courseInfo.Librarians;
		const div = document.getElementById('librarianList');
		div.innerHTML = '';
		for(i=0;i<libList.length;i++){
			let libInput = document.createElement('input');
				libInput.type = "checkbox";
				libInput.name = "Librarians[]";
				libInput.id = 'Lib'+libList[i];
				libInput.value = libList[i];
			div.appendChild(libInput);
			let libLabel = document.createElement('label');
				libLabel.innerText = libList[i];
				libLabel.htmlFor = 'Lib'+libList[i];
			div.appendChild(libLabel);
		}
		console.log(courseInfo.Librarians);
	}
</script>

	<!-- Main Menu Content -->
	<!--Drop down menu for Annual Reports or Individual Orders reports-->
	<h3>Data Input for <?php echo $my_nameis; ?></h3>
	<p><em><strong>Note:</strong> If there is a Research Skill or Delivery Method option you think should be added to the following form, please contact the head of the LRC.</em></p>
	<form name="interactionUpdateForm" method="GET" target="_self">
		<div class="large-3 columns">
			<select id="datePeriod" onchange="updateList('courseList', 'datePeriod', 'u')">
			<option value="select">Select a Year - Semester</option>			
			<?php foreach($datePeriod as $key=>$semester){echo '<option value="'.$key.'">'.$semester.'</option>';} ?>
			</select>
		</div>
		<div class="large-3 columns">
			<select id="ucourseList" onchange="updateList('interactID', 'ucourseList', 'u')">
				<option value="select">Select a Course</option>
				<?php foreach($interactCourses as $id=>$course){echo '<option name="courseOptions" class="u'.$course['DatePeriod'].'" value="'.$id.'" hidden>'.$course['Name'].'</option>';} ?>
			</select>
			<!-- Second Column Information - List of Courses if a Course Information Form? --> 
		</div>
		<div class="large-3 columns">
			<select name="uinteractID" id="uinteractID">
				<option value="select">Select an Activity</option>
				<?php foreach($interactions as $id=>$interactionArray){echo '<option class="u'.$interactionArray['Course'].'" value="'.$id.'" hidden>'.$interactionArray['Name'].'</option>';} ?>				
			</select>
		</div>
		<div class="large-3 columns">
			<input type="submit" value="Update Form">
		</div>		
	</form>
	<hr>
	
	<!--This is where the forms pop up. Hello, reports!-->
	<form name="interactionForm" method="POST" target="_self">
		<input type="hidden" name="update" value="<?php if(isset($update)){echo $update;}else{echo 'FALSE';} ?>">
		<div class="large-12 columns">	
			<div class="large-3 columns">
			<label for="realDatePeriod">Select a Date Period</label>			
				<select name="realDatePeriod" id="realDatePeriod" onchange="updateList('realCourseList', 'realDatePeriod')">
				<?php foreach($datePeriod as $key=>$semester){echo '<option value="'.$key.'"';
				if($formValue['Semester']==$key){echo 'selected';}
				echo '>'.$semester.'</option>';} ?>
				</select>			
			</div>	
			<div class="large-3 columns">
			<label for="realCourseList">Select a Course</label>			
					<?php //In the case of updates, there may be courses that are not in the following list. If so, then automatically set the value and turn off editing. ?>
					<?php if(isset($update)&&!isset($courseList[$formValue['CourseID']])){ //This is an update and it's not from a course the librarian can normally edit. ?>
						<select name="realCourseList" id="realCourseList" required disabled>
							<option value="<?php echo $formValue['CourseID']; ?>" selected><?php echo $interactCourses[$formValue['CourseID']]['Name']; ?></option>
						</select>
						<input type="hidden" name="realCourseList" value="<?php echo $formValue['CourseID']; ?>">
					<?php } else { ?>
						<select name="realCourseList" id="realCourseList" onchange="updateLibrarian()" required>
							<?php 
								foreach($courseList as $key=>$course){
									echo '<option value="'.$key.'" class="'.$course['DatePeriod'].'"';
									if($formValue['CourseID']==$key){echo ' selected';}else{echo ' hidden';}
									echo '>'.$course['Name'].'</option>';
								} ?>
						</select> 
					<?php } ?>
				<script type="text/javascript">updateList('realCourseList', 'realDatePeriod');</script>		
			</div>							
			<div class="large-3 columns">
			<label for="interaction">Activity Type</label>
				<select id="interaction" name="interaction">
				<?php 
				foreach($intList as $key=>$int){
					echo '<option value="'.$key.'" id="'.$int.'" name="'.$int.'"';
					if($key == $formValue['ActivityType']){echo ' selected';}
					echo '>'.$int.'</option>';} ?>
				</select>
			</div>
			<div class="large-3 columns">
				<label for="interactDate">Date of Activity</label>
				<input type="date" name="interactDate" id="interactDate" <?php if(isset($formValue['ActivityDate'])){echo 'value ="'.$formValue['ActivityDate'].'"';} ?>>
			</div>		
		</div>
		<h4>Research Skills Taught</h4>
		<div class="large-12 columns">
			<div class="large-6 columns">
			<?php
				$i = 0;
				$activityDivCount = (count($activities)/2)-1;
				foreach($activities as $key=>$activity){
					if($i>$activityDivCount){echo '</div><div class="large-6 columns">'; $activityDivCount = count($activities);}
					echo '<div><input type="checkbox" value="'.$key.'" id="'.$activity.'" name="activity[]"';
					if(in_array($key, $formValue['Activities'])){echo 'checked';}					
					echo '><label for="'.$activity.'">'.$activity.'</label></div>';
					$i++;
				}
			?>
			</div>
		</div>
		<hr>
		<div class="large-12 columns">
			<label for="attachedLibrarians">Librarian(s) attached to this course interaction</label>
			<div class="large-4 columns">
				<?php $i=1;
					foreach($librarianList as $uniqname=>$librarian){
						if($i>$librarianDivCount){echo '</div><div class="large-4 columns">'; $i=1;}
						$checkAttributes = "";
						if(in_array($uniqname, $formValue['Librarians'])||$uniqname == $whoami){
							$checkAttributes = ' disabled checked';
							echo '<input name="attachedLibrarians[]" type="hidden" value="'.$uniqname.'"/>';
						}
						echo '<input type="checkbox" value="'.$uniqname.'" id="'.$uniqname.'" name="attachedLibrarians[]"';
						echo $checkAttributes;
						echo '><label for="'.$uniqname.'">'.$librarian.'</label><br>'; 
						$i++;
					}
				?>
			</div>
		</div>
		<hr>
		<div class="large-12 columns">
			<div class="large-6 columns">
				<input type="Submit" value="<?php if(isset($_GET['uinteractID'])){echo 'Update Course Activity';}else{echo 'Submit New Activity';} ?>">
			</div>
			<div class="large-6 columns">
				<?php
					if(isset($formValue)){echo '<a href="courseInteraction.php" target="_SELF"><input type="button" value="Start New Interaction"></a>';}
				?>
			</div>
		</div>		
	</form>	
</div>
<?php include($_SERVER['DOCUMENT_ROOT'] .'/staff/footer.php');?> 