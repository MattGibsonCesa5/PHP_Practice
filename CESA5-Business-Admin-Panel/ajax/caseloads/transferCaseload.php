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
            if (isset($_POST["case_id"]) && $_POST["case_id"] <> "") { $case_id = $_POST["case_id"]; } else { $case_id = null; }
            if (isset($_POST["new_caseload"]) && $_POST["new_caseload"] <> "") { $caseload_id = $_POST["new_caseload"]; } else { $caseload_id = null; }
            if (isset($_POST["transfer_date"]) && $_POST["transfer_date"] <> "") { $transfer_date = $_POST["transfer_date"]; } else { $transfer_date = null; }
            if (isset($_POST["end_date"]) && $_POST["end_date"] <> "") { $end_date = $_POST["end_date"]; } else { $end_date = null; }
            if (isset($_POST["frequency"]) && $_POST["frequency"] <> "") { $frequency = $_POST["frequency"]; } else { $frequency = null; }
            if (isset($_POST["uos"]) && is_numeric($_POST["uos"])) { $units = $_POST["uos"]; } else { $units = 0; }
            if (isset($_POST["days"]) && is_numeric($_POST["days"])) { $days = $_POST["days"]; } else { $days = 0; }
            if (isset($_POST["classroom"]) && is_numeric($_POST["classroom"])) { $classroom_id = $_POST["classroom"]; } else { $classroom_id = null; }
            if (isset($_POST["IEP_status"]) && is_numeric($_POST["IEP_status"])) { $IEP_status = $_POST["IEP_status"]; } else { $IEP_status = 0; }
            if (isset($_POST["request_id"]) && $_POST["request_id"] <> "") { $request_id = $_POST["request_id"]; } else { $request_id = null; }

            // verify the case exists
            if (verifyCase($conn, $case_id))
            {
                // verify caseload exists
                if (verifyCaseload($conn, $caseload_id))
                {
                    // convert the transfer and end dates to the correct database format
                    $DB_transfer_date = date("Y-m-d", strtotime($transfer_date));
                    $DB_end_date = date("Y-m-d", strtotime($end_date));

                    // get current caseload details
                    $getCaseloadDetails = mysqli_prepare($conn, "SELECT * FROM cases WHERE id=?");
                    mysqli_stmt_bind_param($getCaseloadDetails, "i", $case_id);
                    if (mysqli_stmt_execute($getCaseloadDetails))
                    {
                        $getCaseloadDetailsResult = mysqli_stmt_get_result($getCaseloadDetails);
                        if (mysqli_num_rows($getCaseloadDetailsResult) > 0) // caseload details found
                        {
                            // store caseload details locally
                            $caseloadDetails = mysqli_fetch_array($getCaseloadDetailsResult);
                            $old_caseload_id = $caseloadDetails["caseload_id"];
                            $period_id = $caseloadDetails["period_id"];
                            $student_id = $caseloadDetails["student_id"];
                            $district = $caseloadDetails["district_attending"];
                            $school = $caseloadDetails["school_attending"];
                            $grade = $caseloadDetails["grade_level"];
                            $evaluation_method = $caseloadDetails["evaluation_method"];
                            $enrollment_type = $caseloadDetails["enrollment_type"];
                            $educational_plan = $caseloadDetails["educational_plan"];
                            $residency = $caseloadDetails["residency"];
                            $bill_to = $caseloadDetails["bill_to"];
                            $billing_type = $caseloadDetails["billing_type"];
                            $billing_notes = $caseloadDetails["billing_notes"];

                            // create the new caseload
                            $transferCaseload = mysqli_prepare($conn, "INSERT INTO cases (caseload_id, period_id, student_id, district_attending, school_attending, start_date, end_date, grade_level, evaluation_method, enrollment_type, educational_plan, residency, bill_to, billing_type, billing_notes, frequency, extra_ieps, extra_evaluations, estimated_uos, remove_iep, created_by, dismissal_reasoning_id, active, classroom_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 0, ?, ?, ?, 0, 1, ?)");
                            mysqli_stmt_bind_param($transferCaseload, "iiiisssiiiisiissdiii", $caseload_id, $period_id, $student_id, $district, $school, $DB_transfer_date, $DB_end_date, $grade, $evaluation_method, $enrollment_type, $educational_plan, $residency, $bill_to, $billing_type, $billing_notes, $frequency, $units, $IEP_status, $_SESSION["id"], $classroom_id);
                            if (mysqli_stmt_execute($transferCaseload)) // successfully executed query to transfer the caseload
                            {
                                // store the new case ID
                                $new_case_id = mysqli_insert_id($conn);

                                // build int for IEP removal - if IEP was not completed prior to transfer, we must set initial caseload to remove IEP
                                if ($IEP_status == 1) { $backload_IEP = 0; } else { $backload_IEP = 1; }

                                // update the prior caseload's end date and active status
                                $updatePriorCaseload = mysqli_prepare($conn, "UPDATE cases SET end_date=?, remove_iep=?, dismissed=1, dismissal_reasoning_id=(SELECT id FROM caseload_dismissal_reasonings WHERE reason='Transferring caseloads'), active=0 WHERE id=?");
                                mysqli_stmt_bind_param($updatePriorCaseload, "sii", $DB_transfer_date, $backload_IEP, $case_id);
                                if (mysqli_stmt_execute($updatePriorCaseload)) // successfully executed the query to update the prior caseload
                                {
                                    // log to screen transfer status
                                    echo "<span class=\"log-success\">Successfully</span> transferred the student to their new caseload.<br>";

                                    // if the billing type is day use, update the membership days
                                    if ($billing_type == 2)
                                    {
                                        $updateDays = mysqli_prepare($conn, "UPDATE cases SET membership_days=?, estimated_uos=0, frequency=null WHERE id=?");
                                        mysqli_stmt_bind_param($updateDays, "ii", $days, $new_case_id);
                                        if (!mysqli_stmt_execute($updateDays)) { echo "<span class=\"log-fail\">Failed</span> to set the membership days for the student.<br>"; }
                                    }

                                    // if the request ID was provided; update request to indicate transfer we completed
                                    if (isset($request_id) && $request_id != null)
                                    {
                                        // get the current timestamp
                                        $timestamp = date("Y-m-d H:i:s");

                                        // update the transfer request
                                        $updateRequest = mysqli_prepare($conn, "UPDATE caseload_transfers SET transfer_status=1, accepted_by=?, accepted_at=? WHERE id=?");
                                        mysqli_stmt_bind_param($updateRequest, "isi", $_SESSION["id"], $timestamp, $request_id);
                                        mysqli_stmt_execute($updateRequest);
                                    }

                                    // log caseload transfer
                                    $message = "Successfully transferred the case with ID of $case_id from caseload with ID of $old_caseload_id to the caseload with ID of $caseload_id. 
                                                The new case ID is $new_case_id, we have set the original case with ID of $case_id to inactive.";
                                    $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                                    mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                                    mysqli_stmt_execute($log);
                                }
                                else { echo "<span class=\"log-fail\">Failed</span> to update the prior caseload details. Please verify the end date and active status of the original caseload.<br>"; }
                            }
                            else { echo "<span class=\"log-fail\">Failed</span> to transfer the student from one caseload to another. An unexpected error has occurred! Please try again later.<br>"; }
                        }
                        else { echo "<span class=\"log-fail\">Failed</span> to transfer the student from one caseload to another. An unexpected error has occurred! Please try again later.<br>"; }
                    }
                    else { echo "<span class=\"log-fail\">Failed</span> to transfer the student from one caseload to another. An unexpected error has occurred! Please try again later.<br>"; }
                }
                else { echo "<span class=\"log-fail\">Failed</span> to transfer the student from one caseload to another. The new caseload does not exist!<br>"; }
            }
            else { echo "<span class=\"log-fail\">Failed</span> to edit the caseload. The caseload selected does not exist!<br>"; }
        }
        else { echo "<span class=\"log-fail\">Failed</span> to transfer the student from one caseload to another. Your account does not have permission to edit caseloads.<br>"; }

        // disconnect from the database
        mysqli_close($conn);
    }
?>