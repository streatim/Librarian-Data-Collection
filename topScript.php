<?php 
    /*
        This script is intended to hold code and HTML that needs to run on all the pages.
    */
?>
<?php //Prepare PHP to load classes in the classes directory.
    spl_autoload_register(function ($class) {
        $possiblePaths = [
            "classes",
            "classes/formItems/CourseInfo",
            "classes/mysql/",
        ];
        foreach($possiblePaths as $path){
            $classString = str_replace('\\', DIRECTORY_SEPARATOR, $class);
            $filePath = "{$path}/{$classString}.php";
            if(file_exists($filePath)) {
                require $filePath;
                break;
            }
        }

    });
?>
<?php //Determine the current user and set their values. You will need to rewrite the classes/Authenticate.php::authenticate() function in order to work with your system.
    $auth = new Authenticate();
    $authenticatedReturn = $auth->returnOutput();
    $user = new ActiveUser(...$authenticatedReturn);
    $userName = $user->get('name');
    $userID = $user->get('identifier');
?>
<?php //Stylesheet for all the forms ?>
<link rel="stylesheet" href="main.css"> 
<script src="main.js"></script>
<div id="formBody">
    <nav class="navMenu">
        <a class="navItem" href="courseInfo.php">Course Info</a>
        <a class="navItem" href="courseInteraction.php">Activities</a>
        <a class="navItem" href="reports.php">Reports</a>
        <a class="navItem" href="/">Home</a>
    </nav>