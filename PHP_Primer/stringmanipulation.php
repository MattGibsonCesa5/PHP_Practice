
<?php  
$title="string manipulation";$h1 = "string manipulation";
include "./includes/header.php";?>
<?php
    $string1= "im matt";
    $string2= "im chris";
    //concatenation is like this for string variables , OR
    echo "$string1, wedwdwe,  $string2";
    echo "</br>";
    //like this ,the udemy course said to do it like this:
    echo $string1 . ", wefwefwef ," . $string2;
    //, but both work and the top one is simplier
    echo "</br>";
    $myName="matt gibson";
    echo ucwords($myName);
    echo "</br>";
    echo ucfirst($myName);
    echo "</br>";
    echo strtoupper($myName);
    echo "</br>";
    echo strtolower($myName);
    echo "</br>";
    echo "</hr>";
    //repeat string 28 times
    echo "repeat string:" . str_repeat($myName, 28) . "</br>";
    echo "repeat string:" . strtoupper(str_repeat($myName, 28)) . "</br>";
    //extract the string starting from 3 position, get 7 
    //characters (spaces included) after the number at the 3 posiytion 
    //(starts at 0 like an index),
    // output is t gibso
    echo "repeat string:" . substr($myName, 3,7) . "</br>"

    ?>
<?php require "./includes/footer.php"?>