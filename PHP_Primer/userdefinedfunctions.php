
<?php  
$title="functions";$h1 = "functions";
include "./includes/header.php";?>

    <?php

    function writeMessage()
    {
        echo "hello there </br>";
    }

    writeMessage();


    function addNumbers($a, $b)
    {
        return $a + $b;
    }

    echo addNumbers(33.24, 67.98);

    echo "</br>";
    function divideNumbers($a, $b)
    {
        $result =  $a / $b;
        echo "the result is :$result";
    }

    divideNumbers(30, 7);
    echo "</br>";


    $a = 100;
    divideNumbers($a, 20);
    echo "</br>";




    function changeNum($num)
    {

        $num = $num + 10;
        return $num;
    }
    $num = 500;

    echo changeNum($num); //output is 510
    echo "</br>";
    echo "$num"; //output is 500



    // function changeNum(&$num){

    //     $num = $num + 10;
    //     return $num;
    // }
    // $num = 500;

    // echo changeNum($num);//output is 510
    // echo "</br>";
    // echo "$num";//output is 510

       
    ?>
<?php require "./includes/footer.php"?>