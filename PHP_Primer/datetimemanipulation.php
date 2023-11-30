
<?php $title = "date and time";
 $h1 = "date and time";
 include "./includes/header.php";?>
   
    <?php
    //datenow, getdate, mon,mday,year are all built in to php
    $datenow = getdate();

    
    echo $datenow['mon'] . ' ' . $datenow['mday'] . ', ' . $datenow['year'];

    echo "</br>";
    //time is built in function
echo time();

    
    ?>
<?php require "./includes/footer.php"?>
