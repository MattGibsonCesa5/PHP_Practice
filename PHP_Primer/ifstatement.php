
<?php 
$title = "if statement";
$h1 = "if statement";
include "./includes/header.php";?>



    <?php 

    echo "<h2> If Statement</h2>";
   
    $grade = 59;

    if  ($grade === 59 || $grade === 100) {
        echo '<h1>grade is on either ends of the extreme so send the test back to me and I will re-grade and verify the grade </h1>';
    }else if ($grade === 69){
        echo '<h1> LOL you scored exactly <span style="color: purple">69</span></h1>';
    }else if($grade <= 59) {
        // adding inline styling
       
    }else if ($grade <= 69) {
        echo '<h1>grade is an <span style="color: orange">D</span></h1>';
    } else if ($grade <= 79) {
        echo '<h1>grade is an <span style="color: yellow">C</span></h1>';
    } else if ($grade <= 89) {
        echo '<h1>grade is an <span style="color: lime">B</span></h1>';
    
    } else{
        echo '<h1>grade is an <span style="color: green">A</span></h1>';
    }

    
    
    ?>

<?php require "./includes/footer.php"?>