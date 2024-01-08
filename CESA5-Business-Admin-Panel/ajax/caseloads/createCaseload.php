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
            if (isset($_POST["subcategory_id"]) && $_POST["subcategory_id"] <> "") { $subcategory_id = $_POST["subcategory_id"]; } else { $subcategory_id = null; }
            if (isset($_POST["period"]) && $_POST["period"] <> "") { $period = $_POST["period"]; } else { $period = null; }

            // verify the period is set and exists
            if ($period != null && $period_id = getPeriodID($conn, $period))
            {
                // verify the therapist is set and exists
                if ($therapist_id != null && verifyTherapist($conn, $therapist_id))
                {
                    // verify the category is set and exists
                    if ($category_id != null && verifyCaseloadCategory($conn, $category_id))
                    {
                        // verify the subcategory exists and is valid for the category
                        if (verifyCaseloadSubcategory($conn, $category_id, $subcategory_id))
                        {
                            // get the therapist's display name
                            $name = getUserDisplayName($conn, $therapist_id);

                            // get the name of the category and subcategory
                            $category_name = getCaseloadCategoryName($conn, $category_id);
                            $subcategory_name = getCaseloadSubcategoryName($conn, $subcategory_id);
                            
                            // verify that the therapist is not already assigned to this category
                            $verifyUniqueness = mysqli_prepare($conn, "SELECT id FROM caseloads WHERE employee_id=? AND category_id=? AND subcategory_id=?");
                            mysqli_stmt_bind_param($verifyUniqueness, "iii", $therapist_id, $category_id, $subcategory_id);
                            if (mysqli_stmt_execute($verifyUniqueness))
                            {
                                $verifyUniquenessResult = mysqli_stmt_get_result($verifyUniqueness);
                                if (mysqli_num_rows($verifyUniquenessResult) == 0) // caseload for this therapist and category is unique; continue
                                {
                                    // add employee as therapist
                                    $createCaseload = mysqli_prepare($conn, "INSERT INTO caseloads (employee_id, category_id, subcategory_id) VALUES (?, ?, ?)");
                                    mysqli_stmt_bind_param($createCaseload, "iii", $therapist_id, $category_id, $subcategory_id);
                                    if (mysqli_stmt_execute($createCaseload)) 
                                    { 
                                        // get the new caseload's ID
                                        $caseload_id = mysqli_insert_id($conn);

                                        // log caseload creation to screen
                                        echo "<span class=\"log-success\">Successfully</span> added a new caseload for the therapist $name. Category for caseload is $category_name, with subcategory $subcategory_name.<br>"; 

                                        // set the caseload's status to active for the period provided
                                        $setStatus = mysqli_prepare($conn, "INSERT INTO caseloads_status (caseload_id, period_id, status) VALUES (?, ?, 1)");
                                        mysqli_stmt_bind_param($setStatus, "ii", $caseload_id, $period_id);
                                        mysqli_stmt_execute($setStatus);

                                        // set the caseload's status to inactive for all other periods
                                        $getPeriods = mysqli_prepare($conn, "SELECT id FROM periods WHERE id!=?");
                                        mysqli_stmt_bind_param($getPeriods, "i", $period_id);
                                        if (mysqli_stmt_execute($getPeriods))
                                        {
                                            $getPeriodsResults = mysqli_stmt_get_result($getPeriods);
                                            if (mysqli_num_rows($getPeriodsResults) > 0)
                                            {
                                                // loop through all existiing periods
                                                while ($period = mysqli_fetch_array($getPeriodsResults))
                                                {
                                                    // store period ID
                                                    $looped_period_id = $period["id"];

                                                    // set the caseload's status to inactive for the looped period
                                                    $setStatus = mysqli_prepare($conn, "INSERT INTO caseloads_status (caseload_id, period_id, status) VALUES (?, ?, 0)");
                                                    mysqli_stmt_bind_param($setStatus, "ii", $caseload_id, $looped_period_id);
                                                    mysqli_stmt_execute($setStatus);
                                                }
                                            }
                                        }

                                        // log caseload creation
                                        $message = "Successfully created a new caseload for $name. Category for caseload is $category_name, with subcategory $subcategory_name.";
                                        $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                                        mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                                        mysqli_stmt_execute($log);
                                    }
                                    else { echo "<span class=\"log-fail\">Failed</span> to create the caseload for $name. An unexpected error has occurred! Please try again later.<br>"; }
                                }
                                else { echo "<span class=\"log-fail\">Failed</span> to create the caseload. The therapist $name already has a caseload created for them in the $category_name category!<br>"; }
                            }
                            else { echo "<span class=\"log-fail\">Failed</span> to create the caseload. An unexpected error has occurred! Please try again later.<br>"; }
                        }
                        else { echo "<span class=\"log-fail\">Failed</span> to create the caseload. The subcategory selected was invalid for the category selected!<br>"; }
                    }
                    else { echo "<span class=\"log-fail\">Failed</span> to create the caseload. You must select a valid category the caseload is assigned to!<br>"; }
                }
                else { echo "<span class=\"log-fail\">Failed</span> to create the caseload. You must select a valid therapist for the caseload.<br>"; }
            }
            else { echo "<span class=\"log-fail\">Failed</span> to create the caseload. The period selected was invalid!<br>"; }
        }
        else { echo "<span class=\"log-fail\">Failed</span> to create the caseload. Your account does not have permission to create new caseloads.<br>"; }
        
        // disconnect from the database
        mysqli_close($conn);
    }
?>