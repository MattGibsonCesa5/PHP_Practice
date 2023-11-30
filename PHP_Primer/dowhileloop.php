
<?php $title = "do while loops";
$h1 = "do while loop";
include "./includes/header.php";?>



    <?php 

$grade = 0;
//do while is a post conditon loop
   do {
    echo " grade is currently the value of : $grade";
    $grade+=5;
   } while ($grade <= 10);

    
    
    ?>

<?php require "./includes/footer.php"?>