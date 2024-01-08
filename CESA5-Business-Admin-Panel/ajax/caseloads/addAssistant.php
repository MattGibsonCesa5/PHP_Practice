<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../includes/functions.php");
        include("../../includes/config.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "ADD_THERAPISTS"))
        {
            // get parameters from POST
            if (isset($_POST["therapist_id"]) && $_POST["therapist_id"] <> "") { $therapist_id = $_POST["therapist_id"]; } else { $therapist_id = null; }
            if (isset($_POST["category_id"]) && $_POST["category_id"] <> "") { $category_id = $_POST["category_id"]; } else { $category_id = null; }

            if ($therapist_id != null)
            {
                if ($category_id != null)
                {
                    // verify the therapist exists
                    if (checkExistingEmployee($conn, $therapist_id))
                    {
                        // get the employee's display name
                        $name = getEmployeeDisplayName($conn, $therapist_id);

                        // verify the category exists
                        if (verifyCaseloadCategory($conn, $category_id))
                        {
                            // get the name of the category
                            $category_name = getCaseloadCategoryName($conn, $category_id);
                            
                            // verify that the therapist is not already assigned to this category
                            $verifyUniqueness = mysqli_prepare($conn, "SELECT id FROM caseload_assistants WHERE employee_id=? AND category_id=?");
                            mysqli_stmt_bind_param($verifyUniqueness, "ii", $therapist_id, $category_id);
                            if (mysqli_stmt_execute($verifyUniqueness))
                            {
                                $verifyUniquenessResult = mysqli_stmt_get_result($verifyUniqueness);
                                if (mysqli_num_rows($verifyUniquenessResult) == 0) // caseload for this therapist and category is unique; continue
                                {
                                    // add employee as therapist
                                    $addAssitant = mysqli_prepare($conn, "INSERT INTO caseload_assistants (employee_id, category_id) VALUES (?, ?)");
                                    mysqli_stmt_bind_param($addAssitant, "ii", $therapist_id, $category_id);
                                    if (mysqli_stmt_execute($addAssitant)) 
                                    { 
                                        // log caseload creation to screen
                                        echo "<span class=\"log-success\">Successfully</span> added $name as a designated assistant for $category_name caseloads.<br>"; 

                                        // log therapist addition
                                        $message = "Successfully added $name as a designated assistant for $category_name caseloads.";
                                        $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                                        mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                                        mysqli_stmt_execute($log);
                                    }
                                    else { echo "<span class=\"log-fail\">Failed</span> to add $name as a designated assistant. An unexpected error has occurred! Please try again later.<br>"; }
                                }
                                else { echo "<span class=\"log-fail\">Failed</span> to add $name as a designated assistant. $name already is designated as an assistant for $category_name caseloads!<br>"; }
                            }
                            else { echo "<span class=\"log-fail\">Failed</span> to add $name as a designated assistant. An unexpected error has occurred! Please try again later.<br>"; }
                        }
                        else { echo "<span class=\"log-fail\">Failed</span> to add $name as a designated assistant. The category selected does not exist!<br>"; }
                    }
                    else { echo "<span class=\"log-fail\">Failed</span> to add the selected employee as an assitant. The employee selected does not exist!<br>"; }
                }
                else { echo "<span class=\"log-fail\">Failed</span> to add the selected employee as an assitant. You must select a category the caseload is assigned to!<br>"; }
            }
            else { echo "<span class=\"log-fail\">Failed</span> to add an employee as an assitant. You must select a therapist for the caseload.<br>"; }
        }
        else { echo "<span class=\"log-fail\">Failed</span> to add an employee as an assitant. Your account does not have permission to create new caseloads.<br>"; }
        
        // disconnect from the database
        mysqli_close($conn);
    }
?>