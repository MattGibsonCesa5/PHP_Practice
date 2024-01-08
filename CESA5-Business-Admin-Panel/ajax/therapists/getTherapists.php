<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // initialize array to store therapists
        $therapists = [];

        // get additional required files
        include("../../includes/config.php");
        include("../../includes/functions.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        // verify the user has permission to manage therapists
        if (checkUserPermission($conn, "VIEW_EMPLOYEES_ALL") && checkUserPermission($conn, "EDIT_EMPLOYEES"))
        {
            // get all therapists
            $getTherapists = mysqli_query($conn, "SELECT u.id, u.fname, u.lname FROM therapists d
                                                JOIN users u ON d.user_id=u.id
                                                ORDER BY u.lname ASC, u.fname ASC");
            if (mysqli_num_rows($getTherapists) > 0)
            {
                while ($therapist = mysqli_fetch_array($getTherapists))
                {
                    // store therapist data locally
                    $therapist_id = $therapist["id"];
                    $therapist_fname = $therapist["fname"];
                    $therapist_lname = $therapist["lname"];
                    $therapist_dname = $therapist_lname.", ".$therapist_fname;

                    // build the actions column
                    $actions = "<button class='btn btn-danger float-end' type='button' onclick='getRemoveTherapistModal(".$therapist_id.");'><i class='fa-solid fa-trash-can'></i></button>";

                    // build the temporary array to store the therapist
                    $temp = [];
                    $temp["name"] = $therapist_dname;
                    $temp["actions"] = $actions;

                    // add temporary array to master list
                    $therapists[] = $temp;
                }
            }
        }
        
        // disconnect from the database
        mysqli_close($conn);

        // send data to be printed
        $fullData = [];
        $fullData["draw"] = 1;
        $fullData["data"] = $therapists;
        echo json_encode($fullData);
    }
?>