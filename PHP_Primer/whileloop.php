
<?php  
$title="while loop";$h1 = "while loop";
include "./includes/header.php";?>


    <?php 
// //while loops need a starting point otherwise I would they know when
//  thet reac a certain value. Basically is the same thing as a for loop but
//has different applications
$grade = 0;
//while is a pre0condition loop
   while ($grade <= 10) {

    echo " grade is currently the value of : $grade";
    $grade+=5;
    echo"</br>";
   }

    
    
    ?>


<?php require "./includes/footer.php"?>