
<?php  
$title="swith/case statement";$h1 = "swith/case statement";
include "./includes/header.php";?>
<?php
$grade = "A";
switch ($grade) {
    case 'F':
        echo "Grade is an F";
        break;
    case 'D':
        echo "Grade is a D";
        break;
    case 'C':
        echo "Grade is a C";
        break;
    case 'B':
        echo "Grade is a B";
        break;
    case 'A':
        echo "Grade is an A";
        break;
    default:
        echo "Invalid letter grade";
        break;
}
?>
<?php require "./includes/footer.php"?>