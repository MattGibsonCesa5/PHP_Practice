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
            if (isset($_POST["caseload_id"]) && $_POST["caseload_id"] <> "") { $caseload_id = $_POST["caseload_id"]; } else { $caseload_id = null; }
            if (isset($_POST["category_id"]) && $_POST["category_id"] <> "") { $category_id = $_POST["category_id"]; } else { $category_id = null; }
            if (isset($_POST["subcategory_id"]) && $_POST["subcategory_id"] <> "") { $subcategory_id = $_POST["subcategory_id"]; } else { $subcategory_id = null; }
            if (isset($_POST["status"]) && (is_numeric($_POST["status"]) && $_POST["status"] == 1)) { $status = $_POST["status"]; } else { $status = 0; }
            if (isset($_POST["period"]) && $_POST["period"] <> "") { $period = $_POST["period"]; } else { $period = null; }

            // verify the caseload is set and exists
            if ($caseload_id != null && verifyCaseload($conn, $caseload_id))
            {
                // verify the period is set and exists
                if ($period != null && $period_id = getPeriodID($conn, $period))
                {
                    // verify the category is set and exists
                    if ($category_id != null && verifyCaseloadCategory($conn, $category_id))
                    {
                        // verify the subcategory exists and is valid for the category
                        if (verifyCaseloadSubcategory($conn, $category_id, $subcategory_id))
                        {
                            // get the caseload's therapist
                            $therapist_id = getCaseloadTherapist($conn, $caseload_id);
                            if ($therapist_id != null)
                            {
                                // get the employee's display name
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
                                        try
                                        {
                                            $editCaseload = mysqli_prepare($conn, "UPDATE caseloads SET category_id=?, subcategory_id=? WHERE id=?");
                                            mysqli_stmt_bind_param($editCaseload, "iii", $category_id, $subcategory_id, $caseload_id);
                                            if (mysqli_stmt_execute($editCaseload)) 
                                            { 
                                                // update the caseload's status for just the period provided
                                                $setStatus = mysqli_prepare($conn, "UPDATE caseloads_status SET status=? WHERE caseload_id=? AND period_id=?");
                                                mysqli_stmt_bind_param($setStatus, "isi", $status, $caseload_id, $period_id);
                                                mysqli_stmt_execute($setStatus);

                                                // log caseload edit
                                                echo "<span class=\"log-success\">Successfully</span> edited $name's caseload. Set the category to $category_name and subcategory to $subcategory_name.<br>";
                                                $message = "Successfully edited $name's caseload (employee ID: $therapist_id; caseload ID: $caseload_id). Set the category to $category_name and subcategory to $subcategory_name.";
                                                $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                                                mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                                                mysqli_stmt_execute($log);
                                            }
                                            else { echo "<span class=\"log-fail\">Failed</span> to edit the caseload. An unexpected error has occurred! Please try again later.<br>"; }
                                        }
                                        catch (Exception $e)
                                        {
                                            echo "<span class=\"log-fail\">Failed</span> to edit the caseload. An unexpected error has occurred! Please try again later.<br>";
                                        }
                                    }
                                    else { echo "<span class=\"log-fail\">Failed</span> to edit the caseload. The therapist $name already has a caseload created for them in the $category_name category with the subcategory $subcategory_name!<br>"; }
                                }
                                else { echo "<span class=\"log-fail\">Failed</span> to edit the caseload. An unexpected error has occurred! Please try again later.<br>"; }
                            }
                            else { echo "<span class=\"log-fail\">Failed</span> to edit the caseload. An unexpected error has occurred! Please try again later.<br>"; }
                        }
                        else { echo "<span class=\"log-fail\">Failed</span> to edit the caseload. The subcategory selected was invalid for the category selected!<br>"; }
                    }
                    else { echo "<span class=\"log-fail\">Failed</span> to edit the caseload. You must select a valid category the caseload is assigned to!<br>"; }
                }
                else { echo "<span class=\"log-fail\">Failed</span> to edit the caseload. The period selected does not exist!<br>"; }
            }
            else { echo "<span class=\"log-fail\">Failed</span> to edit the caseload. The caseload selected does not exist!<br>"; }
        }
        else { echo "<span class=\"log-fail\">Failed</span> to edit the caseload. Your account does not have permission to edit caseloads.<br>"; }
        
        // disconnect from the database
        mysqli_close($conn);
    }
?>