<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // initialize array to store directors
        $directors = [];

        // get additional required files
        include("../../includes/config.php");
        include("../../includes/functions.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        // verify the user has permission to manage directors
        if (checkUserPermission($conn, "VIEW_EMPLOYEES_ALL") && checkUserPermission($conn, "EDIT_EMPLOYEES"))
        {
            // get all directors
            $getDirectors = mysqli_query($conn, "SELECT u.id, u.fname, u.lname FROM directors d
                                                JOIN users u ON d.user_id=u.id
                                                ORDER BY u.lname ASC, u.fname ASC");
            if (mysqli_num_rows($getDirectors) > 0)
            {
                while ($director = mysqli_fetch_array($getDirectors))
                {
                    // store director data locally
                    $director_id = $director["id"];
                    $director_fname = $director["fname"];
                    $director_lname = $director["lname"];
                    $director_dname = $director_lname.", ".$director_fname;

                    // build the actions column
                    $actions = "<button class='btn btn-danger float-end' type='button' onclick='getRemoveDirectorModal(".$director_id.");'><i class='fa-solid fa-trash-can'></i></button>";

                    // build the temporary array to store the director
                    $temp = [];
                    $temp["name"] = $director_dname;
                    $temp["departments"] = "";
                    $temp["actions"] = $actions;

                    // add temporary array to master list
                    $directors[] = $temp;
                }
            }
        }
        
        // disconnect from the database
        mysqli_close($conn);

        // send data to be printed
        $fullData = [];
        $fullData["draw"] = 1;
        $fullData["data"] = $directors;
        echo json_encode($fullData);
    }
?>