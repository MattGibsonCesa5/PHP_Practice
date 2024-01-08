<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../includes/functions.php");
        include("../../includes/config.php");
        include("../../getSettings.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "VIEW_CASELOADS_ALL") && checkUserPermission($conn, "VIEW_THERAPISTS"))
        {
            // get parameters from POST
            if (isset($_POST["caseload_id"]) && $_POST["caseload_id"] <> "") { $caseload_id = $_POST["caseload_id"]; } else { $caseload_id = null; }
            if (isset($_POST["district_id"]) && $_POST["district_id"] <> "") { $district_id = $_POST["district_id"]; } else { $district_id = null; }
            if (isset($_POST["new_caseload"]) && $_POST["new_caseload"] <> "") { $new_caseload = $_POST["new_caseload"]; } else { $new_caseload = null; }
            if (isset($_POST["transfer_date"]) && $_POST["transfer_date"] <> "") { $transfer_date = $_POST["transfer_date"]; } else { $transfer_date = null; }
            if (isset($_POST["end_date"]) && $_POST["end_date"] <> "") { $end_date = $_POST["end_date"]; } else { $end_date = null; }
            if (isset($_POST["remove_iep"]) && is_numeric($_POST["remove_iep"]) && $_POST["remove_iep"] == 1) { $remove_iep = 1; } else { $remove_iep = 0; }
            if (isset($_POST["period"]) && $_POST["period"] <> "") { $period = $_POST["period"]; } else { $period = null; }
            
            // verify the period exists; if it exists, store the period ID
            if ($period != null && $period_id = getPeriodID($conn, $period)) 
            {
                // get the caseload term start date for the period
                $term_start = "2000-01-01";
                $getTermStart = mysqli_prepare($conn, "SELECT caseload_term_start FROM periods WHERE id=?");
                mysqli_stmt_bind_param($getTermStart, "i", $period_id);
                if (mysqli_stmt_execute($getTermStart))
                {
                    $getTermStartResult = mysqli_stmt_get_result($getTermStart);
                    if (mysqli_num_rows($getTermStartResult) > 0)
                    {
                        $term_start = mysqli_fetch_array($getTermStartResult)["caseload_term_start"];
                    }
                }

                // verify transfer date
                if (isset($transfer_date) && $transfer_date <> "") 
                {
                    if (strtotime($term_start) >= strtotime($transfer_date)) {  $transfer_date = $term_start; }
                }
                else { $transfer_date = $term_start;}

                // verify existing caseload exists
                if (verifyCaseload($conn, $caseload_id))
                {
                    // verify the caseload we are transferring students into exists
                    if (verifyCaseload($conn, $new_caseload))
                    {
                        // store caseload display names
                        $old_caseload_name = getCaseloadDisplayName($conn, $caseload_id);
                        $new_caseload_name = getCaseloadDisplayName($conn, $new_caseload);

                        if ($district_id != null && is_numeric($district_id))
                        {
                            // convert the transfer and end dates to the correct database format
                            $DB_transfer_date = date("Y-m-d", strtotime($transfer_date));
                            $DB_end_date = date("Y-m-d", strtotime($end_date));

                            // build and prepare the query to get the cases we are transferring based on parameters provided
                            if ($district_id == -1) // transferring all students within the caseload
                            {
                                $getCases = mysqli_prepare($conn, "SELECT * FROM cases WHERE caseload_id=? AND period_id=?");
                                mysqli_stmt_bind_param($getCases, "ii", $caseload_id, $period_id);
                            }
                            else // transferring students only from the selected district
                            {
                                // verify the district selected exists
                                if (verifyCustomer($conn, $district_id))
                                {
                                    $getCases = mysqli_prepare($conn, "SELECT * FROM cases WHERE caseload_id=? AND period_id=? AND district_attending=?");
                                    mysqli_stmt_bind_param($getCases, "iii", $caseload_id, $period_id, $district_id);
                                }
                            }

                            // execute the query to get the ID of cases we are transferring
                            if (isset($getCases) && mysqli_stmt_execute($getCases))
                            {
                                $getCasesResults = mysqli_stmt_get_result($getCases);
                                if (mysqli_num_rows($getCasesResults) > 0)
                                {
                                    while ($case = mysqli_fetch_array($getCasesResults))
                                    {
                                        // store the case details locally
                                        $case_id = $case["id"];
                                        $period_id = $case["period_id"];
                                        $student_id = $case["student_id"];
                                        $district = $case["district_attending"];
                                        $school = $case["school_attending"];
                                        $classroom_id = $case["classroom_id"];
                                        $grade = $case["grade_level"];
                                        $evaluation_method = $case["evaluation_method"];
                                        $enrollment_type = $case["enrollment_type"];
                                        $educational_plan = $case["educational_plan"];
                                        $residency = $case["residency"];
                                        $bill_to = $case["bill_to"];
                                        $billing_type = $case["billing_type"];
                                        $billing_notes = $case["billing_notes"];
                                        $frequency = $case["frequency"];
                                        $units = $case["estimated_uos"];
                                        $membership_days = $case["membership_days"];

                                        // get the students name
                                        $student_name = getStudentDisplayName($conn, $student_id);

                                        // check to see if the student had changes in their case; if so, get the most recent UOS and frequency
                                        $checkForChanges = mysqli_prepare($conn, "SELECT * FROM case_changes WHERE case_id=? ORDER BY start_date DESC LIMIT 1");
                                        mysqli_stmt_bind_param($checkForChanges, "i", $case_id);
                                        if (mysqli_stmt_execute($checkForChanges))
                                        {
                                            $checkForChangesResults = mysqli_stmt_get_result($checkForChanges);
                                            if (mysqli_num_rows($checkForChangesResults) > 0)
                                            {
                                                // store necessary change details locally
                                                $change_details = mysqli_fetch_array($checkForChangesResults);
                                                $change_frequency = $change_details["frequency"];
                                                $change_uos = $change_details["uos"];

                                                // override existing frequency and UOS to the latest changes
                                                $frequency = $change_frequency;
                                                $units = $change_uos;
                                            }
                                        }

                                        // create the new caseload
                                        $transferCaseload = mysqli_prepare($conn, "INSERT INTO cases (caseload_id, period_id, student_id, district_attending, school_attending, classroom_id, start_date, end_date, grade_level, evaluation_method, enrollment_type, educational_plan, residency, bill_to, billing_type, billing_notes, frequency, extra_ieps, extra_evaluations, estimated_uos, remove_iep, created_by, dismissal_reasoning_id, active, membership_days) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 0, ?, ?, ?, (SELECT id FROM caseload_dismissal_reasonings WHERE reason='Transferring caseloads'), 1, ?)");
                                        mysqli_stmt_bind_param($transferCaseload, "iiiiisssiiiisiissdiii", $new_caseload, $period_id, $student_id, $district, $school, $classroom_id, $DB_transfer_date, $DB_end_date, $grade, $evaluation_method, $enrollment_type, $educational_plan, $residency, $bill_to, $billing_type, $billing_notes, $frequency, $units, $remove_iep, $_SESSION["id"], $membership_days);
                                        if (mysqli_stmt_execute($transferCaseload)) // successfully executed query to transfer the caseload
                                        {
                                            // store the new case ID
                                            $new_case_id = mysqli_insert_id($conn);

                                            // log to screen transfer status
                                            echo "<span class=\"log-success\">Successfully</span> transferred $student_name from the $old_caseload_name caseload to the $new_caseload_name caseload.<br>";

                                            // if the start date is the same date as term start, delete prior case
                                            if (strtotime($DB_transfer_date) <= strtotime(date("Y-m-d", strtotime($term_start))))
                                            {
                                                // delete the prior case
                                                $deleteCase = mysqli_prepare($conn, "DELETE FROM cases WHERE id=?");
                                                mysqli_stmt_bind_param($deleteCase, "i", $case_id);
                                                if (mysqli_stmt_execute($deleteCase))
                                                {
                                                    // log case deletion
                                                    echo "<span class=\"log-success\">Successfully</span> deleted the prior case ID of $case_id for $student_name in the $old_caseload_name caseload (caseload ID: $caseload_id).<br>";
                                                    $message = "Successfully deleted the prior case ID of $case_id for $student_name in the $old_caseload_name caseload (caseload ID: $caseload_id).";
                                                    $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                                                    mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                                                    mysqli_stmt_execute($log);
                                                }
                                                else { echo "<span class=\"log-fail\">Failed</span> to delete the prior case ID of $case_id for $student_name in the $old_caseload_name caseload (caseload ID: $caseload_id).<br>"; }
                                            }
                                            else
                                            {
                                                // update the prior caseload's end date and active status
                                                $updatePriorCaseload = mysqli_prepare($conn, "UPDATE cases SET end_date=?, active=0 WHERE id=?");
                                                mysqli_stmt_bind_param($updatePriorCaseload, "si", $DB_transfer_date, $case_id);
                                                if (mysqli_stmt_execute($updatePriorCaseload)) // successfully executed the query to update the prior caseload
                                                {
                                                    // log caseload transfer
                                                    $message = "Successfully transferred $student_name (case ID: $case_id) from $old_caseload_name (caseload ID: $caseload_id) to $new_caseload_name (caseload ID: $new_caseload). 
                                                                The new case ID is $new_case_id, we have set the original case with ID of $case_id to inactive.";
                                                    $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                                                    mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                                                    mysqli_stmt_execute($log);
                                                }
                                                else { echo "<span class=\"log-fail\">Failed</span> to update the prior case details for $student_name. Please verify the end date and active status of the case in the original caseload.<br>"; }
                                            }
                                        }
                                        else { echo "<span class=\"log-fail\">Failed</span> to transfer $student_name to their new caseload. An unexpected error has occurred! Please try again later.<br>"; }
                                    }
                                }
                                else { echo "No cases found within the current caseload. <span class=\"log-fail\">Stopping</span> attempted transfer.<br>"; }
                            }
                            else { echo "<span class=\"log-fail\">Failed</span> to transfer the students from one caseload to another. An unexpected error has occurred! Please try again later. 1<br>"; }
                        }
                        else { echo "<span class=\"log-fail\">Failed</span> to transfer the students from one caseload to another. The students you are trying to transfer was invalid. Please try again! 2<br>"; }
                    }
                    else { echo "<span class=\"log-fail\">Failed</span> to transfer the students. The caseload you are trying to transfer students into does not exist! 3<br>"; }
                }
                else { echo "<span class=\"log-fail\">Failed</span> to transfer the students. The caseload selected does not exist! 4<br>"; }
            }
            else { echo "<span class=\"log-fail\">Failed</span> to transfer the students. The period selected does not exist! 5<br>"; }
        }
        else { echo "<span class=\"log-fail\">Failed</span> to transfer the students from one caseload to another. Your account does not have permission to edit caseloads.<br>"; }

        // disconnect from the database
        mysqli_close($conn);
    }
?>