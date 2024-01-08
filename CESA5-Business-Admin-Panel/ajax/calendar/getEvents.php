<?php

session_start();
if (isset($_SESSION["status"]) && $_SESSION["status"] == 1) {
    // get additional required files
    include("../../includes/config.php");
    include("../../includes/functions.php");

    $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

 
    $user_id = $_SESSION["id"];
    // echo $user_id . "is the id of the current user";
    $getUsersEvents = mysqli_prepare($conn, "SELECT * FROM `events` WHERE `user_id` = ?");
    // bind the user_id parameter to the SQL query it finds the first occurence of a 
    // question mark in the query and replaces it with the value of the variable
    mysqli_stmt_bind_param($getUsersEvents, "i", $user_id);
    // execute the query
    if (mysqli_stmt_execute($getUsersEvents)) {
        // get result set from the query
        $Result = mysqli_stmt_get_result($getUsersEvents);
        //initialize an empty array to store the event associative arrays
        $events = [];
        // as long as there are rows of data in the result set, process each row into
        // an associative array and add it to the $events array
        while ($event = mysqli_fetch_array($Result)) {
            //store the entire newly created event associative
            //array in an array called $events, this will store allcolumns that
            //are returned from the query
            $events[] = $event;
        }


    
        // disconnect from the database
        mysqli_close($conn);
    }
}
